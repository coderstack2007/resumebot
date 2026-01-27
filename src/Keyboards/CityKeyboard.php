<?php
namespace App\Keyboards;

use Telegram\Bot\Keyboard\Keyboard;
use App\Cities\RuCities;

class CityKeyboard
{
    public static function getRegionKeyboard()
    {
        $regions = RuCities::getRegions();
        $keyboard = [];
        
        foreach ($regions as $region) {
            $keyboard[] = [$region['name']];
        }
        
        // Добавляем кнопку "Назад" если нужно
        // $keyboard[] = ['🔙 Назад'];
        
        return Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]);
    }
    
    public static function getCitiesKeyboard($regionId)
    {
        $region = RuCities::getRegionById($regionId);
        if (!$region) {
            return self::getRegionKeyboard(); // Возвращаем клавиатуру областей если регион не найден
        }
        
        $keyboard = [];
        foreach ($region['cities'] as $cityId => $cityName) {
            $keyboard[] = [$cityName];
        }
        
        // Кнопка возврата к выбору области
        $keyboard[] = ['🔙 Назад к выбору области'];
        
        return Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]);
    }
    
    public static function getBackToRegionsKeyboard()
    {
        return Keyboard::make([
            'keyboard' => [['🔙 Назад к выбору области']],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]);
    }
}