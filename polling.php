<?php
require_once 'vendor/autoload.php';

use Telegram\Bot\Api;
use App\BotSettings;
use App\Keyboards\ru\LanguageKeyboard;
use App\Info\RuInfoHandler;
use App\Info\UzInfoHandler;

// Настройки для долгой работы
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '256M');

// Включаем логирование ошибок
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/polling_errors.log');

// Функция логирования
function logDebug($message, $data = null) {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";
    
    if ($data !== null) {
        $log_message .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    $log_message .= PHP_EOL;
    
    file_put_contents($log_dir . '/debug.log', $log_message, FILE_APPEND);
}

// Функция проверки состояния пользователя
function validateUserState($chat_id, $user_state, $step_name = '') {
    $issues = [];
    
    if (!is_array($user_state)) {
        $issues[] = "User state is not an array";
        logDebug("VALIDATION ERROR: State is not array", ['chat_id' => $chat_id, 'step' => $step_name]);
        return $issues;
    }
    
    // Проверка обязательных полей
    if (!isset($user_state['step'])) {
        $issues[] = "Missing 'step' field";
    }
    
    if (!isset($user_state['language'])) {
        $issues[] = "Missing 'language' field";
    }
    
    // Специальные проверки для шагов выбора региона/города
    if (isset($user_state['step'])) {
        $step = $user_state['step'];
        
        // Шаг 6 - выбор города, должен быть region_id
        if ($step == 6 && !isset($user_state['region_id'])) {
            $issues[] = "Step 6 (city selection) but 'region_id' is missing";
            logDebug("CRITICAL: Step 6 without region_id", [
                'chat_id' => $chat_id,
                'state' => $user_state
            ]);
        }
        
        // Шаг 7 - выбор вакансии, должны быть region_id и city_id
        if ($step == 7) {
            if (!isset($user_state['region_id'])) {
                $issues[] = "Step 7 but 'region_id' is missing";
            }
            if (!isset($user_state['city_id'])) {
                $issues[] = "Step 7 but 'city_id' is missing";
            }
        }
    }
    
    if (!empty($issues)) {
        logDebug("VALIDATION ISSUES", [
            'chat_id' => $chat_id,
            'step' => $step_name,
            'issues' => $issues,
            'state' => $user_state
        ]);
    }
    
    return $issues;
}

$telegram = new Api(BotSettings::TOKEN);

echo "🤖 Бот запущен в режиме polling...\n";
logDebug("=== BOT STARTED ===");

$last_update_id = 0;
$user_states = [];

while (true) {
    try {
        $updates = $telegram->getUpdates([
            'offset' => $last_update_id + 1,
            'limit' => 10,
            'timeout' => 30
        ]);
        
        foreach ($updates as $update) {
            $last_update_id = $update['update_id'];

            // ─── Обработка contact ───
            if (isset($update['message']) && isset($update['message']['contact'])) {
                $chat_id    = $update['message']['chat']['id'];
                $contact    = $update['message']['contact'];
                $message_id = $update['message']['message_id'];

                logDebug("CONTACT received", ['chat_id' => $chat_id]);

                if (isset($user_states[$chat_id]) && isset($user_states[$chat_id]['step'])) {
                    validateUserState($chat_id, $user_states[$chat_id], 'before_contact');
                    
                    $user_state = $user_states[$chat_id];

                    switch ($user_state['language']) {
                        case 'ru':
                            RuInfoHandler::handleContact($telegram, $chat_id, $contact, $message_id, $user_states);
                            break;
                        case 'uz':
                            UzInfoHandler::handleContact($telegram, $chat_id, $contact, $message_id, $user_states);
                            break;
                    }
                    
                    if (isset($user_states[$chat_id])) {
                        validateUserState($chat_id, $user_states[$chat_id], 'after_contact');
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
                
                logDebug("PHOTO received", [
                    'chat_id' => $chat_id,
                    'photo_count' => count($photo_array)
                ]);
                
                if (isset($user_states[$chat_id]) && isset($user_states[$chat_id]['step'])) {
                    validateUserState($chat_id, $user_states[$chat_id], 'before_photo');
                    
                    $user_state = $user_states[$chat_id];
                    
                    switch ($user_state['language']) {
                        case 'ru':
                            RuInfoHandler::handlePhoto($telegram, $chat_id, $photo_array, $message_id, $user_states);
                            break;
                            
                        case 'uz':
                            UzInfoHandler::handlePhoto($telegram, $chat_id, $photo_array, $message_id, $user_states);
                            break;
                    }
                    
                    if (isset($user_states[$chat_id])) {
                        validateUserState($chat_id, $user_states[$chat_id], 'after_photo');
                        logDebug("State after photo", [
                            'chat_id' => $chat_id,
                            'new_step' => $user_states[$chat_id]['step'] ?? 'unknown'
                        ]);
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
                
                logDebug("TEXT received", [
                    'chat_id' => $chat_id,
                    'text' => mb_substr($user_text, 0, 50)
                ]);
                
                // Обработка /start
                if (strtolower($user_text) === '/start') {
                    if (isset($user_states[$chat_id])) {
                        logDebug("User state reset by /start", [
                            'chat_id' => $chat_id,
                            'old_state' => $user_states[$chat_id]
                        ]);
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
                        logDebug("Failed to delete message", ['error' => $e->getMessage()]);
                    }
                    
                    $user_states[$chat_id] = [
                        'state' => 'choosing_language'
                    ];
                    
                    logDebug("Resume button clicked", ['chat_id' => $chat_id]);
                    
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
                        logDebug("Failed to delete message", ['error' => $e->getMessage()]);
                    }
                    
                    if ($user_text === '🇷🇺 Русский') {
                        $user_states[$chat_id] = [
                            'state' => 'waiting_for_name',
                            'step' => 1,
                            'language' => 'ru'
                        ];
                        
                        logDebug("Language selected: RU", ['chat_id' => $chat_id]);
                        
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
                        
                        logDebug("Language selected: UZ", ['chat_id' => $chat_id]);
                        
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
                        logDebug("Failed to delete message", ['error' => $e->getMessage()]);
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
                        logDebug("Failed to delete message", ['error' => $e->getMessage()]);
                    }
                    
                    $telegram->sendMessage([
                        'chat_id' => $chat_id,
                        'text' => "❌ Неправильный выбор. Пожалуйста, выберите язык:",
                        'reply_markup' => LanguageKeyboard::getLanguageKeyboard()
                    ]);
                    echo "⚠️ Неправильный выбор языка от $chat_id: $user_text\n";
                    continue;
                }
                
                // ═══════════════════════════════════════════════════════
                // ЮЗЕР В ПРОЦЕССЕ ЗАПОЛНЕНИЯ - ЗДЕСЬ ДОБАВЛЕНЫ ПРОВЕРКИ
                // ═══════════════════════════════════════════════════════
                if ($is_in_process) {
                    $user_state = $user_states[$chat_id];
                    
                    // ПРОВЕРКА СОСТОЯНИЯ ПЕРЕД ОБРАБОТКОЙ
                    $validation_issues = validateUserState($chat_id, $user_state, 'before_handler');
                    
                    if (!empty($validation_issues)) {
                        logDebug("CRITICAL: Validation failed before handler", [
                            'chat_id' => $chat_id,
                            'issues' => $validation_issues,
                            'state' => $user_state,
                            'user_text' => $user_text
                        ]);
                    }
                    
                    // СПЕЦИАЛЬНЫЕ ПРОВЕРКИ ДЛЯ ВЫБОРА ГОРОДОВ (ШАГ 5-6)
                    if (isset($user_state['step']) && ($user_state['step'] == 5 || $user_state['step'] == 6)) {
                        logDebug("=== CITY SELECTION PROCESS ===", [
                            'chat_id' => $chat_id,
                            'step' => $user_state['step'],
                            'user_text' => $user_text,
                            'has_region_id' => isset($user_state['region_id']),
                            'region_id' => $user_state['region_id'] ?? null,
                            'full_state' => $user_state
                        ]);
                        
                        // ШАГ 5 - ВЫБОР РЕГИОНА
                        if ($user_state['step'] == 5) {
                            try {
                                $regions = ($user_state['language'] === 'ru') 
                                    ? \App\Cities\ru\Cities::getRegions()
                                    : \App\Cities\uz\Cities::getRegions();
                                
                                logDebug("Regions loaded", [
                                    'count' => count($regions),
                                    'searching_for' => $user_text
                                ]);
                                
                                $region_id = array_search($user_text, $regions);
                                
                                if ($region_id === false) {
                                    logDebug("REGION NOT FOUND", [
                                        'chat_id' => $chat_id,
                                        'searched_text' => $user_text,
                                        'available_regions' => $regions
                                    ]);
                                } else {
                                    logDebug("Region found", [
                                        'region_id' => $region_id,
                                        'region_name' => $regions[$region_id]
                                    ]);
                                }
                            } catch (\Exception $e) {
                                logDebug("ERROR loading regions", [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                            }
                        }
                        
                        // ШАГ 6 - ВЫБОР ГОРОДА
                        if ($user_state['step'] == 6) {
                            // КРИТИЧЕСКАЯ ПРОВЕРКА: есть ли region_id?
                            if (!isset($user_state['region_id'])) {
                                logDebug("CRITICAL ERROR: Step 6 without region_id!", [
                                    'chat_id' => $chat_id,
                                    'state' => $user_state
                                ]);
                                
                                // Отправляем пользователя назад к выбору региона
                                $telegram->sendMessage([
                                    'chat_id' => $chat_id,
                                    'text' => "❌ Произошла ошибка. Пожалуйста, выберите регион заново:",
                                    'reply_markup' => ($user_state['language'] === 'ru')
                                        ? \App\Keyboards\ru\CitiesKeyboard::getRegionsKeyboard()
                                        : \App\Keyboards\uz\CitiesKeyboard::getRegionsKeyboard()
                                ]);
                                
                                $user_states[$chat_id]['step'] = 5;
                                continue;
                            }
                            
                            try {
                                $region_id = $user_state['region_id'];
                                
                                $cities = ($user_state['language'] === 'ru')
                                    ? \App\Cities\ru\Cities::getCitiesByRegion($region_id)
                                    : \App\Cities\uz\Cities::getCitiesByRegion($region_id);
                                
                                logDebug("Cities loaded for region", [
                                    'region_id' => $region_id,
                                    'cities_count' => count($cities),
                                    'searching_for' => $user_text
                                ]);
                                
                                if (empty($cities)) {
                                    logDebug("CRITICAL: Region has no cities!", [
                                        'region_id' => $region_id
                                    ]);
                                }
                                
                                $city_id = array_search($user_text, $cities);
                                
                                if ($city_id === false) {
                                    logDebug("CITY NOT FOUND", [
                                        'chat_id' => $chat_id,
                                        'region_id' => $region_id,
                                        'searched_text' => $user_text,
                                        'available_cities' => array_slice($cities, 0, 10) // Первые 10 для лога
                                    ]);
                                } else {
                                    logDebug("City found", [
                                        'city_id' => $city_id,
                                        'city_name' => $cities[$city_id]
                                    ]);
                                }
                            } catch (\Exception $e) {
                                logDebug("ERROR loading cities", [
                                    'region_id' => $region_id,
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                            }
                        }
                    }
                    
                    // Передаём обработку соответствующему handler'у
                    try {
                        switch ($user_state['language']) {
                            case 'ru':
                                logDebug("Calling RuInfoHandler", [
                                    'chat_id' => $chat_id,
                                    'step' => $user_state['step']
                                ]);
                                
                                $result = RuInfoHandler::handleUserInput($telegram, $chat_id, $user_text, $message_id, $user_states);
                                
                                logDebug("RuInfoHandler completed", [
                                    'chat_id' => $chat_id,
                                    'result' => $result,
                                    'user_still_exists' => isset($user_states[$chat_id])
                                ]);
                                break;
                                
                            case 'uz':
                                logDebug("Calling UzInfoHandler", [
                                    'chat_id' => $chat_id,
                                    'step' => $user_state['step']
                                ]);
                                
                                $result = UzInfoHandler::handleUserInput($telegram, $chat_id, $user_text, $message_id, $user_states);
                                
                                logDebug("UzInfoHandler completed", [
                                    'chat_id' => $chat_id,
                                    'result' => $result,
                                    'user_still_exists' => isset($user_states[$chat_id])
                                ]);
                                break;
                        }
                        
                        // ПРОВЕРКА СОСТОЯНИЯ ПОСЛЕ ОБРАБОТКИ
                        if (isset($user_states[$chat_id])) {
                            $validation_issues = validateUserState($chat_id, $user_states[$chat_id], 'after_handler');
                            
                            if (!empty($validation_issues)) {
                                logDebug("CRITICAL: Validation failed after handler", [
                                    'chat_id' => $chat_id,
                                    'issues' => $validation_issues,
                                    'new_state' => $user_states[$chat_id]
                                ]);
                            }
                            
                            logDebug("State after handler", [
                                'chat_id' => $chat_id,
                                'new_step' => $user_states[$chat_id]['step'] ?? 'unknown',
                                'has_region_id' => isset($user_states[$chat_id]['region_id']),
                                'has_city_id' => isset($user_states[$chat_id]['city_id'])
                            ]);
                        } else {
                            logDebug("User state removed (process completed?)", [
                                'chat_id' => $chat_id
                            ]);
                        }
                        
                    } catch (\Exception $e) {
                        logDebug("EXCEPTION in handler", [
                            'chat_id' => $chat_id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        
                        // Уведомляем пользователя об ошибке
                        $telegram->sendMessage([
                            'chat_id' => $chat_id,
                            'text' => "❌ Произошла ошибка. Попробуйте еще раз или нажмите /start для перезапуска."
                        ]);
                    }
                }
            }
        }
    } catch (\Exception $e) {
        logDebug("ERROR in main loop", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        echo "❌ Ошибка: " . $e->getMessage() . "\n";
        sleep(2);
    }
}