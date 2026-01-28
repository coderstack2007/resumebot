<?php
namespace App\Keyboards;

class NameKeyboard
{
    /**
     * Клавиатура "Назад" для шага ввода имени
     */
    public static function getBackName(): string
    {
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '⬅️ Назад', 'callback_data' => 'back_to_name']],
                [['text' => '🏠 На главную', 'callback_data' => 'main_menu']]
            ]
        ];
        
        return json_encode($keyboard);
    }
}