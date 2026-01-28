<?php
namespace App\Info;

use App\Keyboards\LanguageKeyboard;
use App\Keyboards\NameKeyboard;
use App\Keyboards\CitiesKeyboard;
use App\Checking\ruCheck;
use App\Cities\RuCities;

class RuInfoHandler
{
    /**
     * Обработка ввода данных пользователя на русском языке
     */
    public static function getStartMessage()
    {
        return "✅ Язык выбран: Русский\n\nПожалуйста, введите ваше ФИО:";
    }
    
    public static function handleUserInput($telegram, $chat_id, $user_text, &$user_states)
    {
        // Проверка, существует ли состояние пользователя
        if (!isset($user_states[$chat_id])) {
            return false;
        }
        
        $user_state = $user_states[$chat_id];
        
        // Проверка на максимальную длину (50 символов)
        if (!ruCheck::checkMaxLength($user_text)) {
            $keyboard = self::getKeyboardForStep($user_state['step']);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => ruCheck::getMaxLengthError(),
                'reply_markup' => $keyboard
            ]);
            return false;
        }
        
        // Проверка на пустое значение
        if (!ruCheck::checkNotEmpty($user_text)) {
            $keyboard = self::getKeyboardForStep($user_state['step']);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => ruCheck::getNotEmptyError(),
                'reply_markup' => $keyboard
            ]);
            return false;
        }
        
        switch ($user_state['step']) {
            case 1: // Ожидаем имя
                return self::handleName($telegram, $chat_id, $user_text, $user_states);
            case 2: // Ожидаем возраст
                return self::handleAge($telegram, $chat_id, $user_text, $user_states);
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
    private static function handleName($telegram, $chat_id, $user_text, &$user_states)
    {
        // Проверка имени
        if (!ruCheck::checkName($user_text)) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => ruCheck::getNameError(),
                'reply_markup' => LanguageKeyboard::getBackKeyboard()
            ]);
            return false;
        }
        
        // Сохраняем имя и переходим к следующему шагу
        $user_states[$chat_id]['name'] = $user_text;
        $user_states[$chat_id]['step'] = 2; 
        
        // Запрашиваем возраст с NameKeyboard
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => ruCheck::getNameAcceptedMessage(),
            'reply_markup' => NameKeyboard::getBackName()
        ]);
        
        return true;
    }

    /**
     * Обработка возраста
     */
    private static function handleAge($telegram, $chat_id, $user_text, &$user_states)
    {
        // Проверка возраста
        if (!is_numeric($user_text)) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => ruCheck::getAgeNumberError(),
                'reply_markup' => NameKeyboard::getBackName()
            ]);
            return false;
        }
        
        if (!ruCheck::checkAge($user_text)) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => ruCheck::getAgeRangeError(),
                'reply_markup' => NameKeyboard::getBackName()
            ]);
            return false;
        }

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
     * Обработка выбора региона (callback)
     */
    public static function handleRegionCallback($telegram, $chat_id, $callback_data, &$user_states)
    {
        // Извлекаем ID региона из callback_data (формат: region_1)
        $region_id = (int)str_replace('region_', '', $callback_data);
        
        // Проверяем существует ли регион
        if (!RuCities::regionExists($region_id)) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => '❌ Ошибка: регион не найден',
                'reply_markup' => CitiesKeyboard::getRegionsKeyboard()
            ]);
            return false;
        }
        
        // Сохраняем выбранный регион
        $user_states[$chat_id]['region_id'] = $region_id;
        $user_states[$chat_id]['step'] = 4;
        
        $region_name = RuCities::getRegionName($region_id);
        
        // Показываем города выбранного региона
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "✅ Регион выбран: $region_name\n\n🏙 Выберите ваш город:",
            'reply_markup' => CitiesKeyboard::getCitiesKeyboard($region_id)
        ]);
        
        return true;
    }
    
    /**
     * Обработка выбора города (callback)
     */
    public static function handleCityCallback($telegram, $chat_id, $callback_data, &$user_states)
    {
        // Извлекаем ID региона и города из callback_data (формат: city_1_101)
        $parts = explode('_', $callback_data);
        $region_id = (int)$parts[1];
        $city_id = (int)$parts[2];
        
        // Проверяем существует ли город
        if (!RuCities::cityExists($region_id, $city_id)) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => '❌ Ошибка: город не найден',
                'reply_markup' => CitiesKeyboard::getCitiesKeyboard($region_id)
            ]);
            return false;
        }
        
        // Сохраняем выбранный город
        $user_states[$chat_id]['city_id'] = $city_id;
        
        // Получаем все данные пользователя
        $name = $user_states[$chat_id]['name'];
        $age = $user_states[$chat_id]['age'];
        $region_name = RuCities::getRegionName($region_id);
        $city_name = RuCities::getCityName($region_id, $city_id);
        
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