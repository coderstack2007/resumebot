<?php
require_once 'vendor/autoload.php';

use App\Database;

echo "=====================================\n";
echo "   ПРОСМОТР СОХРАНЕННЫХ РЕЗЮМЕ\n";
echo "=====================================\n\n";

try {
    $db = Database::getInstance();
    
    // Получаем все резюме
    $resumes = $db->getAllResumes();
    
    if (empty($resumes)) {
        echo "📋 База данных пуста. Резюме еще не были сохранены.\n";
    } else {
        echo "📊 Всего резюме в базе: " . count($resumes) . "\n\n";
        
        foreach ($resumes as $resume) {
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "ID резюме: #" . $resume['id'] . "\n";
            echo "Chat ID: " . $resume['chat_id'] . "\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "👤 ФИО: " . $resume['name'] . "\n";
            echo "🎂 Возраст: " . $resume['age'] . " лет\n";
            echo "📱 Телефон: " . $resume['phone'] . "\n";
            echo "📸 Фото: " . ($resume['photo_filename'] ?? 'не указано') . "\n";
            echo "📍 Регион: " . $resume['region_name'] . " (ID: " . $resume['region_id'] . ")\n";
            echo "🏙 Город: " . $resume['city_name'] . " (ID: " . $resume['city_id'] . ")\n";
            echo "💼 Вакансия: " . $resume['job_name'] . " (ID: " . $resume['job_id'] . ")\n";
            echo "🌐 Язык: " . $resume['language'] . "\n";
            echo "📅 Создано: " . $resume['created_at'] . "\n";
            echo "\n";
        }
        
        // Показываем статистику
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "           СТАТИСТИКА\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        
        $stats = $db->getStatistics();
        
        if ($stats) {
            echo "📈 Общее количество: " . $stats['total'] . "\n\n";
            
            echo "По языкам:\n";
            foreach ($stats['by_language'] as $lang) {
                $lang_name = $lang['language'] === 'ru' ? 'Русский' : 'Узбекский';
                echo "  • $lang_name: " . $lang['count'] . "\n";
            }
            
            echo "\nТоп-5 вакансий:\n";
            $top_jobs = array_slice($stats['by_job'], 0, 5);
            foreach ($top_jobs as $job) {
                echo "  • " . $job['job_name'] . ": " . $job['count'] . "\n";
            }
            
            echo "\nТоп-5 городов:\n";
            $top_cities = array_slice($stats['by_city'], 0, 5);
            foreach ($top_cities as $city) {
                echo "  • " . $city['city_name'] . ": " . $city['count'] . "\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n=====================================\n";