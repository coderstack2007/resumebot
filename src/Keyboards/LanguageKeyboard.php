<?php
namespace App\Keyboards;

class LanguageKeyboard
{
    public static function getLanguageKeyboard(): string
    {
        $keyboard = [
            'inline_keyboard' => [
                [['text' => "🇷🇺 Русский", 'callback_data' => 'lang_ru']],
                [['text' => "🇺🇿 O'zbekcha", 'callback_data' => 'lang_uz']],
            ]
        ];
        return json_encode($keyboard);
    }

    public static function getBackKeyboard(): string
    {
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '⬅️ Назад', 'callback_data' => 'back_to_language']],
                [['text' => '🏠 На главную', 'callback_data' => 'main_menu']],
            ]
        ];
        return json_encode($keyboard);
    }
}
