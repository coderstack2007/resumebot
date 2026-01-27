<?php
namespace App\Keyboards;

class NameKeyboard 
{
    public static function  getBackName() {
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '↩️ Назад', 'callback_data' => 'back_to_name']],
                [['text' => '🏠 На главную', 'callback_data' => 'back_to_language']]
            ]
        ];
        return json_encode($keyboard);
    }
}