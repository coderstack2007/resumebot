<?php
namespace App\Checking;

use App\Backs\BackHandler;

/**
 * Класс для проверки данных на русском языке
 */
class RuCheck
{
    /**
     * Проверка существования состояния пользователя
     */
    public static function checkUserStateExists($chat_id, $user_states): bool
    {
        return isset($user_states[$chat_id]);
    }
    
    /**
     * Проверка длины текста
     */
    public static function checkMaxLength($text, $maxLength = 50): bool
    {
        return mb_strlen($text, 'UTF-8') <= $maxLength;
    }
    
    /**
     * Проверка на пустое значение
     */
    public static function checkNotEmpty($text): bool
    {
        return !empty(trim($text));
    }
    
    /**
     * Проверка имени (ФИО)
     */
    public static function checkName($name): bool
    {
        return preg_match('/^[А-Яа-яЁёA-Za-z\s\-]+$/u', $name);
    }
    
    /**
     * Проверка возраста
     */
    public static function checkAge($age): bool
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
     * Проверка на использование только русских букв (без латиницы)
     */
    public static function checkRussianOnly($text): bool
    {
        // Проверяем, что текст содержит только русские буквы, пробелы и дефисы
        // Если найдены латинские буквы (a-z, A-Z) - возвращаем false
        return !preg_match('/[a-zA-Z]/', $text);
    }
    /**
     * Валидация текста с отправкой ошибки пользователю
     */
    public static function validateAndSendError($telegram, $chat_id, $user_text, $message_id, $keyboard): bool
    {
        // Проверка на максимальную длину (50 символов)
        if (!self::checkMaxLength($user_text)) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => self::getMaxLengthError(),
                'reply_markup' => $keyboard
            ]);
            return false;
        }
        
        // Проверка на пустое значение
        if (!self::checkNotEmpty($user_text)) {
            BackHandler::deleteMessage($telegram, $chat_id, $message_id);
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => self::getNotEmptyError(),
                'reply_markup' => $keyboard
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Получить сообщение об ошибке для проверки длины
     */
    public static function getMaxLengthError(): string
    {
        return '❌ Ошибка: текст не должен превышать 50 символов. Попробуйте еще раз:';
    }
    
    /**
     * Получить сообщение об ошибке для пустого значения
     */
    public static function getNotEmptyError(): string
    {
        return '❌ Ошибка: поле не может быть пустым. Попробуйте еще раз:';
    }
    
    /**
     * Получить сообщение об ошибке для имени
     */
    public static function getNameError(): string
    {
        return '❌ Имя может содержать только буквы, пробелы и дефисы. Попробуйте еще раз:';
    }
    
    /**
     * Получить сообщение об ошибке для возраста (не число)
     */
    public static function getAgeNumberError(): string
    {
        return '❌ Возраст должен быть числом. Попробуйте еще раз:';
    }
    
    /**
     * Получить сообщение об ошибке для возраста (неправильный диапазон)
     */
    public static function getAgeRangeError(): string
    {
        return '❌ Возраст должен быть в диапазоне от 15 до 60 лет. Попробуйте еще раз:';
    }
    
    /**
     * Получить сообщение о принятии имени
     */
    public static function getNameAcceptedMessage(): string
    {
        return "✅ ФИО принято!\n\nТеперь введите ваш возраст (15-60 лет):";
    }
}