<?php
namespace App\Keyboards;

class LanguageKeyboard
{
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
                [['text' => '⬅️ Назад к выбору языка']]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];
        
        return json_encode($keyboard);
    }
}