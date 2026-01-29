<?php
namespace App\Cities\uz;

class Cities
{
    /**
     * Получить все регионы
     */
    public static function getRegions(): array
    {
        return [
            1 => 'Toshkent',
            2 => 'Toshkent viloyati',
            3 => 'Andijon viloyati',
            4 => 'Buxoro viloyati',
            5 => 'Jizzax viloyati',
            6 => 'Qashqadaryo viloyati',
            7 => 'Navoiy viloyati',
            8 => 'Namangan viloyati',
            9 => 'Samarqand viloyati',
            10 => 'Surxondaryo viloyati',
            11 => 'Sirdaryo viloyati',
            12 => 'Farg\'ona viloyati',
            13 => 'Xorazm viloyati',
            14 => 'Qoraqalpog\'iston Respublikasi'
        ];
    }

    /**
     * Получить города по ID региона
     */
    public static function getCitiesByRegion(int $region_id): array
    {
        $regions = [
            1 => [
                'id' => 1,
                'name' => 'Toshkent',
                'cities' => [
                    101 => 'Chilonzor',
                    102 => 'Yunusobod',
                    103 => 'Mirobod',
                    104 => 'Sergeli',
                    105 => 'Olmazor',
                    106 => 'Yakkasaroy',
                    107 => 'Shayhontohur',
                    108 => 'Uchtepa',
                    109 => 'Bektemir',
                    110 => 'Yashnobod',
                    111 => 'Mirzo Ulug\'bek'
                ]
            ],
            2 => [
                'id' => 2,
                'name' => 'Toshkent viloyati',
                'cities' => [
                    201 => 'Angren',
                    202 => 'Olmaliq',
                    203 => 'Bekobod',
                    204 => 'Chirchiq',
                    205 => 'Yangiyo\'l',
                    206 => 'Nurafshon',
                    207 => 'Ohangaron',
                    208 => 'Parkent',
                    209 => 'Bo\'ka',
                    210 => 'G\'azalkent'
                ]
            ],
            3 => [
                'id' => 3,
                'name' => 'Andijon viloyati',
                'cities' => [
                    301 => 'Andijon',
                    302 => 'Asaka',
                    303 => 'Shahrixon',
                    304 => 'Xonobod',
                    305 => 'Paytug\'',
                    306 => 'Marhamat',
                    307 => 'Qo\'rg\'ontepa',
                    308 => 'Xo\'jaobod',
                    309 => 'Baliqchi',
                    310 => 'Izboskan'
                ]
            ],
            4 => [
                'id' => 4,
                'name' => 'Buxoro viloyati',
                'cities' => [
                    401 => 'Buxoro',
                    402 => 'Kogon',
                    403 => 'G\'allaoral',
                    404 => 'G\'azli',
                    405 => 'Qorako\'l',
                    406 => 'Vobkent',
                    407 => 'Jondor',
                    408 => 'Romitan',
                    409 => 'Shofirkon',
                    410 => 'G\'ijduvon'
                ]
            ],
            5 => [
                'id' => 5,
                'name' => 'Jizzax viloyati',
                'cities' => [
                    501 => 'Jizzax',
                    502 => 'G\'allaoral',
                    503 => 'Do\'stlik',
                    504 => 'Paxtakor',
                    505 => 'Zafarobod',
                    506 => 'Zarbdor',
                    507 => 'Zomin',
                    508 => 'Baxmal',
                    509 => 'Forish',
                    510 => 'Mirzacho\'l'
                ]
            ],
            6 => [
                'id' => 6,
                'name' => 'Qashqadaryo viloyati',
                'cities' => [
                    601 => 'Qarshi',
                    602 => 'Shahrisabz',
                    603 => 'Kitob',
                    604 => 'G\'uzor',
                    605 => 'Muborak',
                    606 => 'Koson',
                    607 => 'Kamashi',
                    608 => 'Yakkabog\'',
                    609 => 'Chiroqchi',
                    610 => 'Nishon'
                ]
            ],
            7 => [
                'id' => 7,
                'name' => 'Navoiy viloyati',
                'cities' => [
                    701 => 'Navoiy',
                    702 => 'Zarafshon',
                    703 => 'Karmana',
                    704 => 'Uchquduq',
                    705 => 'Nurota',
                    706 => 'Qiziltepa',
                    707 => 'Xatirchi',
                    708 => 'Tamdibuloq',
                    709 => 'Konimex',
                    710 => 'Navbahor'
                ]
            ],
            8 => [
                'id' => 8,
                'name' => 'Namangan viloyati',
                'cities' => [
                    801 => 'Namangan',
                    802 => 'Xaqqulobod',
                    803 => 'Chust',
                    804 => 'Kosonsoy',
                    805 => 'To\'raqo\'rg\'on',
                    806 => 'Uchqo\'rg\'on',
                    807 => 'Pop',
                    808 => 'Mingbuloq',
                    809 => 'Yangiqo\'rg\'on',
                    810 => 'Chortoq'
                ]
            ],
            9 => [
                'id' => 9,
                'name' => 'Samarqand viloyati',
                'cities' => [
                    901 => 'Samarqand',
                    902 => 'Kattaqo\'rg\'on',
                    903 => 'Bulung\'ur',
                    904 => 'Jomboy',
                    905 => 'Ishtixon',
                    906 => 'Oqdaryo',
                    907 => 'Payariq',
                    908 => 'Urgut',
                    909 => 'Nurobod',
                    910 => 'Chelak'
                ]
            ],
            10 => [
                'id' => 10,
                'name' => 'Surxondaryo viloyati',
                'cities' => [
                    1001 => 'Termiz',
                    1002 => 'Denov',
                    1003 => 'Sho\'rchi',
                    1004 => 'Boysun',
                    1005 => 'Qumqo\'rg\'on',
                    1006 => 'Jarqo\'rg\'on',
                    1007 => 'Oltinsoy',
                    1008 => 'Sharg\'un',
                    1009 => 'Uzun',
                    1010 => 'Sariosiyo'
                ]
            ],
            11 => [
                'id' => 11,
                'name' => 'Sirdaryo viloyati',
                'cities' => [
                    1101 => 'Guliston',
                    1102 => 'Yangiyer',
                    1103 => 'Sirdaryo',
                    1104 => 'Baxt',
                    1105 => 'Sharaf Rashidov',
                    1106 => 'Oqoltin',
                    1107 => 'Mirzaobod',
                    1108 => 'Sayxunobod',
                    1109 => 'Xovos',
                    1110 => 'Do\'stlik'
                ]
            ],
            12 => [
                'id' => 12,
                'name' => 'Farg\'ona viloyati',
                'cities' => [
                    1201 => 'Farg\'ona',
                    1202 => 'Marg\'ilon',
                    1203 => 'Qo\'qon',
                    1204 => 'Quvasoy',
                    1205 => 'Rishton',
                    1206 => 'Yaypan',
                    1207 => 'Beshariq',
                    1208 => 'Quva',
                    1209 => 'Uchko\'prik',
                    1210 => 'Toshloq'
                ]
            ],
            13 => [
                'id' => 13,
                'name' => 'Xorazm viloyati',
                'cities' => [
                    1301 => 'Urganch',
                    1302 => 'Xiva',
                    1303 => 'Pitnak',
                    1304 => 'Shovot',
                    1305 => 'Xazorasp',
                    1306 => 'Xonqa',
                    1307 => 'Gurlan',
                    1308 => 'Bog\'ot',
                    1309 => 'Yangiariq',
                    1310 => 'Qo\'shko\'pir'
                ]
            ],
            14 => [
                'id' => 14,
                'name' => 'Qoraqalpog\'iston Respublikasi',
                'cities' => [
                    1401 => 'Nukus',
                    1402 => 'To\'rtko\'l',
                    1403 => 'Beruniy',
                    1404 => 'Qo\'ng\'irot',
                    1405 => 'Mo\'ynoq',
                    1406 => 'Taxiatosh',
                    1407 => 'Xo\'jayli',
                    1408 => 'Chimboy',
                    1409 => 'Qorao\'zak',
                    1410 => 'Shumanay'
                ]
            ]
        ];

        if (isset($regions[$region_id])) {
            return $regions[$region_id]['cities'];
        }

        return [];
    }

    /**
     * Получить название региона по ID
     */
    public static function getRegionName(int $region_id): ?string
    {
        $regions = self::getRegions();
        return $regions[$region_id] ?? null;
    }

    /**
     * Получить название города по ID региона и ID города
     */
    public static function getCityName(int $region_id, int $city_id): ?string
    {
        $cities = self::getCitiesByRegion($region_id);
        return $cities[$city_id] ?? null;
    }

    /**
     * Проверить существует ли регион
     */
    public static function regionExists(int $region_id): bool
    {
        return isset(self::getRegions()[$region_id]);
    }

    /**
     * Проверить существует ли город в регионе
     */
    public static function cityExists(int $region_id, int $city_id): bool
    {
        $cities = self::getCitiesByRegion($region_id);
        return isset($cities[$city_id]);
    }
}