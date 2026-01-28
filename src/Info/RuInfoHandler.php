<?php
namespace App\Info;

use App\Keyboards\ru\LanguageKeyboard;
use App\Keyboards\ru\NameKeyboard;
use App\Keyboards\ru\CitiesKeyboard;
use App\Keyboards\ru\JobsKeyboard;
use App\Checking\ru\Check;
use App\Cities\RuCities;
use App\Jobs\Ru\Jobs;
use App\Backs\BackHandler;

class RuInfoHandler
{
    /**
     * Обработка ввода данных пользователя на русском языке
     */
    public static function getStartMessage()
    {
        return "✅ Язык выбран: Русский\n\nПожалуйста, введите ваше ФИО:";
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
            case 4: // Ожидаем выбор региона
                return self::handleRegionSelection($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 5: // Ожидаем выбор города
                return self::handleCitySelection($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 6: // Ожидаем выбор вакансии
                return self::handleJobSelection($telegram, $chat_id, $user_text, $message_id, $user_states);
        }
        
        return false;
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
                return CitiesKeyboard::getRegionsKeyboard();
            case 5:
                $region_id = $user_state['region_id'] ?? 1;
                return CitiesKeyboard::getCitiesKeyboard($region_id);
            case 6:
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
        
        // Запрашиваем выбор региона
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => Check::getPhoneAcceptedMessage() . "\n\n📍 Выберите ваш регион:",
            'reply_markup' => CitiesKeyboard::getRegionsKeyboard()
        ]);
        
        return true;
    }
    
    /**
     * Обработка выбора региона (текстовое сообщение)
     */
    private static function handleRegionSelection($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        // Ищем регион по названию
        $regions = RuCities::getRegions();
        $region_id = array_search($user_text, $regions);
        
        if ($region_id === false) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => '❌ Ошибка: регион не найден. Пожалуйста, используйте кнопки.',
                'reply_markup' => CitiesKeyboard::getRegionsKeyboard()
            ]);
            return false;
        }
        
        // Удаляем сообщение пользователя
        BackHandler::deleteMessage($telegram, $chat_id, $message_id);
        
        // Сохраняем выбранный регион
        $user_states[$chat_id]['region_id'] = $region_id;
        $user_states[$chat_id]['step'] = 5;
        
        // Показываем города выбранного региона
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "✅ Регион выбран: $user_text\n\n🏙 Выберите ваш город:",
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
        $cities = RuCities::getCitiesByRegion($region_id);
        $city_id = array_search($user_text, $cities);
        
        if ($city_id === false) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => '❌ Ошибка: город не найден. Пожалуйста, используйте кнопки.',
                'reply_markup' => CitiesKeyboard::getCitiesKeyboard($region_id)
            ]);
            return false;
        }
        
        // Удаляем сообщение пользователя
        BackHandler::deleteMessage($telegram, $chat_id, $message_id);
        
        // Сохраняем выбранный город
        $user_states[$chat_id]['city_id'] = $city_id;
        $user_states[$chat_id]['step'] = 6;  // Переходим к выбору вакансии
        
        // Показываем вакансии
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "✅ Город выбран: $user_text\n\n💼 Выберите вакансию, на которую хотите откликнуться:",
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
                'text' => '❌ Ошибка: вакансия не найдена. Пожалуйста, используйте кнопки.',
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
        $region_id = $user_states[$chat_id]['region_id'];
        $city_id = $user_states[$chat_id]['city_id'];
        
        $region_name = RuCities::getRegionName($region_id);
        $city_name = RuCities::getCityName($region_id, $city_id);
        $job_name = $user_text;
        
        // Выводим итоговую информацию
        $response_text = "✅ Спасибо! Ваши данные сохранены:\n\n";
        $response_text .= "👤 ФИО: $name\n";
        $response_text .= "🎂 Возраст: $age лет\n";
        $response_text .= "📱 Телефон: $phone\n";
        $response_text .= "📍 Регион: $region_name\n";
        $response_text .= "🏙 Город: $city_name\n";
        $response_text .= "💼 Вакансия: $job_name\n";
        $response_text .= "\n🎉 Ваш отклик отправлен! Мы свяжемся с вами в ближайшее время.";
        
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