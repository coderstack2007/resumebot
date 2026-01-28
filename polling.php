<?php
require_once 'vendor/autoload.php';

use Telegram\Bot\Api;
use App\BotSettings;
use App\Keyboards\LanguageKeyboard;
use App\Info\RuInfoHandler;
use App\Backs\BackHandler;

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
            $chat_id = null;
            
            // Определяем chat_id из сообщения или callback
            if (isset($update['message'])) {
                $chat_id = $update['message']['chat']['id'];
            } elseif (isset($update['callback_query'])) {
                $chat_id = $update['callback_query']['message']['chat']['id'];
            }
            
            // Если chat_id не найден, пропускаем
            if (!$chat_id) {
                continue;
            }
            
            // Обработка /start
            if (isset($update['message']) && 
                isset($update['message']['text']) && 
                strtolower($update['message']['text']) === '/start') {
                
                $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => 'Выберите язык:',
                    'reply_markup' => LanguageKeyboard::getLanguageKeyboard()
                ]);
                echo "✅ Обработан /start от $chat_id\n";
            }
            
            // Обработка callback кнопок
            if (isset($update['callback_query'])) {
                
                $callback = $update['callback_query'];
                $chat_id = $callback['message']['chat']['id'];
                $message_id = $callback['message']['message_id'];
                $data = $callback['data'];
                
                // Отвечаем на callback
                try {
                    $telegram->answerCallbackQuery([
                        'callback_query_id' => $callback['id']
                    ]);
                } catch (\Exception $e) {
                    echo "⚠️ Callback ответ не отправлен: " . $e->getMessage() . "\n";
                }
                
                // Теперь обрабатываем логику
                switch ($data) {
                    case 'lang_ru':
                        // Устанавливаем состояние пользователя
                        if (!isset($user_states[$chat_id])) {
                            $user_states[$chat_id] = [
                                'state' => 'waiting_for_name',
                                'step' => 1,
                                'language' => 'ru'
                            ];
                        }
                        
                        // Удаляем сообщение с выбором языка
                        try {
                            $telegram->deleteMessage([
                                'chat_id' => $chat_id,
                                'message_id' => $message_id
                            ]);
                        } catch (\Exception $e) {
                            echo "⚠️ Не удалось удалить сообщение: " . $e->getMessage() . "\n";
                        }
                        
                        // Отправляем новое сообщение
                        $text = RuInfoHandler::getStartMessage();
                        $keyboard = LanguageKeyboard::getBackKeyboard();
                        
                        $telegram->sendMessage([
                            'chat_id' => $chat_id,
                            'text' => $text,
                            'reply_markup' => $keyboard 
                        ]);
                        echo "✅ Обработан callback $data от $chat_id\n";
                        break;
                        
                    case 'lang_uz':
                        // Удаляем сообщение с выбором языка
                        try {
                            $telegram->deleteMessage([
                                'chat_id' => $chat_id,
                                'message_id' => $message_id
                            ]);
                        } catch (\Exception $e) {
                            echo "⚠️ Не удалось удалить сообщение: " . $e->getMessage() . "\n";
                        }
                        
                        $text = "✅ Til tanlandi: O'zbekcha";
                        $keyboard = LanguageKeyboard::getBackKeyboard();
                        
                        $telegram->sendMessage([
                            'chat_id' => $chat_id,
                            'text' => $text,
                            'reply_markup' => $keyboard 
                        ]);
                        echo "✅ Обработан callback $data от $chat_id\n";
                        break;
                        
                     default:
                        // Проверяем, является ли callback кнопкой "Назад" или "На главную"
                        if (strpos($data, 'back_') === 0 || $data === 'main_menu') {
                            // Удаляем сообщение перед обработкой
                            try {
                                $telegram->deleteMessage([
                                    'chat_id' => $chat_id,
                                    'message_id' => $message_id
                                ]);
                            } catch (\Exception $e) {
                                echo "⚠️ Не удалось удалить сообщение: " . $e->getMessage() . "\n";
                            }
                            
                            $result = BackHandler::handleBackCallback($telegram, $chat_id, $message_id, $data, $user_states);
                            if ($result) {
                                echo "✅ Обработан callback $data от $chat_id\n";
                            } else {
                                echo "❌ Ошибка обработки $data от $chat_id\n";
                            }
                        }
                        
                        // Проверяем, является ли callback выбором региона
                        elseif (strpos($data, 'region_') === 0) {
                            // Удаляем сообщение с регионами
                            try {
                                $telegram->deleteMessage([
                                    'chat_id' => $chat_id,
                                    'message_id' => $message_id
                                ]);
                            } catch (\Exception $e) {
                                echo "⚠️ Не удалось удалить сообщение: " . $e->getMessage() . "\n";
                            }
                            
                            $result = RuInfoHandler::handleRegionCallback($telegram, $chat_id, $data, $user_states);
                            if ($result) {
                                echo "✅ Обработан callback $data от $chat_id\n";
                            } else {
                                echo "❌ Ошибка обработки $data от $chat_id\n";
                            }
                        }
                        
                        // Проверяем, является ли callback выбором города
                        elseif (strpos($data, 'city_') === 0) {
                            // Удаляем сообщение с городами
                            try {
                                $telegram->deleteMessage([
                                    'chat_id' => $chat_id,
                                    'message_id' => $message_id
                                ]);
                            } catch (\Exception $e) {
                                echo "⚠️ Не удалось удалить сообщение: " . $e->getMessage() . "\n";
                            }
                            
                            $result = RuInfoHandler::handleCityCallback($telegram, $chat_id, $data, $user_states);
                            if ($result) {
                                echo "✅ Обработан callback $data от $chat_id\n";
                            } else {
                                echo "❌ Ошибка обработки $data от $chat_id\n";
                            }
                        }
                        
                        // Если callback не распознан
                        else {
                            echo "⚠️ Неизвестный callback: $data от $chat_id\n";
                        }
                        break;
                }
            }
            
            // Обработка ввода данных пользователем
            if (isset($update['message']) && 
                isset($update['message']['text']) && 
                isset($user_states[$chat_id])) {
                
                $user_text = trim($update['message']['text']);
                $user_state = $user_states[$chat_id];
                
                // Проверяем, что это не команда /start
                if (strtolower($user_text) === '/start') {
                    continue;
                }
                
                // Обработка в зависимости от выбранного языка
                switch ($user_state['language']) {
                    case 'ru':
                        RuInfoHandler::handleUserInput($telegram, $chat_id, $user_text, $user_states);
                        break;
                        
                    // Добавьте обработку других языков здесь
                }
            }
        }
    } catch (\Exception $e) {
        echo "❌ Ошибка: " . $e->getMessage() . "\n";
        sleep(2);
    }
}