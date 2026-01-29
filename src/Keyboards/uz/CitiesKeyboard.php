<?php
namespace App\Keyboards\uz;

use App\Cities\uz\Cities;

class CitiesKeyboard
{
    /**
     * Клавиатура для выбора региона (Reply Keyboard)
     */
    public static function getRegionsKeyboard()
    {
        $regions = Cities::getRegions();
        $buttons = [];
        
        // Создаем кнопки по 2 в ряд
        $row = [];
        $count = 0;
        
        foreach ($regions as $id => $name) {
            $row[] = ['text' => $name];
            $count++;
            
            // Когда накопилось 2 кнопки, добавляем ряд
            if ($count == 2) {
                $buttons[] = $row;
                $row = [];
                $count = 0;
            }
        }
        
        // Добавляем оставшиеся кнопки
        if (!empty($row)) {
            $buttons[] = $row;
        }
        
        // Добавляем кнопку "Назад"
        $buttons[] = [
            ['text' => '⬅️ Orqaga']
        ];
        
        return json_encode([
            'keyboard' => $buttons,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]);
    }
    
    /**
     * Клавиатура для выбора города (Reply Keyboard)
     */
    public static function getCitiesKeyboard(int $region_id)
    {
        $cities = Cities::getCitiesByRegion($region_id);
        $buttons = [];
        
        // Создаем кнопки по 2 в ряд
        $row = [];
        $count = 0;
        
        foreach ($cities as $city_id => $city_name) {
            $row[] = ['text' => $city_name];
            $count++;
            
            // Когда накопилось 2 кнопки, добавляем ряд
            if ($count == 2) {
                $buttons[] = $row;
                $row = [];
                $count = 0;
            }
        }
        
        // Добавляем оставшиеся кнопки
        if (!empty($row)) {
            $buttons[] = $row;
        }
        
        // Добавляем кнопку "Назад к регионам"
        $buttons[] = [
            ['text' => '⬅️ Hududlarga qaytish']
        ];
        
        return json_encode([
            'keyboard' => $buttons,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]);
    }
}