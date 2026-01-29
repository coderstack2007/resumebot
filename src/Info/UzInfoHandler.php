<?php
namespace App\Info;

use App\Keyboards\uz\LanguageKeyboard;
use App\Keyboards\uz\NameKeyboard;
use App\Keyboards\uz\CitiesKeyboard;
use App\Keyboards\uz\JobsKeyboard;
use App\Checking\uz\Check;
use App\Cities\uz\Cities;
use App\Jobs\Uz\Jobs;
use App\Backs\uz\BackHandler;

class UzInfoHandler
{
    /**
     * Обработка ввода данных пользователя на узбекском языке
     */
    public static function getStartMessage()
    {
        return "✅ Til tanlandi: O'zbekcha\n\nIltimos, FIOingizni kiriting:";
    }
    
    public static function handleUserInput($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        // Проверка существования состояния пользователя
        if (!Check::checkUserStateExists($chat_id, $user_states)) {
            return false;
        }
        
        $user_state = $user_states[$chat_id];
        
        // Обработка кнопок "Назад"
        if (BackHandler::isBackButton($user_text)) {
            return BackHandler::handleBackButton($telegram, $chat_id, $user_text, $message_id, $user_states);
        }
        
        // Валидация текста (длина и пустое значение)
        $keyboard = self::getKeyboardForStep($user_state['step'], $user_state);
        if (!Check::validateAndSendError($telegram, $chat_id, $user_text, $message_id, $keyboard)) {
            return false;
        }
        
        // Обработка в зависимости от шага
        switch ($user_state['step']) {
            case 1: // Ожидаем имя
                return self::handleName($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 2: // Ожидаем возраст
                return self::handleAge($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 3: // Ожидаем телефонный номер
                return self::handlePhone($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 4: // Ожидаем фото - если пришел текст, выводим ошибку
                BackHandler::deleteMessage($telegram, $chat_id, $message_id);
                $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => Check::getImageRequiredError(),
                    'reply_markup' => NameKeyboard::getBackName()
                ]);
                return false;
            case 5: // Ожидаем выбор региона
                return self::handleRegionSelection($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 6: // Ожидаем выбор города
                return self::handleCitySelection($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 7: // Ожидаем выбор вакансии
                return self::handleJobSelection($telegram, $chat_id, $user_text, $message_id, $user_states);
        }
        
        return false;
    }
    
    /**
     * Обработка фото от пользователя
     */
    public static function handlePhoto($telegram, $chat_id, $photo_array, $message_id, &$user_states)
    {
        // Проверка существования состояния пользователя
        if (!Check::checkUserStateExists($chat_id, $user_states)) {
            return false;
        }
        
        $user_state = $user_states[$chat_id];
        
        // Проверяем, что мы ожидаем фото (шаг 4)
        if ($user_state['step'] != 4) {
            return false;
        }
        
        // Получаем самое большое фото (лучшее качество)
        $photo = end($photo_array);
        $file_id = $photo['file_id'];
        $file_size = $photo['file_size'] ?? 0;
        
        // Проверка размера файла
        if (!Check::checkImageSize($file_size)) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => Check::getImageSizeError(),
                'reply_markup' => NameKeyboard::getBackName()
            ]);
            return false;
        }
        
        // Получаем информацию о файле
        try {
            $file_info = $telegram->getFile(['file_id' => $file_id]);
            $file_path = $file_info['file_path'];
            
            // Получаем расширение файла
            $extension = pathinfo($file_path, PATHINFO_EXTENSION);
            
            // Проверка формата (дополнительная проверка по расширению)
            if (!in_array(strtolower($extension), ['jpg', 'jpeg', 'png'])) {
                BackHandler::deleteMessage($telegram, $chat_id, $message_id);
                
                $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => Check::getImageFormatError(),
                    'reply_markup' => NameKeyboard::getBackName()
                ]);
                return false;
            }
            
            // Скачиваем файл
            $file_url = "https://api.telegram.org/file/bot" . \App\BotSettings::TOKEN . "/$file_path";
            $file_content = file_get_contents($file_url);
            
            if ($file_content === false) {
                throw new \Exception("Не удалось скачать файл");
            }
            
            // Создаем директорию, если её нет
            $images_dir = dirname(__DIR__, 2) . '/src/images';
            if (!file_exists($images_dir)) {
                mkdir($images_dir, 0777, true);
            }
            
