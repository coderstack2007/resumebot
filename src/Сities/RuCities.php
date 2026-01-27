<?php
namespace App\Cities;

class RuCities
{
    public static function getRegions()
    {
        return [
            1 => [
                'id' => 1,
                'name' => 'Навои',
                'cities' => [
                    101 => 'Кармана',
                    102 => 'Навои г'
                ]
            ],
            2 => [
                'id' => 2,
                'name' => 'Ташкент',
                'cities' => [
                    201 => 'Чиланзар',
                    202 => 'Юнусобод'
                ]
            ],
            // Добавьте другие регионы по необходимости
            3 => [
                'id' => 3,
                'name' => 'Самарканд',
                'cities' => [
                    301 => 'Самарканд г',
                    302 => 'Ургут'
                ]
            ],
            4 => [
                'id' => 4,
                'name' => 'Бухара',
                'cities' => [
                    401 => 'Бухара г',
                    402 => 'Гиждуван'
                ]
            ]
        ];
    }
    
    public static function getAllCities()
    {
        $allCities = [];
        foreach (self::getRegions() as $region) {
            foreach ($region['cities'] as $id => $name) {
                $allCities[$id] = $name;
            }
        }
        return $allCities;
    }
    
    public static function getRegionByCityId($cityId)
    {
        foreach (self::getRegions() as $region) {
            if (isset($region['cities'][$cityId])) {
                return $region;
            }
        }
        return null;
    }
    
    public static function getCityName($cityId)
    {
        $allCities = self::getAllCities();
        return $allCities[$cityId] ?? null;
    }
    
    public static function getRegionById($regionId)
    {
        $regions = self::getRegions();
        return $regions[$regionId] ?? null;
    }
}