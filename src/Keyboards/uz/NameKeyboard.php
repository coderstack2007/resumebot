<?php
namespace App\Keyboards\uz;

class NameKeyboard
{
    /**
     * Клавиатура "Назад" для шага ввода возраста
     */
    public static function getBackName(): string
    {
        $keyboard = [
            'keyboard' => [
                [['text' => '⬅️ Orqaga']]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];
        
        return json_encode($keyboard);
    }
}