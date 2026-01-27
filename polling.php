    <?php
    require_once 'vendor/autoload.php';

    use Telegram\Bot\Api;
    use App\BotSettings;
    use App\Keyboards\LanguageKeyboard;
    use App\Info\RuInfoHandler;
use App\Keyboards\NameKeyboard;

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
                
                // Обработка callback кнопок - ВАЖНО: сначала отвечаем на callback
                if (isset($update['callback_query'])) {
                    
                    $callback = $update['callback_query'];
                    $chat_id = $callback['message']['chat']['id'];
                    $message_id = $callback['message']['message_id'];
                    $data = $callback['data'];
                    
                    
                    try {
                        $telegram->answerCallbackQuery([
                            'callback_query_id' => $callback['id']
                        ]);
                    } catch (\Exception $e) {
                        // Игнорируем ошибки ответа на callback, но логируем
                        echo "⚠️ Callback ответ не отправлен: " . $e->getMessage() . "\n";
                        // Продолжаем выполнение
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
                            $text = RuInfoHandler::getStartMessage();
                            $keyboard = LanguageKeyboard::getBackKeyboard();
                            
                            try {
                                $telegram->editMessageText([
                                    'chat_id' => $chat_id,
                                    'message_id' => $message_id,
                                    'text' => $text,
                                    'reply_markup' => $keyboard 
                                ]);
                                echo "✅ Обработан callback $data от $chat_id\n";
                            } catch (\Exception $e) {
                                echo "❌ Ошибка редактирования сообщения: " . $e->getMessage() . "\n";
                                // Если не удалось отредактировать, отправляем новое
                                $telegram->sendMessage([
                                    'chat_id' => $chat_id,
                                    'text' => $text,
                                    'reply_markup' => $keyboard 
                                ]);
                            }
                            break;
                            
                        case 'lang_uz':
                            $text = "✅ Til tanlandi: O'zbekcha";
                            $keyboard = LanguageKeyboard::getBackKeyboard();
                            
                            try {
                                $telegram->editMessageText([
                                    'chat_id' => $chat_id,
                                    'message_id' => $message_id,
                                    'text' => $text,
                                    'reply_markup' => $keyboard 
                                ]);
                                echo "✅ Обработан callback $data от $chat_id\n";
                            } catch (\Exception $e) {
                                echo "❌ Ошибка редактирования сообщения: " . $e->getMessage() . "\n";
                                $telegram->sendMessage([
                                    'chat_id' => $chat_id,
                                    'text' => $text,
                                    'reply_markup' => $keyboard 
                                ]);
                            }
                            break;
                            
                      
                            
                        case 'back_to_language':
                            // Сбрасываем состояние пользователя
                            if (isset($user_states[$chat_id])) {
                                unset($user_states[$chat_id]);
                            }
                            
                            $text = 'Выберите язык:';
                            $keyboard = LanguageKeyboard::getLanguageKeyboard();
                            
                            try {
                                $telegram->editMessageText([
                                    'chat_id' => $chat_id,
                                    'message_id' => $message_id,
                                    'text' => $text,
                                    'reply_markup' => $keyboard 
                                ]);
                                echo "✅ Обработан callback $data от $chat_id\n";
                            } catch (\Exception $e) {
                                echo "❌ Ошибка редактирования сообщения: " . $e->getMessage() . "\n";
                                $telegram->sendMessage([
                                    'chat_id' => $chat_id,
                                    'text' => $text,
                                    'reply_markup' => $keyboard 
                                ]);
                            }
                            break;
                       case 'back_to_name':
                            // Возвращаемся к вводу имени
                            if (isset($user_states[$chat_id])) {
                                // Сбрасываем шаг к 1 (ввод имени)
                                $user_states[$chat_id]['step'] = 1;
                            } else {
                                // Если состояние потеряно, создаем новое
                                $user_states[$chat_id] = [
                                    'state' => 'waiting_for_name',
                                    'step' => 1,
                                    'language' => 'ru' // Предполагаем русский язык
                                ];
                            }
                            
                            $text = "Введите ваше ФИО:";
                            $keyboard = LanguageKeyboard::getBackKeyboard(); // Используем LanguageKeyboard для шага 1
                            
                            try {
                                $telegram->editMessageText([
                                    'chat_id' => $chat_id,
                                    'message_id' => $message_id,
                                    'text' => $text,
                                    'reply_markup' => $keyboard 
                                ]);
                                echo "✅ Обработан callback $data от $chat_id\n";
                            } catch (\Exception $e) {
                                echo "❌ Ошибка редактирования сообщения: " . $e->getMessage() . "\n";
                                $telegram->sendMessage([
                                    'chat_id' => $chat_id,
                                    'text' => $text,
                                    'reply_markup' => $keyboard 
                                ]);
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
                        continue; // Пропускаем обработку, т.к. /start уже обработан выше
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
            // Добавляем небольшую паузу при ошибках
            sleep(2);
        }

      
    }