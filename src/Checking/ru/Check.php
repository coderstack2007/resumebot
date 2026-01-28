<?php
namespace App\Checking\ru;

use App\Backs\BackHandler;

/**
 * Класс для проверки данных на русском языке
 */
class Check
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
     * Проверка телефонного номера в формате +998XXXXXXXXX
     * Где X - любая цифра (всего 9 цифр после +998)
     */
    public static function checkPhoneNumber($phone): bool
    {
        // Удаляем все пробелы, скобки, тире для проверки
        $cleanPhone = preg_replace('/[\s\(\)\-]/', '', $phone);
        
        // Проверяем формат: +998 и ровно 9 цифр после него
        return preg_match('/^\+998\d{9}$/', $cleanPhone);
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
     * Получить сообщение об ошибке для телефонного номера
     */
    public static function getPhoneError(): string
    {
        return "❌ Неверный формат номера телефона!\n\n" .
               "Правильный формат: +998XXXXXXXXX\n" .
               "Пример: +998901234567\n\n" .
               "Попробуйте еще раз:";
    }
    
    /**
     * Получить сообщение о принятии имени
     */
    public static function getNameAcceptedMessage(): string
    {
        return "✅ ФИО принято!\n\n🎂 Теперь введите ваш возраст (15-60 лет):";
    }
    
    /**
     * Получить сообщение о принятии возраста
     */
    public static function getAgeAcceptedMessage(): string
    {
        return "✅ Возраст принят!\n\n📱 Теперь введите ваш номер телефона в формате: +998XXXXXXXXX";
    }
    
    /**
     * Получить сообщение о принятии телефона
     */
    public static function getPhoneAcceptedMessage(): string
    {
        return "✅ Номер телефона принят!";
    }
    
    /**
     * Проверка размера файла изображения (максимум 5 МБ)
     */
    public static function checkImageSize($file_size): bool
    {
        $max_size = 5 * 1024 * 1024; // 5 МБ в байтах
        return $file_size <= $max_size;
    }
    
    /**
     * Проверка формата изображения (ТОЛЬКО PNG и JPG/JPEG, WebP ЗАПРЕЩЕН)
     */
    public static function checkImageFormat($mime_type, $extension = null): bool
    {
        // ТОЛЬКО эти форматы разрешены
        $allowed_formats = ['image/jpeg', 'image/jpg', 'image/png'];
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        
        // Проверяем MIME-тип
        $mime_valid = in_array(strtolower($mime_type), $allowed_formats);
        
        // Если указано расширение, проверяем и его
        if ($extension !== null) {
            $ext_valid = in_array(strtolower($extension), $allowed_extensions);
            return $mime_valid && $ext_valid;
        }
        
        
        return $mime_valid;
    }

    /**
     * Получить сообщение об ошибке для размера изображения
     */
    public static function getImageSizeError(): string
    {
        return "❌ Ошибка: размер изображения не должен превышать 5 МБ.\n\nПопробуйте отправить другое фото:";
    }
    
    /**
     * Получить сообщение об ошибке для формата изображения
     */
    public static function getImageFormatError(): string
    {
        return "❌ Ошибка: неподдерживаемый формат изображения!\n\n" .
               "Разрешенные форматы: PNG, JPG/JPEG\n\n" .
               "Попробуйте отправить другое фото:";
    }
    
    /**
     * Получить сообщение об ошибке для отсутствия изображения
     */
    public static function getImageRequiredError(): string
    {
        return "❌ Ошибка: пожалуйста, отправьте фотографию (не файл и не текст).\n\n" .
               "Разрешенные форматы: PNG, JPG/JPEG\n" .
               "Максимальный размер: 5 МБ";
    }
    
    /**
     * Получить сообщение о запросе фото
     */
    public static function getPhotoRequestMessage(): string
    {
        return "📸 Теперь отправьте ваше фото:\n\n" .
               "✅ Разрешенные форматы: PNG, JPG/JPEG\n" .
               "✅ Максимальный размер: 5 МБ";
    }
    
    /**
     * Получить сообщение о принятии фото
     */
    public static function getPhotoAcceptedMessage(): string
    {
        return "✅ Фото успешно получено и сохранено!";
    }
}