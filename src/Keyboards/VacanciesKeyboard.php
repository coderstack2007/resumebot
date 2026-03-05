<?php
namespace App\Keyboards;

use App\AdminDb;
use Telegram\Bot\Keyboard\Keyboard;

class VacanciesKeyboard
{
    const BACK_TEXT         = '⬅️ Назад';
    const NO_VACANCIES_TEXT = '❌ Вакансий нет';

    /**
     * Клавиатура со списком вакансий
     * Формат кнопки: "💼 Название должности | Филиал"
     */
    public static function getVacanciesKeyboard(): Keyboard
    {
        $vacancies = AdminDb::getActiveVacancies();

        $buttons = [];

        if (empty($vacancies)) {
            $buttons[] = [[
                'text' => self::NO_VACANCIES_TEXT,
            ]];
        } else {
            foreach ($vacancies as $v) {
                $buttons[] = [[
                    'text' => self::buildLabel($v),
                ]];
            }
        }

        // Кнопка "Назад" всегда последняя
        $buttons[] = [['text' => self::BACK_TEXT]];

        return Keyboard::make([
            'keyboard'          => $buttons,
            'resize_keyboard'   => true,
            'one_time_keyboard' => false,
        ]);
    }

    /**
     * Проверить, нажал ли пользователь одну из кнопок вакансий.
     * Возвращает vacancy_id (int) или false.
     */
    public static function matchVacancy(string $text): int|false
    {
        if (!str_starts_with($text, '💼 ')) {
            return false;
        }

        $vacancies = AdminDb::getActiveVacancies();

        foreach ($vacancies as $v) {
            if ($text === self::buildLabel($v)) {
                return (int) $v['id'];
            }
        }

        return false;
    }

    /**
     * Является ли текст кнопкой "Назад"
     */
    public static function isBackButton(string $text): bool
    {
        return $text === self::BACK_TEXT;
    }

    // ── Приватный хелпер: строим текст кнопки ─────────────────
    private static function buildLabel(array $v): string
    {
        $position = $v['position_name'] ?? 'Должность не указана';
        $branch   = $v['branch_name']   ?? '';

        return $branch
            ? "💼 {$position} | {$branch}"
            : "💼 {$position}";
    }
}