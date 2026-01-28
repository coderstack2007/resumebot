<?php
namespace App\Keyboards;

class NameKeyboard
{
    /**
     * Клавиатура "Назад" для шага ввода возраста
     */
    public static function getBackName(): string
    {
        $keyboard = [
            'keyboard' => [
                [['text' => '⬅️ Назад']]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];
        
        return json_encode($keyboard);
    }
}