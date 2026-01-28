<?php
namespace App\Keyboards;

use App\Cities\RuCities;

class CitiesKeyboard
{
    /**
     * Клавиатура для выбора региона
     */
    public static function getRegionsKeyboard()
    {
        $regions = RuCities::getRegions();
        $buttons = [];
        
        foreach ($regions as $id => $name) {
            $buttons[] = [
                [
                    'text' => $name,
                    'callback_data' => 'region_' . $id
                ]
            ];
        }
        
        // Добавляем кнопку "Назад к возрасту"
        $buttons[] = [
            [
                'text' => '⬅️ Назад',
                'callback_data' => 'back_to_age'
            ]
        ];
        
        return json_encode([
            'inline_keyboard' => $buttons
        ]);
    }
    
    /**
     * Клавиатура для выбора города
     */
    public static function getCitiesKeyboard(int $region_id)
    {
        $cities = RuCities::getCitiesByRegion($region_id);
        $buttons = [];
        
        foreach ($cities as $city_id => $city_name) {
            $buttons[] = [
                [
                    'text' => $city_name,
                    'callback_data' => 'city_' . $region_id . '_' . $city_id
                ]
            ];
        }
        
        // Добавляем кнопку "Назад к регионам" вместо "back_to_age"
        $buttons[] = [
            [
                'text' => '⬅️ Назад',
                'callback_data' => 'back_to_regions'
            ]
        ];
        
        return json_encode([
            'inline_keyboard' => $buttons
        ]);
    }
}