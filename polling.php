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
            
            // Обработка фото
            if (isset($update['message']) && isset($update['message']['photo'])) {
                $chat_id = $update['message']['chat']['id'];
                $photo_array = $update['message']['photo'];
                $message_id = $update['message']['message_id'];
                
                // Проверяем, находится ли пользователь в процессе заполнения
                if (isset($user_states[$chat_id]) && isset($user_states[$chat_id]['step'])) {
                    $user_state = $user_states[$chat_id];
                    
                    // Обработка в зависимости от выбранного языка
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
            
            // Обработка только текстовых сообщений
            if (isset($update['message']) && isset($update['message']['text'])) {
                $chat_id = $update['message']['chat']['id'];
                $user_text = trim($update['message']['text']);
                $message_id = $update['message']['message_id'];
                
                // Обработка /start - показываем главное меню
                if (strtolower($user_text) === '/start') {
                    // Сбрасываем состояние при /start
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
                
                // Проверяем, находится ли пользователь в процессе заполнения резюме
                $is_in_process = isset($user_states[$chat_id]) && isset($user_states[$chat_id]['step']);
                
                // Обработка кнопки "Оставить резюме" - показываем выбор языка
                if (LanguageKeyboard::isResumeButton($user_text)) {
                    // Удаляем сообщение пользователя
                    try {
                        $telegram->deleteMessage([
                            'chat_id' => $chat_id,
                            'message_id' => $message_id
                        ]);
                    } catch (\Exception $e) {
                        echo "⚠️ Не удалось удалить сообщение: " . $e->getMessage() . "\n";
                    }
                    
                    // Устанавливаем состояние "выбор языка"
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
                
                // Обработка выбора языка (после нажатия "Оставить резюме")
                if (LanguageKeyboard::isLanguageButton($user_text) && 
                    isset($user_states[$chat_id]) && 
                    $user_states[$chat_id]['state'] === 'choosing_language') {
                    
                    // Удаляем сообщение пользователя
                    try {
                        $telegram->deleteMessage([
                            'chat_id' => $chat_id,
                            'message_id' => $message_id
                        ]);
                    } catch (\Exception $e) {
                        echo "⚠️ Не удалось удалить сообщение: " . $e->getMessage() . "\n";
                    }
                    
                    // Устанавливаем язык и начинаем процесс
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
                
                // Если получили неизвестную команду и пользователь не в процессе
                if (!$is_in_process && 
                    (!isset($user_states[$chat_id]) || $user_states[$chat_id]['state'] !== 'choosing_language')) {
                    
                    // Удаляем сообщение пользователя
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
                
                // Если пользователь на этапе выбора языка, но ввел что-то не то
                if (isset($user_states[$chat_id]) && 
                    $user_states[$chat_id]['state'] === 'choosing_language' &&
                    !LanguageKeyboard::isLanguageButton($user_text)) {
                    
                    // Удаляем сообщение пользователя
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
                
                // Обработка ввода данных если пользователь в процессе заполнения
                if ($is_in_process) {
                    $user_state = $user_states[$chat_id];
                    
                    // Обработка в зависимости от выбранного языка
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