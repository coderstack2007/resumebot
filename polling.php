<?php
require_once 'vendor/autoload.php';

use Telegram\Bot\Api;
use App\BotSettings;
use App\Keyboards\ru\LanguageKeyboard;
use App\Keyboards\VacanciesKeyboard;
use App\Info\RuInfoHandler;
use App\Info\UzInfoHandler;

// ── Настройки долгой работы ───────────────────────────────────
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '256M');

// ── Логирование ошибок ────────────────────────────────────────
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/polling_errors.log');

// ─────────────────────────────────────────────────────────────
// Вспомогательные функции
// ─────────────────────────────────────────────────────────────

function logDebug($message, $data = null): void
{
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }

    $timestamp   = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";

    if ($data !== null) {
        $log_message .= ' | Data: ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    file_put_contents($log_dir . '/debug.log', $log_message . PHP_EOL, FILE_APPEND);
}

function validateUserState($chat_id, $user_state, $step_name = ''): array
{
    $issues = [];

    if (!is_array($user_state)) {
        logDebug("VALIDATION ERROR: State is not array", ['chat_id' => $chat_id, 'step' => $step_name]);
        return ["User state is not an array"];
    }

    if (!isset($user_state['step']))     { $issues[] = "Missing 'step' field"; }
    if (!isset($user_state['language'])) { $issues[] = "Missing 'language' field"; }

    if (isset($user_state['step'])) {
        $step = $user_state['step'];

        if ($step == 6 && !isset($user_state['region_id'])) {
            $issues[] = "Step 6 (city selection) but 'region_id' is missing";
            logDebug("CRITICAL: Step 6 without region_id", ['chat_id' => $chat_id, 'state' => $user_state]);
        }
        if ($step == 7) {
            if (!isset($user_state['region_id'])) { $issues[] = "Step 7 but 'region_id' is missing"; }
            if (!isset($user_state['city_id']))   { $issues[] = "Step 7 but 'city_id' is missing"; }
        }
    }

    if (!empty($issues)) {
        logDebug("VALIDATION ISSUES", [
            'chat_id' => $chat_id,
            'step'    => $step_name,
            'issues'  => $issues,
            'state'   => $user_state,
        ]);
    }

    return $issues;
}

// ─────────────────────────────────────────────────────────────
// Запуск бота
// ─────────────────────────────────────────────────────────────

$telegram = new Api(BotSettings::TOKEN);

echo "🤖 Бот запущен в режиме polling...\n";
logDebug("=== BOT STARTED ===");

$last_update_id = 0;
$user_states    = [];

