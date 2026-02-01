<?php
namespace App\Keyboards\uz;

class NumberKeyboard
{
    /**
     * Клавиатура с кнопкой "Номер берish" + кнопкой назад.
     * Кнопка request_contact отправляет contact объект напрямую.
     */
    public static function getPhoneKeyboard(): string
    {
        $keyboard = [
            'keyboard' => [
                [
                    [
                        'text'           => '📱 Nomeringiz berishlar',
                        'request_contact' => true
                    ]
                ],
                [
                    ['text' => '⬅️ Orqaga']
                ]
            ],
            'resize_keyboard'   => true,
            'one_time_keyboard' => false,
            'selective'         => true
        ];

        return json_encode($keyboard);
    }
}