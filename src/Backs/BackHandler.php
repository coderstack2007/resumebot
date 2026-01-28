<?php
namespace App\Backs;

use App\Info\RuInfoHandler;
use App\Keyboards\LanguageKeyboard;
use App\Keyboards\NameKeyboard;
use App\Keyboards\CitiesKeyboard;
use App\Cities\RuCities;

class BackHandler
{
    /**
     * Обработка всех callback-действий с кнопками "Назад" и "На главную"
     */
    public static function handleBackCallback($telegram, $chat_id, $message_id, $data, &$user_states)
    {
        switch ($data) {
            case 'main_menu':
                return self::handleMainMenu($telegram, $chat_id, $user_states);
                
            case 'back_to_language':
                return self::handleBackToLanguage($telegram, $chat_id, $user_states);
                
            case 'back_to_name':
                return self::handleBackToName($telegram, $chat_id, $user_states);
                
            case 'back_to_age':
                return self::handleBackToAge($telegram, $chat_id, $user_states);
                
            case 'back_to_regions':
                return self::handleBackToRegions($telegram, $chat_id, $user_states);
                
            default:
                return false;
        }
    }
    
    /**
     * Обработка кнопки "На главную"
     */
    private static function handleMainMenu($telegram, $chat_id, &$user_states)
    {
        // Сбрасываем состояние пользователя
        if (isset($user_states[$chat_id])) {
            unset($user_states[$chat_id]);
        }
        
        $text = 'Выберите язык:';
        $keyboard = LanguageKeyboard::getLanguageKeyboard();
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $text,
            'reply_markup' => $keyboard 
        ]);
        
        return true;
    }
    
    /**
     * Возврат к выбору языка
     */
    private static function handleBackToLanguage($telegram, $chat_id, &$user_states)
    {
        // Сбрасываем состояние пользователя
        if (isset($user_states[$chat_id])) {
            unset($user_states[$chat_id]);
        }
        
        $text = 'Выберите язык:';
        $keyboard = LanguageKeyboard::getLanguageKeyboard();
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $text,
            'reply_markup' => $keyboard 
        ]);
        
        return true;
    }
    
    /**
     * Обработка кнопки "Назад" на шаге возраста (возврат к вводу имени)
     */
    public static function handleBackToName($telegram, $chat_id, &$user_states)
    {
        if (!isset($user_states[$chat_id])) {
            return false;
        }
        
        // Возвращаемся к шагу ввода имени
        $user_states[$chat_id]['step'] = 1;
        unset($user_states[$chat_id]['name']);
        unset($user_states[$chat_id]['age']);
        unset($user_states[$chat_id]['region_id']);
        unset($user_states[$chat_id]['city_id']);
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "Пожалуйста, введите ваше ФИО:",
            'reply_markup' => LanguageKeyboard::getBackKeyboard()
        ]);
        
        return true;
    }
    
    /**
     * Обработка кнопки "Назад к регионам"
     */
    public static function handleBackToRegions($telegram, $chat_id, &$user_states)
    {
        if (!isset($user_states[$chat_id])) {
            return false;
        }
        
        $user_states[$chat_id]['step'] = 3;
        unset($user_states[$chat_id]['region_id']);
        unset($user_states[$chat_id]['city_id']);
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "📍 Выберите ваш регион:",
            'reply_markup' => CitiesKeyboard::getRegionsKeyboard()
        ]);
        
        return true;
    }
    
    /**
     * Обработка кнопки "Назад к возрасту"
     */
    public static function handleBackToAge($telegram, $chat_id, &$user_states)
    {
        if (!isset($user_states[$chat_id])) {
            return false;
        }
        
        $user_states[$chat_id]['step'] = 2;
        unset($user_states[$chat_id]['age']);
        unset($user_states[$chat_id]['region_id']);
        unset($user_states[$chat_id]['city_id']);
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "🎂 Теперь введите ваш возраст (15-60 лет):",
            'reply_markup' => NameKeyboard::getBackName()
        ]);
        
        return true;
    }
}