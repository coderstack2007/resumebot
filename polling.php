<?php
require_once 'vendor/autoload.php';

use Telegram\Bot\Api;
use App\BotSettings;
use App\Keyboards\ru\LanguageKeyboard;
use App\Info\RuInfoHandler;
use App\Info\UzInfoHandler;

$telegram = new Api(BotSettings::TOKEN);

// Бесконечный цикл опроса
echo "🤖 Бот запущен в режиме polling...\n";
$last_update_id = 0;

// Хранилище состояний пользователей
$user_states = [];

while (true) {
    try {
        // Получаем новые обновления
        $updates = $telegram->getUpdates([
            'offset' => $last_update_id + 1,
            'limit' => 10,
            'timeout' => 30
        ]);
        
        foreach ($updates as $update) {
            $last_update_id = $update['update_id'];

            // ─── Обработка contact (кнопка "Поделиться номером") ───
            if (isset($update['message']) && isset($update['message']['contact'])) {
                $chat_id    = $update['message']['chat']['id'];
                $contact    = $update['message']['contact'];
                $message_id = $update['message']['message_id'];

                if (isset($user_states[$chat_id]) && isset($user_states[$chat_id]['step'])) {
                    $user_state = $user_states[$chat_id];

                    switch ($user_state['language']) {
                        case 'ru':
                            RuInfoHandler::handleContact($telegram, $chat_id, $contact, $message_id, $user_states);
                            break;
                        case 'uz':
                            UzInfoHandler::handleContact($telegram, $chat_id, $contact, $message_id, $user_states);
                            break;
                    }
                }

                echo "✅ Обработан contact от $chat_id\n";
                continue;
            }
            
            // ─── Обработка фото ───
            if (isset($update['message']) && isset($update['message']['photo'])) {
                $chat_id = $update['message']['chat']['id'];
                $photo_array = $update['message']['photo'];
                $message_id = $update['message']['message_id'];
                
                if (isset($user_states[$chat_id]) && isset($user_states[$chat_id]['step'])) {
                    $user_state = $user_states[$chat_id];
                    
                    switch ($user_state['language']) {
                        case 'ru':
                            RuInfoHandler::handlePhoto($telegram, $chat_id, $photo_array, $message_id, $user_states);
                            break;
                            
                        case 'uz':
                            UzInfoHandler::handlePhoto($telegram, $chat_id, $photo_array, $message_id, $user_states);
                            break;
                    }
                }
                
                echo "✅ Обработано фото от $chat_id\n";
                continue;
            }
            
            // ─── Обработка текстовых сообщений ───
            if (isset($update['message']) && isset($update['message']['text'])) {
                $chat_id = $update['message']['chat']['id'];
                $user_text = trim($update['message']['text']);
                $message_id = $update['message']['message_id'];
                
                // Обработка /start
                if (strtolower($user_text) === '/start') {
                    if (isset($user_states[$chat_id])) {
                        unset($user_states[$chat_id]);
                    }
                    
                    $telegram->sendMessage([
                        'chat_id' => $chat_id,
                        'text' => "👋 Добро пожаловать в систему подбора резюме!\n\nНажмите кнопку ниже, чтобы начать:",
                        'reply_markup' => LanguageKeyboard::getMainMenu()
                    ]);
                    echo "✅ Обработан /start от $chat_id\n";
                    continue;
                }
                
                $is_in_process = isset($user_states[$chat_id]) && isset($user_states[$chat_id]['step']);
                
                // Обработка кнопки "Оставить резюме"
                if (LanguageKeyboard::isResumeButton($user_text)) {
                    try {
                        $telegram->deleteMessage([
                            'chat_id' => $chat_id,
                            'message_id' => $message_id
                        ]);
                    } catch (\Exception $e) {
                        echo "⚠️ Не удалось удалить сообщение: " . $e->getMessage() . "\n";
                    }
                    
                    $user_states[$chat_id] = [
                        'state' => 'choosing_language'
                    ];
                    
                    $telegram->sendMessage([
                        'chat_id' => $chat_id,
                        'text' => "Выберите язык:",
                        'reply_markup' => LanguageKeyboard::getLanguageKeyboard()
                    ]);
                    echo "✅ Показан выбор языка для $chat_id\n";
                    continue;
                }
                
                // Обработка выбора языка
                if (LanguageKeyboard::isLanguageButton($user_text) && 
                    isset($user_states[$chat_id]) && 
                    $user_states[$chat_id]['state'] === 'choosing_language') {
                    
                    try {
                        $telegram->deleteMessage([
                            'chat_id' => $chat_id,
                            'message_id' => $message_id
                        ]);
                    } catch (\Exception $e) {
                        echo "⚠️ Не удалось удалить сообщение: " . $e->getMessage() . "\n";
                    }
                    
                    if ($user_text === '🇷🇺 Русский') {
                        $user_states[$chat_id] = [
                            'state' => 'waiting_for_name',
                            'step' => 1,
                            'language' => 'ru'
                        ];
                        
                        $text = RuInfoHandler::getStartMessage();
                        $keyboard = LanguageKeyboard::getBackKeyboard();
                        
                        $telegram->sendMessage([
                            'chat_id' => $chat_id,
                            'text' => $text,
                            'reply_markup' => $keyboard
                        ]);
                        echo "✅ Выбран русский язык, начат процесс от $chat_id\n";
                    } elseif ($user_text === "🇺🇿 O'zbekcha") {
                        $user_states[$chat_id] = [
                            'state' => 'waiting_for_name',
                            'step' => 1,
                            'language' => 'uz'
                        ];
                        
                        $text = UzInfoHandler::getStartMessage();
                        $keyboard = \App\Keyboards\uz\LanguageKeyboard::getBackKeyboard();
                        
                        $telegram->sendMessage([
                            'chat_id' => $chat_id,
                            'text' => $text,
                            'reply_markup' => $keyboard
                        ]);
                        echo "✅ Выбран узбекский язык, начат процесс от $chat_id\n";
                    }
                    
                    continue;
                }
                
                // Неизвестная команда и юзер не в процессе
                if (!$is_in_process && 
                    (!isset($user_states[$chat_id]) || $user_states[$chat_id]['state'] !== 'choosing_language')) {
                    
                    try {
                        $telegram->deleteMessage([
                            'chat_id' => $chat_id,
                            'message_id' => $message_id
                        ]);
                    } catch (\Exception $e) {
                        echo "⚠️ Не удалось удалить сообщение: " . $e->getMessage() . "\n";
                    }
                    
                    $telegram->sendMessage([
                        'chat_id' => $chat_id,
                        'text' => "❌ Неправильный выбор. Пожалуйста, используйте кнопку:",
                        'reply_markup' => LanguageKeyboard::getMainMenu()
                    ]);
                    echo "⚠️ Неправильный выбор от $chat_id: $user_text\n";
                    continue;
                }
                
                // Юзер на выборе языка но ввёл не то
                if (isset($user_states[$chat_id]) && 
                    $user_states[$chat_id]['state'] === 'choosing_language' &&
                    !LanguageKeyboard::isLanguageButton($user_text)) {
                    
                    try {
                        $telegram->deleteMessage([
                            'chat_id' => $chat_id,
                            'message_id' => $message_id
                        ]);
                    } catch (\Exception $e) {
                        echo "⚠️ Не удалось удалить сообщение: " . $e->getMessage() . "\n";
                    }
                    
                    $telegram->sendMessage([
                        'chat_id' => $chat_id,
                        'text' => "❌ Неправильный выбор. Пожалуйста, выберите язык:",
                        'reply_markup' => LanguageKeyboard::getLanguageKeyboard()
                    ]);
                    echo "⚠️ Неправильный выбор языка от $chat_id: $user_text\n";
                    continue;
                }
                
                // Юзер в процессе заполнения — передаём обработку
                if ($is_in_process) {
                    $user_state = $user_states[$chat_id];
                    
                    switch ($user_state['language']) {
                        case 'ru':
                            RuInfoHandler::handleUserInput($telegram, $chat_id, $user_text, $message_id, $user_states);
                            break;
                            
                        case 'uz':
                            UzInfoHandler::handleUserInput($telegram, $chat_id, $user_text, $message_id, $user_states);
                            break;
                    }
                }
            }
        }
    } catch (\Exception $e) {
        echo "❌ Ошибка: " . $e->getMessage() . "\n";
        sleep(2);
    }
}