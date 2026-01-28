<?php
require_once 'vendor/autoload.php';

use Telegram\Bot\Api;
use App\BotSettings;
use App\Keyboards\LanguageKeyboard;
use App\Info\RuInfoHandler;

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
            
            // Обработка только текстовых сообщений
            if (isset($update['message']) && isset($update['message']['text'])) {
                $chat_id = $update['message']['chat']['id'];
                $user_text = trim($update['message']['text']);
                $message_id = $update['message']['message_id'];
                
                // Обработка /start
                if (strtolower($user_text) === '/start') {
                    // Сбрасываем состояние при /start
                    if (isset($user_states[$chat_id])) {
                        unset($user_states[$chat_id]);
                    }
                    
                    $telegram->sendMessage([
                        'chat_id' => $chat_id,
                        'text' => 'Выберите язык:',
                        'reply_markup' => LanguageKeyboard::getLanguageKeyboard()
                    ]);
                    echo "✅ Обработан /start от $chat_id\n";
                    continue;
                }
                
                // Обработка выбора языка
                if ($user_text === '🇷🇺 Русский') {
                    // Удаляем сообщение пользователя
                    try {
                        $telegram->deleteMessage([
                            'chat_id' => $chat_id,
                            'message_id' => $message_id
                        ]);
                    } catch (\Exception $e) {
                        echo "⚠️ Не удалось удалить сообщение: " . $e->getMessage() . "\n";
                    }
                    
                    // Устанавливаем состояние
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
                    echo "✅ Выбран русский язык от $chat_id\n";
                    continue;
                }
                
                if ($user_text === "🇺🇿 O'zbekcha") {
                    // Удаляем сообщение пользователя
                    try {
                        $telegram->deleteMessage([
                            'chat_id' => $chat_id,
                            'message_id' => $message_id
                        ]);
                    } catch (\Exception $e) {
                        echo "⚠️ Не удалось удалить сообщение: " . $e->getMessage() . "\n";
                    }
                    
                    // Здесь добавьте обработку узбекского языка
                    $telegram->sendMessage([
                        'chat_id' => $chat_id,
                        'text' => "✅ Til tanlandi: O'zbekcha",
                        'reply_markup' => LanguageKeyboard::getBackKeyboard()
                    ]);
                    echo "✅ Выбран узбекский язык от $chat_id\n";
                    continue;
                }
                
                // Обработка ввода данных если пользователь в процессе
                if (isset($user_states[$chat_id])) {
                    $user_state = $user_states[$chat_id];
                    
                    // Обработка в зависимости от выбранного языка
                    switch ($user_state['language']) {
                        case 'ru':
                            RuInfoHandler::handleUserInput($telegram, $chat_id, $user_text, $message_id, $user_states);
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