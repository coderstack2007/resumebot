<?php
namespace App\Info;

use App\Keyboards\LanguageKeyboard;
use App\Keyboards\NameKeyboard;
use App\Keyboards\CitiesKeyboard;
use App\Checking\RuCheck;
use App\Cities\RuCities;
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
        if (!RuCheck::checkUserStateExists($chat_id, $user_states)) {
            return false;
        }
        
        $user_state = $user_states[$chat_id];
        
        // Обработка кнопок "Назад"
        if (BackHandler::isBackButton($user_text)) {
            return BackHandler::handleBackButton($telegram, $chat_id, $user_text, $message_id, $user_states);
        }
        
        // Валидация текста (длина и пустое значение)
        $keyboard = self::getKeyboardForStep($user_state['step']);
        if (!RuCheck::validateAndSendError($telegram, $chat_id, $user_text, $message_id, $keyboard)) {
            return false;
        }
        
        // Обработка в зависимости от шага
        switch ($user_state['step']) {
            case 1: // Ожидаем имя
                return self::handleName($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 2: // Ожидаем возраст
                return self::handleAge($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 3: // Ожидаем выбор региона
                return self::handleRegionSelection($telegram, $chat_id, $user_text, $message_id, $user_states);
            case 4: // Ожидаем выбор города
                return self::handleCitySelection($telegram, $chat_id, $user_text, $message_id, $user_states);
        }
        
        return false;
    }
    
    /**
     * Получение клавиатуры в зависимости от шага
     */
    private static function getKeyboardForStep($step)
    {
        switch ($step) {
            case 1:
                return LanguageKeyboard::getBackKeyboard();
            case 2:
                return NameKeyboard::getBackName();
            case 3:
                return CitiesKeyboard::getRegionsKeyboard();
            default:
                return NameKeyboard::getBackName();
        }
    }
    
    /**
     * Обработка имени
     */
    private static function handleName($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        // Проверка имени
        if (!RuCheck::checkName($user_text)) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => RuCheck::getNameError(),
                'reply_markup' => LanguageKeyboard::getBackKeyboard()
            ]);
            return false;
        }
        
        // Удаляем сообщение пользователя с ФИО
        BackHandler::deleteMessage($telegram, $chat_id, $message_id);
        
        // Сохраняем имя и переходим к следующему шагу
        $user_states[$chat_id]['name'] = $user_text;
        $user_states[$chat_id]['step'] = 2; 
        
        // Запрашиваем возраст
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => RuCheck::getNameAcceptedMessage(),
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
                'text' => RuCheck::getAgeNumberError(),
                'reply_markup' => NameKeyboard::getBackName()
            ]);
            return false;
        }
        
        if (!RuCheck::checkAge($user_text)) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => RuCheck::getAgeRangeError(),
                'reply_markup' => NameKeyboard::getBackName()
            ]);
            return false;
        }

        // Удаляем сообщение пользователя с возрастом
        BackHandler::deleteMessage($telegram, $chat_id, $message_id);

        // Сохраняем возраст
        $user_states[$chat_id]['age'] = (int)$user_text;
        $user_states[$chat_id]['step'] = 3;
        
        // Запрашиваем выбор региона
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "✅ Возраст принят!\n\n📍 Выберите ваш регион:",
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
        $user_states[$chat_id]['step'] = 4;
        
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
        
        // Получаем все данные пользователя
        $name = $user_states[$chat_id]['name'];
        $age = $user_states[$chat_id]['age'];
        $region_name = RuCities::getRegionName($region_id);
        $city_name = $user_text;
        
        // Выводим итоговую информацию
        $response_text = "✅ Спасибо! Ваши данные сохранены:\n\n";
        $response_text .= "👤 ФИО: $name\n";
        $response_text .= "🎂 Возраст: $age лет\n";
        $response_text .= "📍 Регион: $region_name\n";
        $response_text .= "🏙 Город: $city_name\n";
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $response_text,
            'reply_markup' => json_encode(['remove_keyboard' => true])
        ]);
        
        // Очищаем состояние пользователя
        unset($user_states[$chat_id]);
        
        return true;
    }
}