<?php
namespace App\Keyboards\ru;

use App\Jobs\Ru\Jobs;

class JobsKeyboard
{
    /**
     * Клавиатура для выбора вакансии (Reply Keyboard)
     */
    public static function getJobsKeyboard()
    {
        $jobs = Jobs::getJobs();
        $buttons = [];
        
        // Создаем кнопки по 2 в ряд
        $row = [];
        $count = 0;
        
        foreach ($jobs as $id => $name) {
            $row[] = ['text' => $name];
            $count++;
            
            // Когда накопилось 2 кнопки, добавляем ряд
            if ($count == 2) {
                $buttons[] = $row;
                $row = [];
                $count = 0;
            }
        }
        
        // Добавляем оставшиеся кнопки
        if (!empty($row)) {
            $buttons[] = $row;
        }
        
        // Добавляем кнопку "Назад к городам"
        $buttons[] = [
            ['text' => '⬅️ Назад к городам']
        ];
        
        return json_encode([
            'keyboard' => $buttons,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]);
    }
}