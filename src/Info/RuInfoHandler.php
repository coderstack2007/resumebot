<?php
namespace App\Info;

use App\Keyboards\LanguageKeyboard;
use App\Keyboards\NameKeyboard;
use App\Checking\ruCheck; 

class RuInfoHandler
{
    /**
     * Обработка ввода данных пользователя на русском языке
     */
    public static function getStartMessage()
    {
        return "✅ Язык выбран: Русский\n\nПожалуйста, введите ваше ФИО:";
    }
    
    public static function handleUserInput($telegram, $chat_id, $user_text, &$user_states)
    {
        // Проверка, существует ли состояние пользователя
        if (!isset($user_states[$chat_id])) {
            return false;
        }
        
        $user_state = $user_states[$chat_id];
        
        // Проверка на максимальную длину (50 символов)
        if (!ruCheck::checkMaxLength($user_text)) {
            $keyboard = ($user_state['step'] == 1) 
                ? LanguageKeyboard::getBackKeyboard() 
                : NameKeyboard::getBackName();
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => ruCheck::getMaxLengthError(),
                'reply_markup' => $keyboard
            ]);
            return false;
        }
        
        // Проверка на пустое значение
        if (!ruCheck::checkNotEmpty($user_text)) {
            $keyboard = ($user_state['step'] == 1) 
                ? LanguageKeyboard::getBackKeyboard() 
                : NameKeyboard::getBackName();
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => ruCheck::getNotEmptyError(),
                'reply_markup' => $keyboard
            ]);
            return false;
        }
        
        switch ($user_state['step']) {
            case 1: // Ожидаем имя
                return self::handleName($telegram, $chat_id, $user_text, $user_states);
            case 2: // Ожидаем возраст
                return self::handleAge($telegram, $chat_id, $user_text, $user_states);
        }
        
        return false;
    }
    
    /**
     * Обработка имени
     */
    private static function handleName($telegram, $chat_id, $user_text, &$user_states)
    {
        // Проверка имени
        if (!ruCheck::checkName($user_text)) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => ruCheck::getNameError(),
                'reply_markup' => LanguageKeyboard::getBackKeyboard()
            ]);
            return false;
        }
        
        // Сохраняем имя и переходим к следующему шагу
        $user_states[$chat_id]['name'] = $user_text;
        $user_states[$chat_id]['step'] = 2; 
        
        // Запрашиваем возраст с NameKeyboard
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => ruCheck::getNameAcceptedMessage(),
            'reply_markup' => NameKeyboard::getBackName()
        ]);
        
        return true;
    }

    /**
     * Обработка возраста
     */
    private static function handleAge($telegram, $chat_id, $user_text, &$user_states)
    {
        // Проверка возраста
        if (!is_numeric($user_text)) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => ruCheck::getAgeNumberError(),
                'reply_markup' => NameKeyboard::getBackName()
            ]);
            return false;
        }
        
        if (!ruCheck::checkAge($user_text)) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => ruCheck::getAgeRangeError(),
                'reply_markup' => NameKeyboard::getBackName()
            ]);
            return false;
        }

        // Сохраняем возраст
        $user_states[$chat_id]['age'] = (int)$user_text;
        
        // Выводим итоговую информацию
        $name = $user_states[$chat_id]['name'];
        $age = $user_states[$chat_id]['age'];
        
        $response_text = "✅ Спасибо! Ваши данные сохранены:\n";
        $response_text .= "👤 ФИО: $name\n";
        $response_text .= "🎂 Возраст: $age лет\n";
        
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $response_text,
            'reply_markup' => NameKeyboard::getBackName()
        ]);
        
        // Очищаем состояние пользователя
        unset($user_states[$chat_id]);
        
        return true;
    }
}