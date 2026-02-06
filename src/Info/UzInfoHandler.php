<?php
namespace App\Info;

use App\Keyboards\uz\LanguageKeyboard;
use App\Keyboards\uz\NameKeyboard;
use App\Keyboards\uz\CitiesKeyboard;
use App\Keyboards\uz\JobsKeyboard;
use App\Keyboards\uz\NumberKeyboard;
use App\Checking\uz\Check;
use App\Cities\uz\Cities;
use App\Jobs\uz\Jobs;
use App\Backs\uz\BackHandler;
use App\Database;

class UzInfoHandler
{
    public static function getStartMessage()
    {
        return "✅ Til tanlandi: O'zbekcha\n\nIltimos, FIOingizni kiriting:";
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
                    'chat_id' => $chat_id,
                    'text' => Check::getImageRequiredError(),
                    'reply_markup' => NameKeyboard::getBackName()
                ]);
                return false;
            case 5:
                return self::handleRegionSelection($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 6:
                return self::handleCitySelection($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 7:
                return self::handleJobSelection($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 8:
                return self::handleConfirmation($telegram, $chat_id, $user_text, $message_id, $user_states);
        }
        
        return false;
    }

    /**
     * Обработка contact (скинутый номер через кнопку "Nomeringiz berishlar")
     */
    public static function handleContact($telegram, $chat_id, $contact, $message_id, &$user_states)
    {
        if (!Check::checkUserStateExists($chat_id, $user_states)) {
            return false;
        }

        $user_state = $user_states[$chat_id];

        // contact допустим только на шаге 3 (ввод телефона)
        if ($user_state['step'] !== 3) {
            return false;
        }

        $phone = $contact['phone_number'] ?? null;

        if (!$phone) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text'    => "❌ Nomer olish mumkin bolmadi. Qo'l bilan kiriting yoki qayta urinib ko'ring:",
                'reply_markup' => NumberKeyboard::getPhoneKeyboard()
            ]);
            return false;
        }

        // Нормализуем: убираем пробелы, скобки, дефисы
        $cleanPhone = preg_replace('/[\s\(\)\-]/', '', $phone);

        BackHandler::deleteMessage($telegram, $chat_id, $message_id);

        $user_states[$chat_id]['phone'] = $cleanPhone;
        $user_states[$chat_id]['step']  = 4;

        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text'    => Check::getPhoneAcceptedMessage() . "\n\n" . Check::getPhotoRequestMessage(),
            'reply_markup' => NameKeyboard::getBackName()
        ]);

        return true;
    }
    
    public static function handlePhoto($telegram, $chat_id, $photo_array, $message_id, &$user_states)
    {
        if (!Check::checkUserStateExists($chat_id, $user_states)) {
            return false;
        }
        
        $user_state = $user_states[$chat_id];
        
        if ($user_state['step'] != 4) {
            return false;
        }
        
        $photo = end($photo_array);
        $file_id = $photo['file_id'];
        $file_size = $photo['file_size'] ?? 0;
        
        if (!Check::checkImageSize($file_size)) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => Check::getImageSizeError(),
                'reply_markup' => NameKeyboard::getBackName()
            ]);
            return false;
        }
        
        try {
            $file_info = $telegram->getFile(['file_id' => $file_id]);
            $file_path = $file_info['file_path'];
            $extension = pathinfo($file_path, PATHINFO_EXTENSION);
            
            if (!in_array(strtolower($extension), ['jpg', 'jpeg', 'png'])) {
                BackHandler::deleteMessage($telegram, $chat_id, $message_id);
                $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => Check::getImageFormatError(),
                    'reply_markup' => NameKeyboard::getBackName()
                ]);
                return false;
            }
            
            $file_url = "https://api.telegram.org/file/bot" . \App\BotSettings::TOKEN . "/$file_path";
            $ch = curl_init($file_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // На случай проблем с SSL
            $file_content = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            
            if ($file_content === false) {
                throw new \Exception("Не удалось скачать файл");
            }
            
            $images_dir = dirname(__DIR__, 2) . '/src/images';
            if (!file_exists($images_dir)) {
                mkdir($images_dir, 0777, true);
            }
            
            $filename = $chat_id . '_' . time() . '.' . $extension;
            $save_path = $images_dir . '/' . $filename;
            
            if (file_put_contents($save_path, $file_content) === false) {
                throw new \Exception("Не удалось сохранить файл");
            }
            
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $user_states[$chat_id]['photo_filename'] = $filename;
            $user_states[$chat_id]['step'] = 5;
            
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
    
    private static function getKeyboardForStep($step, $user_state = [])
    {
        switch ($step) {
            case 1:
                return LanguageKeyboard::getBackKeyboard();
            case 2:
                return NameKeyboard::getBackName();
            case 3:
                // Шаг 3 — кнопка "Nomeringiz berishlar" + назад
                return NumberKeyboard::getPhoneKeyboard();
            case 4:
                return NameKeyboard::getBackName();
            case 5:
                return CitiesKeyboard::getRegionsKeyboard();
            case 6:
                $region_id = $user_state['region_id'] ?? 1;
                return CitiesKeyboard::getCitiesKeyboard($region_id);
            case 7:
                return JobsKeyboard::getJobsKeyboard();
            case 8:
                return JobsKeyboard::getConfirmationKeyboard();
            default:
                return NameKeyboard::getBackName();
        }
    }
    
    private static function handleName($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        BackHandler::deleteMessage($telegram, $chat_id, $message_id);
        
        $user_states[$chat_id]['name'] = $user_text;
        $user_states[$chat_id]['step'] = 2;
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => Check::getNameAcceptedMessage(),
            'reply_markup' => NameKeyboard::getBackName()
        ]);
        
        return true;
    }

    private static function handleAge($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
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

        BackHandler::deleteMessage($telegram, $chat_id, $message_id);

        $user_states[$chat_id]['age'] = (int)$user_text;
        $user_states[$chat_id]['step'] = 3;
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => Check::getAgeAcceptedMessage(),
            'reply_markup' => NumberKeyboard::getPhoneKeyboard()
        ]);
        
        return true;
    }
    
    private static function handlePhone($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        if (!Check::checkPhoneNumber($user_text)) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => Check::getPhoneError(),
                'reply_markup' => NumberKeyboard::getPhoneKeyboard()
            ]);
            return false;
        }

        BackHandler::deleteMessage($telegram, $chat_id, $message_id);

        $cleanPhone = preg_replace('/[\s\(\)\-]/', '', $user_text);
        
        $user_states[$chat_id]['phone'] = $cleanPhone;
        $user_states[$chat_id]['step'] = 4;
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => Check::getPhoneAcceptedMessage() . "\n\n" . Check::getPhotoRequestMessage(),
            'reply_markup' => NameKeyboard::getBackName()
        ]);
        
        return true;
    }
    
    private static function handleRegionSelection($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
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
        
        BackHandler::deleteMessage($telegram, $chat_id, $message_id);
        
        $user_states[$chat_id]['region_id'] = $region_id;
        $user_states[$chat_id]['step'] = 6;
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "✅ Hudud tanlandi: $user_text\n\n🏙 Shaharingizni tanlang:",
            'reply_markup' => CitiesKeyboard::getCitiesKeyboard($region_id)
        ]);
        
        return true;
    }
    
    private static function handleCitySelection($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        $region_id = $user_states[$chat_id]['region_id'];
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
        
        BackHandler::deleteMessage($telegram, $chat_id, $message_id);
        
        $user_states[$chat_id]['city_id'] = $city_id;
        $user_states[$chat_id]['step'] = 7;
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "✅ Shahar tanlandi: $user_text\n\n💼 Qaysi vakansiyaga murojaat qilmoqchisiz?",
            'reply_markup' => JobsKeyboard::getJobsKeyboard()
        ]);
        
        return true;
    }
    
    private static function handleJobSelection($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
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
        
        BackHandler::deleteMessage($telegram, $chat_id, $message_id);
        
        $user_states[$chat_id]['job_id'] = $job_id;
        $user_states[$chat_id]['step'] = 8;
        
        $region_name = Cities::getRegionName($user_states[$chat_id]['region_id']);
        $city_name   = Cities::getCityName($user_states[$chat_id]['region_id'], $user_states[$chat_id]['city_id']);
        
        $response_text = "📋 Kiritilgan ma'lumotlar to'g'riligini tekshiring:\n\n";
        $response_text .= "👤 FIO: " . $user_states[$chat_id]['name'] . "\n";
        $response_text .= "🎂 Yosh: " . $user_states[$chat_id]['age'] . " yosh\n";
        $response_text .= "📱 Telefon: " . $user_states[$chat_id]['phone'] . "\n";
        $response_text .= "📸 Rasm: " . ($user_states[$chat_id]['photo_filename'] ?? "ko'rsatilmagan") . "\n";
        $response_text .= "📍 Hudud: $region_name\n";
        $response_text .= "🏙 Shahar: $city_name\n";
        $response_text .= "💼 Vakansiya: $user_text\n";
        $response_text .= "\n❓ Barcha ma'lumotlar to'g'rimi?";
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $response_text,
            'reply_markup' => JobsKeyboard::getConfirmationKeyboard()
        ]);
        
        return true;
    }
    
    private static function handleConfirmation($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        if (JobsKeyboard::isConfirmButton($user_text)) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $region_name = Cities::getRegionName($user_states[$chat_id]['region_id']);
            $city_name   = Cities::getCityName($user_states[$chat_id]['region_id'], $user_states[$chat_id]['city_id']);
            $job_name    = Jobs::getJobName($user_states[$chat_id]['job_id']);
            
            $response_text = "✅ Rahmat! Ma'lumotlaringiz saqlandi:\n\n";
            $response_text .= "👤 FIO: " . $user_states[$chat_id]['name'] . "\n";
            $response_text .= "🎂 Yosh: " . $user_states[$chat_id]['age'] . " yosh\n";
            $response_text .= "📱 Telefon: " . $user_states[$chat_id]['phone'] . "\n";
            $response_text .= "📸 Rasm: " . ($user_states[$chat_id]['photo_filename'] ?? "ko'rsatilmagan") . "\n";
            $response_text .= "📍 Hudud: $region_name\n";
            $response_text .= "🏙 Shahar: $city_name\n";
            $response_text .= "💼 Vakansiya: $job_name\n";
            $response_text .= "\n🎉 Murojaatingiz yuborildi! Tez orada siz bilan bog'lanamiz.";
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => $response_text,
                'reply_markup' => json_encode(['remove_keyboard' => true])
            ]);
            
            self::saveToDatabase($chat_id, $user_states[$chat_id]);
            
            unset($user_states[$chat_id]);
            
            return true;
        } 
        elseif ($user_text === '⬅️ Orqaga') {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $user_states[$chat_id]['step'] = 7;
            unset($user_states[$chat_id]['job_id']);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "💼 Qaysi vakansiyaga murojaat qilmoqchisiz?",
                'reply_markup' => JobsKeyboard::getJobsKeyboard()
            ]);
            
            return true;
        }
        
        BackHandler::deleteMessage($telegram, $chat_id, $message_id);
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => '❌ Iltimos, javob berish uchun tugmalardan foydalaning.',
            'reply_markup' => JobsKeyboard::getConfirmationKeyboard()
        ]);
        
        return false;
    }
    
    private static function saveToDatabase($chat_id, $user_data)
    {
        try {
            $db = Database::getInstance();
            
            $data = [
                'chat_id'        => $chat_id,
                'name'           => $user_data['name'],
                'age'            => $user_data['age'],
                'phone'          => $user_data['phone'],
                'photo_filename' => $user_data['photo_filename'] ?? null,
                'region_id'      => $user_data['region_id'],
                'city_id'        => $user_data['city_id'],
                'job_id'         => $user_data['job_id'],
                'language'       => 'uz',
            ];
            
            $resume_id = $db->saveResume($data);
            
            if ($resume_id) {
                echo "✅ Резюме пользователя $chat_id успешно сохранено в БД (ID: $resume_id)\n";
            }
        } catch (\Exception $e) {
            echo "❌ Ошибка при сохранении в БД: " . $e->getMessage() . "\n";
        }
    }
}