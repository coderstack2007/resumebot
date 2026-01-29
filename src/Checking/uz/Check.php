<?php
namespace App\Checking\uz;

use App\Backs\uz\BackHandler;

/**
 * Класс для проверки данных на узбекском языке
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
        return preg_match('/^[А-Яа-яЁёA-Za-z\s\-\']+$/u', $name);
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
     * Проверка на использование только узбекских/русских букв (без латиницы для основного текста)
     */
    public static function checkRussianOnly($text): bool
    {
        // Проверяем, что текст содержит только русские/узбекские буквы, пробелы и дефисы
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
        return '❌ Xatolik: matn 50 belgidan oshmasligi kerak. Yana bir marta urinib ko\'ring:';
    }
    
    /**
     * Получить сообщение об ошибке для пустого значения
     */
    public static function getNotEmptyError(): string
    {
        return '❌ Xatolik: maydon bo\'sh bo\'lishi mumkin emas. Yana bir marta urinib ko\'ring:';
    }
    
    /**
     * Получить сообщение об ошибке для имени
     */
    public static function getNameError(): string
    {
        return '❌ Ism faqat harflar, bo\'sh joylar va defislardan iborat bo\'lishi mumkin. Yana bir marta urinib ko\'ring:';
    }
    
    /**
     * Получить сообщение об ошибке для возраста (не число)
     */
    public static function getAgeNumberError(): string
    {
        return '❌ Yosh raqam bo\'lishi kerak. Yana bir marta urinib ko\'ring:';
    }
    
    /**
     * Получить сообщение об ошибке для возраста (неправильный диапазон)
     */
    public static function getAgeRangeError(): string
    {
        return '❌ Yosh 15 dan 60 yoshgacha bo\'lishi kerak. Yana bir marta urinib ko\'ring:';
    }
    
    /**
     * Получить сообщение об ошибке для телефонного номера
     */
    public static function getPhoneError(): string
    {
        return "❌ Telefon raqami formati noto'g'ri!\n\n" .
               "To'g'ri format: +998XXXXXXXXX\n" .
               "Misol: +998901234567\n\n" .
               "Yana bir marta urinib ko'ring:";
    }
    
    /**
     * Получить сообщение о принятии имени
     */
    public static function getNameAcceptedMessage(): string
    {
        return "✅ FIO qabul qilindi!\n\n🎂 Endi yoshingizni kiriting (15-60 yosh):";
    }
    
    /**
     * Получить сообщение о принятии возраста
     */
    public static function getAgeAcceptedMessage(): string
    {
        return "✅ Yosh qabul qilindi!\n\n📱 Endi telefon raqamingizni kiriting: +998XXXXXXXXX formatida";
    }
    
    /**
     * Получить сообщение о принятии телефона
     */
    public static function getPhoneAcceptedMessage(): string
    {
        return "✅ Telefon raqami qabul qilindi!";
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
        return "❌ Xatolik: rasm hajmi 5 MB dan oshmasligi kerak.\n\nBoshqa rasm yuboring:";
    }
    
    /**
     * Получить сообщение об ошибке для формата изображения
     */
    public static function getImageFormatError(): string
    {
        return "❌ Xatolik: rasm formati qo'llab-quvvatlanmaydi!\n\n" .
               "Ruxsat etilgan formatlar: PNG, JPG/JPEG\n\n" .
               "Boshqa rasm yuboring:";
    }
    
    /**
     * Получить сообщение об ошибке для отсутствия изображения
     */
    public static function getImageRequiredError(): string
    {
        return "❌ Xatolik: iltimos, rasm yuboring (fayl yoki matn emas).\n\n" .
               "Ruxsat etilgan formatlar: PNG, JPG/JPEG\n" .
               "Maksimal hajmi: 5 MB";
    }
    
    /**
     * Получить сообщение о запросе фото
     */
    public static function getPhotoRequestMessage(): string
    {
        return "📸 Endi rasmingizni yuboring:\n\n" .
               "✅ Ruxsat etilgan formatlar: PNG, JPG/JPEG\n" .
               "✅ Maksimal hajmi: 5 MB";
    }
    
    /**
     * Получить сообщение о принятии фото
     */
    public static function getPhotoAcceptedMessage(): string
    {
        return "✅ Rasm muvaffaqiyatli qabul qilindi va saqlandi!";
    }
}