            // Генерируем уникальное имя файла: chat_id_timestamp.extension
            $filename = $chat_id . '_' . time() . '.' . $extension;
            $save_path = $images_dir . '/' . $filename;
            
            // Сохраняем файл
            if (file_put_contents($save_path, $file_content) === false) {
                throw new \Exception("Не удалось сохранить файл");
            }
            
            // Удаляем сообщение пользователя с фото
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            // Сохраняем имя файла в состоянии
            $user_states[$chat_id]['photo_filename'] = $filename;
            $user_states[$chat_id]['step'] = 5;
            
            // Запрашиваем выбор региона
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => Check::getPhotoAcceptedMessage() . "\n\n📍 Hududingizni tanlang:",
                'reply_markup' => CitiesKeyboard::getRegionsKeyboard()
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "❌ Rasmni saqlashda xatolik yuz berdi: " . $e->getMessage() . "\n\nYana bir marta urinib ko'ring:",
                'reply_markup' => NameKeyboard::getBackName()
            ]);
            
            echo "❌ Ошибка при сохранении фото: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Получение клавиатуры в зависимости от шага
     */
    private static function getKeyboardForStep($step, $user_state = [])
    {
        switch ($step) {
            case 1:
                return LanguageKeyboard::getBackKeyboard();
            case 2:
                return NameKeyboard::getBackName();
            case 3:
                return NameKeyboard::getBackName();
            case 4:
                return NameKeyboard::getBackName();
            case 5:
                return CitiesKeyboard::getRegionsKeyboard();
            case 6:
                $region_id = $user_state['region_id'] ?? 1;
                return CitiesKeyboard::getCitiesKeyboard($region_id);
            case 7:
                return JobsKeyboard::getJobsKeyboard();
            default:
                return NameKeyboard::getBackName();
        }
    }
    
    /**
     * Обработка имени
     */
    private static function handleName($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        // Удаляем сообщение пользователя с ФИО
        BackHandler::deleteMessage($telegram, $chat_id, $message_id);
        
        // Сохраняем имя и переходим к следующему шагу
        $user_states[$chat_id]['name'] = $user_text;
        $user_states[$chat_id]['step'] = 2; 
        
        // Запрашиваем возраст
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => Check::getNameAcceptedMessage(),
            'reply_markup' => NameKeyboard::getBackName()
        ]);
        
        return true;
    }

    /**
     * Обработка возраста
     */
    private static function handleAge($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        // Проверка возраста
        if (!is_numeric($user_text)) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => Check::getAgeNumberError(),
                'reply_markup' => NameKeyboard::getBackName()
            ]);
            return false;
        }
        
        if (!Check::checkAge($user_text)) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => Check::getAgeRangeError(),
                'reply_markup' => NameKeyboard::getBackName()
            ]);
            return false;
        }

        // Удаляем сообщение пользователя с возрастом
        BackHandler::deleteMessage($telegram, $chat_id, $message_id);

        // Сохраняем возраст
        $user_states[$chat_id]['age'] = (int)$user_text;
        $user_states[$chat_id]['step'] = 3;
        
        // Запрашиваем телефонный номер
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => Check::getAgeAcceptedMessage(),
            'reply_markup' => NameKeyboard::getBackName()
        ]);
        
        return true;
    }
    
    /**
     * Обработка телефонного номера
     */
    private static function handlePhone($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        // Проверка формата телефонного номера
        if (!Check::checkPhoneNumber($user_text)) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => Check::getPhoneError(),
                'reply_markup' => NameKeyboard::getBackName()
            ]);
            return false;
        }

        // Удаляем сообщение пользователя с телефоном
        BackHandler::deleteMessage($telegram, $chat_id, $message_id);

        // Очищаем номер телефона от лишних символов для сохранения
        $cleanPhone = preg_replace('/[\s\(\)\-]/', '', $user_text);
        
        // Сохраняем телефонный номер
        $user_states[$chat_id]['phone'] = $cleanPhone;
        $user_states[$chat_id]['step'] = 4;
        
        // Запрашиваем фото
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => Check::getPhoneAcceptedMessage() . "\n\n" . Check::getPhotoRequestMessage(),
            'reply_markup' => NameKeyboard::getBackName()
        ]);
        
        return true;
    }
    
    /**
     * Обработка выбора региона (текстовое сообщение)
     */
    private static function handleRegionSelection($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        // Ищем регион по названию
        $regions = Cities::getRegions();
        $region_id = array_search($user_text, $regions);
        
        if ($region_id === false) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => '❌ Xatolik: hudud topilmadi. Iltimos, tugmalardan foydalaning.',
                'reply_markup' => CitiesKeyboard::getRegionsKeyboard()
            ]);
            return false;
        }
        
        // Удаляем сообщение пользователя
        BackHandler::deleteMessage($telegram, $chat_id, $message_id);
        
        // Сохраняем выбранный регион
        $user_states[$chat_id]['region_id'] = $region_id;
        $user_states[$chat_id]['step'] = 6;
        
        // Показываем города выбранного региона
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "✅ Hudud tanlandi: $user_text\n\n🏙 Shaharingizni tanlang:",
            'reply_markup' => CitiesKeyboard::getCitiesKeyboard($region_id)
        ]);
        
        return true;
    }
    
    /**
     * Обработка выбора города (текстовое сообщение)
     */
    private static function handleCitySelection($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        $region_id = $user_states[$chat_id]['region_id'];
        
        // Ищем город по названию
        $cities = Cities::getCitiesByRegion($region_id);
        $city_id = array_search($user_text, $cities);
        
        if ($city_id === false) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => '❌ Xatolik: shahar topilmadi. Iltimos, tugmalardan foydalaning.',
                'reply_markup' => CitiesKeyboard::getCitiesKeyboard($region_id)
            ]);
            return false;
        }
        
        // Удаляем сообщение пользователя
        BackHandler::deleteMessage($telegram, $chat_id, $message_id);
        
        // Сохраняем выбранный город
        $user_states[$chat_id]['city_id'] = $city_id;
        $user_states[$chat_id]['step'] = 7;  // Переходим к выбору вакансии
        
        // Показываем вакансии
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "✅ Shahar tanlandi: $user_text\n\n💼 Qaysi vakansiyaga murojaat qilmoqchisiz?",
            'reply_markup' => JobsKeyboard::getJobsKeyboard()
        ]);
        
        return true;
    }
    
    /**
     * Обработка выбора вакансии (текстовое сообщение)
     */
    private static function handleJobSelection($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        // Ищем вакансию по названию
        $jobs = Jobs::getJobs();
        $job_id = array_search($user_text, $jobs);
        
        if ($job_id === false) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => '❌ Xatolik: vakansiya topilmadi. Iltimos, tugmalardan foydalaning.',
                'reply_markup' => JobsKeyboard::getJobsKeyboard()
            ]);
            return false;
        }
        
        // Удаляем сообщение пользователя
        BackHandler::deleteMessage($telegram, $chat_id, $message_id);
        
        // Сохраняем выбранную вакансию
        $user_states[$chat_id]['job_id'] = $job_id;
        
        // Получаем все данные пользователя
        $name = $user_states[$chat_id]['name'];
        $age = $user_states[$chat_id]['age'];
        $phone = $user_states[$chat_id]['phone'];
        $photo_filename = $user_states[$chat_id]['photo_filename'] ?? 'ko\'rsatilmagan';
        $region_id = $user_states[$chat_id]['region_id'];
        $city_id = $user_states[$chat_id]['city_id'];
        
        $region_name = Cities::getRegionName($region_id);
        $city_name = Cities::getCityName($region_id, $city_id);
        $job_name = $user_text;
        
        // Выводим итоговую информацию
        $response_text = "✅ Rahmat! Ma'lumotlaringiz saqlandi:\n\n";
        $response_text .= "👤 FIO: $name\n";
        $response_text .= "🎂 Yosh: $age yosh\n";
        $response_text .= "📱 Telefon: $phone\n";
        $response_text .= "📸 Rasm: $photo_filename\n";
        $response_text .= "📍 Hudud: $region_name\n";
        $response_text .= "🏙 Shahar: $city_name\n";
        $response_text .= "💼 Vakansiya: $job_name\n";
        $response_text .= "\n🎉 Murojaatingiz yuborildi! Tez orada siz bilan bog'lanamiz.";
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $response_text,
            'reply_markup' => json_encode(['remove_keyboard' => true])
        ]);
        
        // Здесь можно добавить сохранение в базу данных
        // self::saveToDatabase($user_states[$chat_id]);
        
        // Очищаем состояние пользователя
        unset($user_states[$chat_id]);
        
        return true;
    }
}