while (true) {
    try {
        $updates = $telegram->getUpdates([
            'offset'  => $last_update_id + 1,
            'limit'   => 10,
            'timeout' => 30,
        ]);

        foreach ($updates as $update) {
            $last_update_id = $update['update_id'];

            // ══════════════════════════════════════════════════════
            // Обработка CONTACT
            // ══════════════════════════════════════════════════════
            if (isset($update['message']['contact'])) {
                $chat_id    = $update['message']['chat']['id'];
                $contact    = $update['message']['contact'];
                $message_id = $update['message']['message_id'];

                logDebug("CONTACT received", ['chat_id' => $chat_id]);

                if (isset($user_states[$chat_id]['step'])) {
                    validateUserState($chat_id, $user_states[$chat_id], 'before_contact');
                    $lang = $user_states[$chat_id]['language'] ?? '';

                    if ($lang === 'ru') {
                        RuInfoHandler::handleContact($telegram, $chat_id, $contact, $message_id, $user_states);
                    } elseif ($lang === 'uz') {
                        UzInfoHandler::handleContact($telegram, $chat_id, $contact, $message_id, $user_states);
                    }

                    if (isset($user_states[$chat_id])) {
                        validateUserState($chat_id, $user_states[$chat_id], 'after_contact');
                    }
                }

                echo "✅ Обработан contact от $chat_id\n";
                continue;
            }

            // ══════════════════════════════════════════════════════
            // Обработка ФОТО
            // ══════════════════════════════════════════════════════
            if (isset($update['message']['photo'])) {
                $chat_id     = $update['message']['chat']['id'];
                $photo_array = $update['message']['photo'];
                $message_id  = $update['message']['message_id'];

                logDebug("PHOTO received", ['chat_id' => $chat_id, 'photo_count' => count($photo_array)]);

                if (isset($user_states[$chat_id]['step'])) {
                    validateUserState($chat_id, $user_states[$chat_id], 'before_photo');
                    $lang = $user_states[$chat_id]['language'] ?? '';

                    if ($lang === 'ru') {
                        RuInfoHandler::handlePhoto($telegram, $chat_id, $photo_array, $message_id, $user_states);
                    } elseif ($lang === 'uz') {
                        UzInfoHandler::handlePhoto($telegram, $chat_id, $photo_array, $message_id, $user_states);
                    }

                    if (isset($user_states[$chat_id])) {
                        validateUserState($chat_id, $user_states[$chat_id], 'after_photo');
                        logDebug("State after photo", [
                            'chat_id'  => $chat_id,
                            'new_step' => $user_states[$chat_id]['step'] ?? 'unknown',
                        ]);
                    }
                }

                echo "✅ Обработано фото от $chat_id\n";
                continue;
            }

            // ══════════════════════════════════════════════════════
            // Обработка ТЕКСТОВЫХ СООБЩЕНИЙ
            // ══════════════════════════════════════════════════════
            if (!isset($update['message']['text'])) {
                continue; // не текст, не фото, не контакт — игнорируем
            }

            $chat_id    = $update['message']['chat']['id'];
            $user_text  = trim($update['message']['text']);
            $message_id = $update['message']['message_id'];

            logDebug("TEXT received", ['chat_id' => $chat_id, 'text' => mb_substr($user_text, 0, 50)]);

            // ── /start ────────────────────────────────────────────
            if (strtolower($user_text) === '/start') {
                if (isset($user_states[$chat_id])) {
                    logDebug("State reset by /start", ['chat_id' => $chat_id, 'old_state' => $user_states[$chat_id]]);
                    unset($user_states[$chat_id]);
                }
                $telegram->sendMessage([
                    'chat_id'      => $chat_id,
                    'text'         => "👋 Добро пожаловать в систему подбора резюме!\n\nНажмите кнопку ниже, чтобы начать:",
                    'reply_markup' => LanguageKeyboard::getMainMenu(),
                ]);
                echo "✅ /start от $chat_id\n";
                continue;
            }

            // Текущее состояние пользователя
            $state        = $user_states[$chat_id]['state'] ?? null;
            $is_in_process = isset($user_states[$chat_id]['step']);

            // ══════════════════════════════════════════════════════
            // ШАГ 1: Кнопка "Оставить резюме" → показать вакансии
            // ══════════════════════════════════════════════════════
            if (LanguageKeyboard::isResumeButton($user_text)) {
                try { $telegram->deleteMessage(['chat_id' => $chat_id, 'message_id' => $message_id]); }
                catch (\Exception $e) { logDebug("Delete failed", ['error' => $e->getMessage()]); }

                $user_states[$chat_id] = ['state' => 'choosing_vacancy'];

                logDebug("Resume button → show vacancies", ['chat_id' => $chat_id]);

                $telegram->sendMessage([
                    'chat_id'      => $chat_id,
                    'text'         => "📋 Выберите вакансию, на которую хотите подать резюме:",
                    'reply_markup' => VacanciesKeyboard::getVacanciesKeyboard(),
                ]);
                echo "✅ Показаны вакансии для $chat_id\n";
                continue;
            }

            // ══════════════════════════════════════════════════════
            // ШАГ 2: Пользователь на экране выбора вакансии
            // ══════════════════════════════════════════════════════
            if ($state === 'choosing_vacancy') {

                // Кнопка "Назад" → главное меню
                if (VacanciesKeyboard::isBackButton($user_text)) {
                    try { $telegram->deleteMessage(['chat_id' => $chat_id, 'message_id' => $message_id]); }
                    catch (\Exception $e) {}

                    unset($user_states[$chat_id]);

                    $telegram->sendMessage([
                        'chat_id'      => $chat_id,
                        'text'         => "👋 Добро пожаловать в систему подбора резюме!\n\nНажмите кнопку ниже, чтобы начать:",
                        'reply_markup' => LanguageKeyboard::getMainMenu(),
                    ]);
                    echo "✅ Назад → главное меню для $chat_id\n";
                    continue;
                }

                // Пользователь выбрал вакансию
                $vacancy_id = VacanciesKeyboard::matchVacancy($user_text);

                if ($vacancy_id !== false) {
                    try { $telegram->deleteMessage(['chat_id' => $chat_id, 'message_id' => $message_id]); }
                    catch (\Exception $e) {}

                    $user_states[$chat_id] = [
                        'state'      => 'choosing_language',
                        'vacancy_id' => $vacancy_id,
                    ];

                    logDebug("Vacancy selected", ['chat_id' => $chat_id, 'vacancy_id' => $vacancy_id]);

                    $telegram->sendMessage([
                        'chat_id'      => $chat_id,
                        'text'         => "✅ Вакансия выбрана!\n\nВыберите язык заполнения:",
                        'reply_markup' => LanguageKeyboard::getLanguageKeyboard(),
                    ]);
                    echo "✅ Вакансия $vacancy_id выбрана, показан выбор языка для $chat_id\n";
                } else {
                    // Что-то непонятное на экране вакансий
                    $telegram->sendMessage([
                        'chat_id'      => $chat_id,
                        'text'         => "❌ Пожалуйста, выберите вакансию из списка ниже:",
                        'reply_markup' => VacanciesKeyboard::getVacanciesKeyboard(),
                    ]);
                }
                continue;
            }

            // ══════════════════════════════════════════════════════
            // ШАГ 3: Пользователь выбирает язык
            // ══════════════════════════════════════════════════════
            if ($state === 'choosing_language' && LanguageKeyboard::isLanguageButton($user_text)) {
                try { $telegram->deleteMessage(['chat_id' => $chat_id, 'message_id' => $message_id]); }
                catch (\Exception $e) {}

                // Сохраняем vacancy_id из предыдущего шага
                $vacancy_id = $user_states[$chat_id]['vacancy_id'] ?? null;

                if ($user_text === '🇷🇺 Русский') {
                    $user_states[$chat_id] = [
                        'state'      => 'waiting_for_name',
                        'step'       => 1,
                        'language'   => 'ru',
                        'vacancy_id' => $vacancy_id,
                    ];
                    $telegram->sendMessage([
                        'chat_id'      => $chat_id,
                        'text'         => RuInfoHandler::getStartMessage(),
                        'reply_markup' => LanguageKeyboard::getBackKeyboard(),
                    ]);
                    echo "✅ Выбран RU для $chat_id (vacancy: $vacancy_id)\n";

                } elseif ($user_text === "🇺🇿 O'zbekcha") {
                    $user_states[$chat_id] = [
                        'state'      => 'waiting_for_name',
                        'step'       => 1,
                        'language'   => 'uz',
                        'vacancy_id' => $vacancy_id,
                    ];
                    $telegram->sendMessage([
                        'chat_id'      => $chat_id,
                        'text'         => UzInfoHandler::getStartMessage(),
                        'reply_markup' => \App\Keyboards\uz\LanguageKeyboard::getBackKeyboard(),
                    ]);
                    echo "✅ Выбран UZ для $chat_id (vacancy: $vacancy_id)\n";
                }
                continue;
            }

            // ══════════════════════════════════════════════════════
            // Пользователь на выборе языка, но ввёл не то
            // ══════════════════════════════════════════════════════
            if ($state === 'choosing_language' && !LanguageKeyboard::isLanguageButton($user_text)) {
                try { $telegram->deleteMessage(['chat_id' => $chat_id, 'message_id' => $message_id]); }
                catch (\Exception $e) {}

                $telegram->sendMessage([
                    'chat_id'      => $chat_id,
                    'text'         => "❌ Пожалуйста, выберите язык из списка:",
                    'reply_markup' => LanguageKeyboard::getLanguageKeyboard(),
                ]);
                echo "⚠️ Неправильный выбор языка от $chat_id: $user_text\n";
                continue;
            }

            // ══════════════════════════════════════════════════════
            // Неизвестная команда, пользователь НЕ в процессе
            // ══════════════════════════════════════════════════════
            if (!$is_in_process) {
                try { $telegram->deleteMessage(['chat_id' => $chat_id, 'message_id' => $message_id]); }
                catch (\Exception $e) {}

                $telegram->sendMessage([
                    'chat_id'      => $chat_id,
                    'text'         => "❌ Неправильный выбор. Пожалуйста, используйте кнопку:",
                    'reply_markup' => LanguageKeyboard::getMainMenu(),
                ]);
                echo "⚠️ Неправильный выбор от $chat_id: $user_text\n";
                continue;
            }

            // ══════════════════════════════════════════════════════
            // ПОЛЬЗОВАТЕЛЬ В ПРОЦЕССЕ ЗАПОЛНЕНИЯ (step 1-N)
            // ══════════════════════════════════════════════════════
            if ($is_in_process) {
                $user_state = $user_states[$chat_id];

                // Валидация перед обработкой
                $validation_issues = validateUserState($chat_id, $user_state, 'before_handler');
                if (!empty($validation_issues)) {
                    logDebug("CRITICAL: Validation failed before handler", [
                        'chat_id'   => $chat_id,
                        'issues'    => $validation_issues,
                        'state'     => $user_state,
                        'user_text' => $user_text,
                    ]);
                }

                // Специальные проверки для шагов 5-6 (регион/город)
                if (in_array($user_state['step'] ?? 0, [5, 6])) {
                    logDebug("=== CITY SELECTION PROCESS ===", [
                        'chat_id'       => $chat_id,
                        'step'          => $user_state['step'],
                        'user_text'     => $user_text,
                        'has_region_id' => isset($user_state['region_id']),
                        'region_id'     => $user_state['region_id'] ?? null,
                    ]);

                    // Шаг 5 — выбор региона
                    if ($user_state['step'] == 5) {
                        try {
                            $regions   = ($user_state['language'] === 'ru')
                                ? \App\Cities\ru\Cities::getRegions()
                                : \App\Cities\uz\Cities::getRegions();
                            $region_id = array_search($user_text, $regions);
                            logDebug("Region search", [
                                'found'     => $region_id !== false,
                                'region_id' => $region_id,
                            ]);
                        } catch (\Exception $e) {
                            logDebug("ERROR loading regions", ['error' => $e->getMessage()]);
                        }
                    }

                    // Шаг 6 — выбор города
                    if ($user_state['step'] == 6) {
                        if (!isset($user_state['region_id'])) {
                            logDebug("CRITICAL: Step 6 without region_id!", ['chat_id' => $chat_id]);
                            $telegram->sendMessage([
                                'chat_id'      => $chat_id,
                                'text'         => "❌ Ошибка. Выберите регион заново:",
                                'reply_markup' => ($user_state['language'] === 'ru')
                                    ? \App\Keyboards\ru\CitiesKeyboard::getRegionsKeyboard()
                                    : \App\Keyboards\uz\CitiesKeyboard::getRegionsKeyboard(),
                            ]);
                            $user_states[$chat_id]['step'] = 5;
                            continue;
                        }

                        try {
                            $cities  = ($user_state['language'] === 'ru')
                                ? \App\Cities\ru\Cities::getCitiesByRegion($user_state['region_id'])
                                : \App\Cities\uz\Cities::getCitiesByRegion($user_state['region_id']);
                            $city_id = array_search($user_text, $cities);
                            logDebug("City search", [
                                'region_id' => $user_state['region_id'],
                                'found'     => $city_id !== false,
                                'city_id'   => $city_id,
                            ]);
                        } catch (\Exception $e) {
                            logDebug("ERROR loading cities", ['error' => $e->getMessage()]);
                        }
                    }
                }

                // Передаём обработку handler'у
                try {
                    $lang = $user_state['language'];

                    logDebug("Calling handler", ['chat_id' => $chat_id, 'lang' => $lang, 'step' => $user_state['step']]);

                    if ($lang === 'ru') {
                        $result = RuInfoHandler::handleUserInput($telegram, $chat_id, $user_text, $message_id, $user_states);
                    } elseif ($lang === 'uz') {
                        $result = UzInfoHandler::handleUserInput($telegram, $chat_id, $user_text, $message_id, $user_states);
                    }

                    // Валидация после обработки
                    if (isset($user_states[$chat_id])) {
                        $after_issues = validateUserState($chat_id, $user_states[$chat_id], 'after_handler');
                        if (!empty($after_issues)) {
                            logDebug("CRITICAL: Validation failed after handler", [
                                'chat_id' => $chat_id,
                                'issues'  => $after_issues,
                            ]);
                        }
                        logDebug("State after handler", [
                            'chat_id'       => $chat_id,
                            'new_step'      => $user_states[$chat_id]['step'] ?? 'unknown',
                            'has_region_id' => isset($user_states[$chat_id]['region_id']),
                            'has_city_id'   => isset($user_states[$chat_id]['city_id']),
                        ]);
                    } else {
                        logDebug("State removed after handler (process completed)", ['chat_id' => $chat_id]);
                    }

                } catch (\Exception $e) {
                    logDebug("EXCEPTION in handler", [
                        'chat_id' => $chat_id,
                        'error'   => $e->getMessage(),
                        'trace'   => $e->getTraceAsString(),
                    ]);
                    $telegram->sendMessage([
                        'chat_id' => $chat_id,
                        'text'    => "❌ Произошла ошибка. Попробуйте ещё раз или нажмите /start.",
                    ]);
                }
            }

        } // foreach $updates

    } catch (\Exception $e) {
        logDebug("ERROR in main loop", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        echo "❌ Ошибка: " . $e->getMessage() . "\n";
        sleep(2);
    }
} // while true