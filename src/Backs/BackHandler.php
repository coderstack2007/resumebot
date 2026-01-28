<?php
namespace App\Backs;

use App\Keyboards\ru\LanguageKeyboard;
use App\Keyboards\ru\NameKeyboard;
use App\Keyboards\ru\CitiesKeyboard;
use App\Keyboards\ru\JobsKeyboard;
use App\Checking\ru\Check;

class BackHandler
{
    /**
     * Проверка, является ли текст кнопкой "Назад"
     */
    public static function isBackButton($user_text): bool
    {
        return in_array($user_text, [
            '⬅️ Назад к выбору языка',
            '⬅️ Назад',
            '⬅️ Назад к регионам',
            '⬅️ Назад к городам'
        ]);
    }
    
    /**
     * Обработка всех кнопок "Назад"
     */
    public static function handleBackButton($telegram, $chat_id, $user_text, $message_id, &$user_states)
    {
        // Удаляем сообщение пользователя
        self::deleteMessage($telegram, $chat_id, $message_id);
        
        switch ($user_text) {
            case '⬅️ Назад к выбору языка':
                return self::handleBackToLanguage($telegram, $chat_id, $user_states);
                
            case '⬅️ Назад':
                return self::handleBack($telegram, $chat_id, $user_states);
                
            case '⬅️ Назад к регионам':
                return self::handleBackToRegions($telegram, $chat_id, $user_states);
                
            case '⬅️ Назад к городам':
                return self::handleBackToCities($telegram, $chat_id, $user_states);
                
            default:
                return false;
        }
    }
    
    /**
     * Обработка кнопки "Назад к выбору языка"
     */
    private static function handleBackToLanguage($telegram, $chat_id, &$user_states)
    {
        // Возвращаемся к выбору языка
        $user_states[$chat_id] = [
            'state' => 'choosing_language'
        ];
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "Выберите язык:",
            'reply_markup' => LanguageKeyboard::getLanguageKeyboard()
        ]);
        
        return true;
    }
    
    /**
     * Обработка кнопки "Назад" (универсальная)
     */
    private static function handleBack($telegram, $chat_id, &$user_states)
    {
        if (!isset($user_states[$chat_id])) {
            return false;
        }
        
        $step = $user_states[$chat_id]['step'];
        
        if ($step == 2) {
            // Возврат к вводу имени
            $user_states[$chat_id]['step'] = 1;
            unset($user_states[$chat_id]['name']);
            unset($user_states[$chat_id]['age']);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "Пожалуйста, введите ваше ФИО:",
                'reply_markup' => LanguageKeyboard::getBackKeyboard()
            ]);
        } elseif ($step == 3) {
            // Возврат к вводу возраста
            $user_states[$chat_id]['step'] = 2;
            unset($user_states[$chat_id]['phone']);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "🎂 Теперь введите ваш возраст (15-60 лет):",
                'reply_markup' => NameKeyboard::getBackName()
            ]);
        } elseif ($step == 4) {
            // Возврат к вводу телефона
            $user_states[$chat_id]['step'] = 3;
            unset($user_states[$chat_id]['region_id']);
            unset($user_states[$chat_id]['city_id']);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => Check::getAgeAcceptedMessage(),
                'reply_markup' => NameKeyboard::getBackName()
            ]);
        }
        
        return true;
    }
    
    /**
     * Обработка кнопки "Назад к регионам"
     */
    private static function handleBackToRegions($telegram, $chat_id, &$user_states)
    {
        if (!isset($user_states[$chat_id])) {
            return false;
        }
        
        $user_states[$chat_id]['step'] = 4;
        unset($user_states[$chat_id]['region_id']);
        unset($user_states[$chat_id]['city_id']);
        unset($user_states[$chat_id]['job_id']);
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "📍 Выберите ваш регион:",
            'reply_markup' => CitiesKeyboard::getRegionsKeyboard()
        ]);
        
        return true;
    }
    
    /**
     * Обработка кнопки "Назад к городам"
     */
    private static function handleBackToCities($telegram, $chat_id, &$user_states)
    {
        if (!isset($user_states[$chat_id])) {
            return false;
        }
        
        $region_id = $user_states[$chat_id]['region_id'] ?? null;
        
        if (!$region_id) {
            return false;
        }
        
        $user_states[$chat_id]['step'] = 5;
        unset($user_states[$chat_id]['city_id']);
        unset($user_states[$chat_id]['job_id']);
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "🏙 Выберите ваш город:",
            'reply_markup' => CitiesKeyboard::getCitiesKeyboard($region_id)
        ]);
        
        return true;
    }
    
    /**
     * Удаление сообщения с обработкой ошибок
     */
    public static function deleteMessage($telegram, $chat_id, $message_id)
    {
        try {
            $telegram->deleteMessage([
                'chat_id' => $chat_id,
                'message_id' => $message_id
            ]);
        } catch (\Exception $e) {
            echo "⚠️ Не удалось удалить сообщение: " . $e->getMessage() . "\n";
        }
    }
}