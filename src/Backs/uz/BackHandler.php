<?php
namespace App\Backs\uz;

use App\Keyboards\uz\LanguageKeyboard;
use App\Keyboards\uz\NameKeyboard;
use App\Keyboards\uz\CitiesKeyboard;
use App\Keyboards\uz\JobsKeyboard;
use App\Keyboards\uz\NumberKeyboard;
use App\Checking\uz\Check;

class BackHandler
{
    /**
     * Проверка, является ли текст кнопкой "Назад"
     */
    public static function isBackButton($user_text): bool
    {
        return in_array($user_text, [
            '⬅️ Til tanlashga qaytish',
            '⬅️ Orqaga',
            '⬅️ Hududlarga qaytish',
            '⬅️ Shaharlarga qaytish'
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
            case '⬅️ Til tanlashga qaytish':
                return self::handleBackToLanguage($telegram, $chat_id, $user_states);
                
            case '⬅️ Orqaga':
                return self::handleBack($telegram, $chat_id, $user_states);
                
            case '⬅️ Hududlarga qaytish':
                return self::handleBackToRegions($telegram, $chat_id, $user_states);
                
            case '⬅️ Shaharlarga qaytish':
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
        $user_states[$chat_id] = [
            'state' => 'choosing_language'
        ];
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "Tilni tanlang:",
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
                'text' => "Iltimos, FIOingizni kiriting:",
                'reply_markup' => LanguageKeyboard::getBackKeyboard()
            ]);
        } elseif ($step == 3) {
            // Возврат к вводу возраста
            $user_states[$chat_id]['step'] = 2;
            unset($user_states[$chat_id]['phone']);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "🎂 Endi yoshingizni kiriting (15-60 yosh):",
                'reply_markup' => NameKeyboard::getBackName()
            ]);
        } elseif ($step == 4) {
            // Возврат к вводу телефона — нужна кнопка "Nomeringiz berishlar"
            $user_states[$chat_id]['step'] = 3;
            unset($user_states[$chat_id]['photo_filename']);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => Check::getAgeAcceptedMessage(),
                'reply_markup' => NumberKeyboard::getPhoneKeyboard()
            ]);
        } elseif ($step == 5) {
            // Возврат к загрузке фото
            $user_states[$chat_id]['step'] = 4;
            unset($user_states[$chat_id]['region_id']);
            unset($user_states[$chat_id]['city_id']);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => Check::getPhotoRequestMessage(),
                'reply_markup' => NameKeyboard::getBackName()
            ]);
        } elseif ($step == 8) {
            // Возврат с подтверждения на выбор вакансии
            $user_states[$chat_id]['step'] = 7;
            unset($user_states[$chat_id]['job_id']);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "💼 Qaysi vakansiyaga murojaat qilmoqchisiz?",
                'reply_markup' => JobsKeyboard::getJobsKeyboard()
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
        
        $user_states[$chat_id]['step'] = 5;
        unset($user_states[$chat_id]['region_id']);
        unset($user_states[$chat_id]['city_id']);
        unset($user_states[$chat_id]['job_id']);
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "📍 Hududingizni tanlang:",
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
        
        $user_states[$chat_id]['step'] = 6;
        unset($user_states[$chat_id]['city_id']);
        unset($user_states[$chat_id]['job_id']);
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => "🏙 Shaharingizni tanlang:",
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