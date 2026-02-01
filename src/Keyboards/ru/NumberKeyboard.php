<?php
namespace App\Keyboards\ru;

class NumberKeyboard
{
    /**
     * Клавиатура с кнопкой "Поделиться номером" + кнопкой назад.
     * Кнопка request_contact отправляет contact объект напрямую.
     */
    public static function getPhoneKeyboard(): string
    {
        $keyboard = [
            'keyboard' => [
                [
                    [
                        'text'           => '📱 Поделиться номером',
                        'request_contact' => true
                    ]
                ],
                [
                    ['text' => '⬅️ Назад']
                ]
            ],
            'resize_keyboard'   => true,
            'one_time_keyboard' => false,
            'selective'         => true
        ];

        return json_encode($keyboard);
    }
}