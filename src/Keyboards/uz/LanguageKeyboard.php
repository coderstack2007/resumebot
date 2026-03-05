<?php
namespace App\Keyboards\uz;

class LanguageKeyboard
{
    /**
     * Главное меню с кнопкой "Оставить резюме"
     */
    public static function getMainMenu(): string
    {
        $keyboard = [
            'keyboard' => [
                [['text' => "📝 Rezyume qoldirish"]]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];
        
        return json_encode($keyboard);
    }
    
    /**
     * Клавиатура выбора языка (Reply Keyboard)
     */
    public static function getLanguageKeyboard(): string
    {
        $keyboard = [
            'keyboard' => [
                [['text' => "🇷🇺 Русский"]],
                [['text' => "🇺🇿 O'zbekcha"]]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];
        
        return json_encode($keyboard);
    }
    
    /**
     * Клавиатура "Назад к языку" (Reply Keyboard)
     */
    public static function getBackKeyboard(): string
    {
        $keyboard = [
            'keyboard' => [
                [['text' => '⬅️ Til tanlashga qaytish']]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];
        
        return json_encode($keyboard);
    }
    
    /**
     * Проверка, является ли текст кнопкой выбора языка
     */
    public static function isLanguageButton($text): bool
    {
        return in_array($text, ['🇷🇺 Русский', "🇺🇿 O'zbekcha"]);
    }
    
    /**
     * Проверка, является ли текст кнопкой "Оставить резюме"
     */
    public static function isResumeButton($text): bool
    {
        return $text === '📝 Rezyume qoldirish';
    }
}