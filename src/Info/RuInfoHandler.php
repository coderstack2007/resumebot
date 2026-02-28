<?php
namespace App\Info;

use App\Keyboards\ru\LanguageKeyboard;
use App\Keyboards\ru\NameKeyboard;
use App\Keyboards\ru\CitiesKeyboard;
use App\Keyboards\ru\JobsKeyboard;
use App\Keyboards\ru\NumberKeyboard;
use App\Checking\ru\Check;
use App\Cities\ru\Cities;
use App\Backs\ru\BackHandler;
use App\Database;
use App\AdminDb;

class RuInfoHandler
{
    public static function getStartMessage()
    {
        return "✅ Язык выбран: Русский\n\nПожалуйста, введите ваше ФИО:";
    }

    public static function handleUserInput($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        if (!Check::checkUserStateExists($chat_id, $user_states)) {
            return false;
        }

        $user_state = $user_states[$chat_id];

        if (BackHandler::isBackButton($user_text)) {
            return BackHandler::handleBackButton($telegram, $chat_id, $user_text, $message_id, $user_states);
        }

        $keyboard = self::getKeyboardForStep($user_state['step'], $user_state);
        if (!Check::validateAndSendError($telegram, $chat_id, $user_text, $message_id, $keyboard)) {
            return false;
        }

        switch ($user_state['step']) {
            case 1:
                return self::handleName($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 2:
                return self::handleAge($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 3:
                return self::handlePhone($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 4:
                BackHandler::deleteMessage($telegram, $chat_id, $message_id);
                $telegram->sendMessage([
                    'chat_id'      => $chat_id,
                    'text'         => Check::getImageRequiredError(),
                    'reply_markup' => NameKeyboard::getBackName()
                ]);
                return false;
            case 5:
                return self::handleRegionSelection($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 6:
                return self::handleCitySelection($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 7:
                return self::handleConfirmation($telegram, $chat_id, $user_text, $message_id, $user_states);
        }

        return false;
    }

    /**
     * Обработка contact (номер через кнопку "Поделиться номером")
     */
    public static function handleContact($telegram, $chat_id, $contact, $message_id, &$user_states)
    {
        if (!Check::checkUserStateExists($chat_id, $user_states)) {
            return false;
        }

        $user_state = $user_states[$chat_id];

        if ($user_state['step'] !== 3) {
            return false;
        }

        $phone = $contact['phone_number'] ?? null;

        if (!$phone) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            $telegram->sendMessage([
                'chat_id'      => $chat_id,
                'text'         => "❌ Не удалось получить номер. Введите вручную или попробуйте снова:",
                'reply_markup' => NumberKeyboard::getPhoneKeyboard()
            ]);
            return false;
        }

        $cleanPhone = preg_replace('/[\s\(\)\-]/', '', $phone);

        BackHandler::deleteMessage($telegram, $chat_id, $message_id);

        $user_states[$chat_id]['phone'] = $cleanPhone;
        $user_states[$chat_id]['step']  = 4;

        $telegram->sendMessage([
            'chat_id'      => $chat_id,
            'text'         => Check::getPhoneAcceptedMessage() . "\n\n" . Check::getPhotoRequestMessage(),
            'reply_markup' => NameKeyboard::getBackName()
        ]);

        return true;
    }

    /**
     * Обработка фото
     */
    public static function handlePhoto($telegram, $chat_id, $photo_array, $message_id, &$user_states)
    {
        if (!isset($user_states[$chat_id]) || $user_states[$chat_id]['step'] !== 4) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            $telegram->sendMessage([
                'chat_id'      => $chat_id,
                'text'         => '❌ Ошибка: отправьте фото в нужный момент.',
                'reply_markup' => LanguageKeyboard::getBackKeyboard()
            ]);
            return;
        }

        try {
            $photo   = end($photo_array);
            $file_id = $photo['file_id'];

            $user_states[$chat_id]['photo_file_id'] = $file_id;

            $file_info = $telegram->getFile(['file_id' => $file_id]);

            if (!isset($file_info['file_path'])) {
                throw new \Exception('file_path отсутствует в ответе API');
            }

            $file_path = $file_info['file_path'];
            $token     = \App\BotSettings::TOKEN;
            $file_url  = "https://api.telegram.org/file/bot{$token}/{$file_path}";

            $ch = curl_init($file_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $file_content = curl_exec($ch);
            $http_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error   = curl_error($ch);
            curl_close($ch);

            if ($file_content === false || $http_code !== 200) {
                throw new \Exception('Не удалось скачать файл. HTTP: ' . $http_code . ', Error: ' . $curl_error);
            }

            $filename  = $chat_id . '_' . time() . '.jpg';
            $save_path = __DIR__ . '/../images/' . $filename;

            $dir = dirname($save_path);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    throw new \Exception('Не удалось создать директорию: ' . $dir);
                }
            }

            if (!is_writable($dir)) {
                throw new \Exception('Нет прав на запись в директорию: ' . $dir);
            }

            if (file_put_contents($save_path, $file_content) === false) {
                throw new \Exception('Не удалось сохранить файл');
            }

            $user_states[$chat_id]['photo_filename'] = $filename;
            $user_states[$chat_id]['step']           = 5;

            BackHandler::deleteMessage($telegram, $chat_id, $message_id);

            $telegram->sendMessage([
                'chat_id'      => $chat_id,
                'text'         => "✅ Фото получено!\n\n📍 Теперь выберите ваш регион:",
                'reply_markup' => CitiesKeyboard::getRegionsKeyboard()
            ]);

        } catch (\Exception $e) {
            error_log("❌ Ошибка при обработке фото для chat_id $chat_id: " . $e->getMessage());
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            $telegram->sendMessage([
                'chat_id'      => $chat_id,
                'text'         => '❌ Произошла ошибка при обработке фото. Попробуйте снова.',
                'reply_markup' => NameKeyboard::getBackName()
            ]);
        }
    }

    // ── Клавиатура по шагу ────────────────────────────────────

    private static function getKeyboardForStep($step, $user_state = [])
    {
        switch ($step) {
            case 1: return LanguageKeyboard::getBackKeyboard();
            case 2: return NameKeyboard::getBackName();
            case 3: return NumberKeyboard::getPhoneKeyboard();
            case 4: return NameKeyboard::getBackName();
            case 5: return CitiesKeyboard::getRegionsKeyboard();
            case 6:
                $region_id = $user_state['region_id'] ?? 1;
                return CitiesKeyboard::getCitiesKeyboard($region_id);
            case 7: return JobsKeyboard::getConfirmationKeyboard();
            default: return NameKeyboard::getBackName();
        }
    }

    // ── Шаги ──────────────────────────────────────────────────

    private static function handleName($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        BackHandler::deleteMessage($telegram, $chat_id, $message_id);

        $user_states[$chat_id]['name'] = $user_text;
        $user_states[$chat_id]['step'] = 2;

        $telegram->sendMessage([
            'chat_id'      => $chat_id,
            'text'         => Check::getNameAcceptedMessage(),
            'reply_markup' => NameKeyboard::getBackName()
        ]);

        return true;
    }

    private static function handleAge($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        if (!is_numeric($user_text)) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            $telegram->sendMessage([
                'chat_id'      => $chat_id,
                'text'         => Check::getAgeNumberError(),
                'reply_markup' => NameKeyboard::getBackName()
            ]);
            return false;
        }

        if (!Check::checkAge($user_text)) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            $telegram->sendMessage([
                'chat_id'      => $chat_id,
                'text'         => Check::getAgeRangeError(),
                'reply_markup' => NameKeyboard::getBackName()
            ]);
            return false;
        }

        BackHandler::deleteMessage($telegram, $chat_id, $message_id);

        $user_states[$chat_id]['age']  = (int)$user_text;
        $user_states[$chat_id]['step'] = 3;

        $telegram->sendMessage([
            'chat_id'      => $chat_id,
            'text'         => Check::getAgeAcceptedMessage(),
            'reply_markup' => NumberKeyboard::getPhoneKeyboard()
        ]);

        return true;
    }

