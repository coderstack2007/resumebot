<?php
namespace App\Checking;

/**
 * Класс для проверки данных на русском языке
 */
class RuCheck
{
    /**
     * Проверка длины текста
     */
    public static function checkMaxLength($text, $maxLength = 50)
    {
        return mb_strlen($text, 'UTF-8') <= $maxLength;
    }
    
    /**
     * Проверка на пустое значение
     */
    public static function checkNotEmpty($text)
    {
        return !empty(trim($text));
    }
    
    /**
     * Проверка имени (ФИО)
     */
    public static function checkName($name)
    {
        return preg_match('/^[А-Яа-яЁёA-Za-z\s\-]+$/u', $name);
    }
    
    /**
     * Проверка возраста
     */
    public static function checkAge($age)
    {
        // Проверяем, что это число
        if (!is_numeric($age)) {
            return false;
        }
        
        $age = (int)$age;
        
        // Проверяем диапазон 15-60
        return ($age >= 15 && $age <= 60);
    }
    
    /**
     * Получить сообщение об ошибке для проверки длины
     */
    public static function getMaxLengthError()
    {
        return '❌ Ошибка: текст не должен превышать 50 символов. Попробуйте еще раз:';
    }
    
    /**
     * Получить сообщение об ошибке для пустого значения
     */
    public static function getNotEmptyError()
    {
        return '❌ Ошибка: поле не может быть пустым. Попробуйте еще раз:';
    }
    
    /**
     * Получить сообщение об ошибке для имени
     */
    public static function getNameError()
    {
        return '❌ Имя может содержать только буквы, пробелы и дефисы. Попробуйте еще раз:';
    }
    
    /**
     * Получить сообщение об ошибке для возраста (не число)
     */
    public static function getAgeNumberError()
    {
        return '❌ Возраст должен быть числом. Попробуйте еще раз:';
    }
    
    /**
     * Получить сообщение об ошибке для возраста (неправильный диапазон)
     */
    public static function getAgeRangeError()
    {
        return '❌ Возраст должен быть в диапазоне от 15 до 60 лет. Попробуйте еще раз:';
    }
    
    /**
     * Получить сообщение о принятии имени
     */
    public static function getNameAcceptedMessage()
    {
        return "✅ ФИО принято!\n\nТеперь введите ваш возраст (15-60 лет):";
    }
}