    private static function handlePhone($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        if (!Check::checkPhoneNumber($user_text)) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            $telegram->sendMessage([
                'chat_id'      => $chat_id,
                'text'         => Check::getPhoneError(),
                'reply_markup' => NumberKeyboard::getPhoneKeyboard()
            ]);
            return false;
        }

        BackHandler::deleteMessage($telegram, $chat_id, $message_id);

        $user_states[$chat_id]['phone'] = preg_replace('/[\s\(\)\-]/', '', $user_text);
        $user_states[$chat_id]['step']  = 4;

        $telegram->sendMessage([
            'chat_id'      => $chat_id,
            'text'         => Check::getPhoneAcceptedMessage() . "\n\n" . Check::getPhotoRequestMessage(),
            'reply_markup' => NameKeyboard::getBackName()
        ]);

        return true;
    }

    private static function handleRegionSelection($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        $regions   = Cities::getRegions();
        $region_id = array_search($user_text, $regions);

        if ($region_id === false) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            $telegram->sendMessage([
                'chat_id'      => $chat_id,
                'text'         => '❌ Ошибка: регион не найден. Пожалуйста, используйте кнопки.',
                'reply_markup' => CitiesKeyboard::getRegionsKeyboard()
            ]);
            return false;
        }

        BackHandler::deleteMessage($telegram, $chat_id, $message_id);

        $user_states[$chat_id]['region_id'] = $region_id;
        $user_states[$chat_id]['step']      = 6;

        $telegram->sendMessage([
            'chat_id'      => $chat_id,
            'text'         => "✅ Регион выбран: $user_text\n\n🏙 Выберите ваш город:",
            'reply_markup' => CitiesKeyboard::getCitiesKeyboard($region_id)
        ]);

        return true;
    }

    private static function handleCitySelection($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        $region_id = $user_states[$chat_id]['region_id'];
        $cities    = Cities::getCitiesByRegion($region_id);
        $city_id   = array_search($user_text, $cities);

        if ($city_id === false) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            $telegram->sendMessage([
                'chat_id'      => $chat_id,
                'text'         => '❌ Ошибка: город не найден. Пожалуйста, используйте кнопки.',
                'reply_markup' => CitiesKeyboard::getCitiesKeyboard($region_id)
            ]);
            return false;
        }

        BackHandler::deleteMessage($telegram, $chat_id, $message_id);

        $user_states[$chat_id]['city_id'] = $city_id;
        $user_states[$chat_id]['step']    = 7;

        // ── Получаем название вакансии из adminresumes ────────
        $vacancy_id   = $user_states[$chat_id]['vacancy_id'] ?? null;
        $vacancy_name = '—';

        if ($vacancy_id) {
            $vacancy = AdminDb::getVacancyById((int)$vacancy_id);
            if ($vacancy) {
                $vacancy_name = $vacancy['position_name'] ?? '—';
                if (!empty($vacancy['branch_name'])) {
                    $vacancy_name .= ' | ' . $vacancy['branch_name'];
                }
            }
        }

        $region_name = Cities::getRegionName($region_id);
        $city_name   = Cities::getCityName($region_id, $city_id);

        $response_text  = "📋 Проверьте правильность введённых данных:\n\n";
        $response_text .= "👤 ФИО: "     . $user_states[$chat_id]['name']  . "\n";
        $response_text .= "🎂 Возраст: " . $user_states[$chat_id]['age']   . " лет\n";
        $response_text .= "📱 Телефон: " . $user_states[$chat_id]['phone'] . "\n";
        $response_text .= "📍 Регион: $region_name\n";
        $response_text .= "🏙 Город: $city_name\n";
        $response_text .= "💼 Вакансия: $vacancy_name\n";
        $response_text .= "\n❓ Все данные указаны верно?";

        // Отправляем с фото или без
        if (!empty($user_states[$chat_id]['photo_file_id'])) {
            $telegram->sendPhoto([
                'chat_id'      => $chat_id,
                'photo'        => $user_states[$chat_id]['photo_file_id'],
                'caption'      => $response_text,
                'reply_markup' => JobsKeyboard::getConfirmationKeyboard()
            ]);
        } else {
            $response_text .= "\n📸 Фото: не загружено";
            $telegram->sendMessage([
                'chat_id'      => $chat_id,
                'text'         => $response_text,
                'reply_markup' => JobsKeyboard::getConfirmationKeyboard()
            ]);
        }

        return true;
    }

    private static function handleConfirmation($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        if (JobsKeyboard::isConfirmButton($user_text)) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);

            $region_name  = Cities::getRegionName($user_states[$chat_id]['region_id']);
            $city_name    = Cities::getCityName($user_states[$chat_id]['region_id'], $user_states[$chat_id]['city_id']);

            $vacancy_id   = $user_states[$chat_id]['vacancy_id'] ?? null;
            $vacancy_name = '—';
            if ($vacancy_id) {
                $vacancy = AdminDb::getVacancyById((int)$vacancy_id);
                if ($vacancy) {
                    $vacancy_name = $vacancy['position_name'] ?? '—';
                    if (!empty($vacancy['branch_name'])) {
                        $vacancy_name .= ' | ' . $vacancy['branch_name'];
                    }
                }
            }

            $response_text  = "✅ Спасибо! Ваши данные сохранены:\n\n";
            $response_text .= "👤 ФИО: "     . $user_states[$chat_id]['name']  . "\n";
            $response_text .= "🎂 Возраст: " . $user_states[$chat_id]['age']   . " лет\n";
            $response_text .= "📱 Телефон: " . $user_states[$chat_id]['phone'] . "\n";
            $response_text .= "📍 Регион: $region_name\n";
            $response_text .= "🏙 Город: $city_name\n";
            $response_text .= "💼 Вакансия: $vacancy_name\n";
            $response_text .= "\n🎉 Ваш отклик отправлен! Мы свяжемся с вами в ближайшее время.";

            if (!empty($user_states[$chat_id]['photo_file_id'])) {
                $telegram->sendPhoto([
                    'chat_id'      => $chat_id,
                    'photo'        => $user_states[$chat_id]['photo_file_id'],
                    'caption'      => $response_text,
                    'reply_markup' => json_encode(['remove_keyboard' => true])
                ]);
            } else {
                $response_text .= "\n📸 Фото: не загружено";
                $telegram->sendMessage([
                    'chat_id'      => $chat_id,
                    'text'         => $response_text,
                    'reply_markup' => json_encode(['remove_keyboard' => true])
                ]);
            }

            self::saveToDatabase($chat_id, $user_states[$chat_id]);
            unset($user_states[$chat_id]);

            return true;
        }

        BackHandler::deleteMessage($telegram, $chat_id, $message_id);
        $telegram->sendMessage([
            'chat_id'      => $chat_id,
            'text'         => '❌ Пожалуйста, используйте кнопки для подтверждения или отмены.',
            'reply_markup' => CitiesKeyboard::getConfirmationKeyboard()
        ]);

        return false;
    }

    // ── Сохранение в БД ───────────────────────────────────────

    private static function saveToDatabase($chat_id, $data)
    {
        try {
            $db = Database::getInstance();

            $db->saveResume([
                'chat_id'        => $chat_id,
                'name'           => $data['name'],
                'age'            => $data['age'],
                'phone'          => $data['phone'],
                'photo_filename' => $data['photo_filename'] ?? null,
                'region_id'      => $data['region_id'],
                'city_id'        => $data['city_id'],
                'vacancy_id'     => $data['vacancy_id'] ?? null,  // ← вместо job_id
                'language'       => 'ru',
            ]);

        } catch (\Exception $e) {
            echo "❌ Ошибка при сохранении в базу данных: " . $e->getMessage() . "\n";
        }
    }
}