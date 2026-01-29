<?php
require_once 'vendor/autoload.php';

use App\Database;

echo "=====================================\n";
echo "   ЭКСПОРТ РЕЗЮМЕ В CSV\n";
echo "=====================================\n\n";

try {
    $db = Database::getInstance();
    
    // Получаем все резюме
    $resumes = $db->getAllResumes();
    
    if (empty($resumes)) {
        echo "❌ База данных пуста. Нет данных для экспорта.\n";
        exit;
    }
    
    // Создаем директорию для экспорта, если её нет
    $export_dir = dirname(__DIR__, 2) . '/exports';
    if (!file_exists($export_dir)) {
        mkdir($export_dir, 0777, true);
    }
    
    // Генерируем имя файла с датой
    $filename = 'resumes_' . date('Y-m-d_H-i-s') . '.csv';
    $filepath = $export_dir . '/' . $filename;
    
    // Открываем файл для записи
    $csv = fopen($filepath, 'w');
    
    if ($csv === false) {
        echo "❌ Ошибка: не удалось создать файл\n";
        exit;
    }
    
    // Записываем BOM для корректного отображения кириллицы в Excel
    fprintf($csv, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Записываем заголовки
    $headers = [
        'ID',
        'Chat ID',
        'ФИО',
        'Возраст',
        'Телефон',
        'Фото',
        'Регион ID',
        'Регион',
        'Город ID',
        'Город',
        'Вакансия ID',
        'Вакансия',
        'Язык',
        'Дата создания'
    ];
    fputcsv($csv, $headers, ';'); // Используем ; как разделитель для Excel
    
    // Записываем данные
    foreach ($resumes as $resume) {
        $row = [
            $resume['id'],
            $resume['chat_id'],
            $resume['name'],
            $resume['age'],
            $resume['phone'],
            $resume['photo_filename'] ?? '',
            $resume['region_id'],
            $resume['region_name'],
            $resume['city_id'],
            $resume['city_name'],
            $resume['job_id'],
            $resume['job_name'],
            $resume['language'] === 'ru' ? 'Русский' : 'Узбекский',
            $resume['created_at']
        ];
        fputcsv($csv, $row, ';');
    }
    
    fclose($csv);
    
    echo "✅ Успешно экспортировано резюме: " . count($resumes) . "\n";
    echo "📁 Файл сохранен: $filepath\n";
    echo "📊 Размер файла: " . number_format(filesize($filepath) / 1024, 2) . " KB\n\n";
    
    // Показываем статистику
    $stats = $db->getStatistics();
    
    if ($stats) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "           СТАТИСТИКА\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📈 Всего резюме: " . $stats['total'] . "\n\n";
        
        echo "По языкам:\n";
        foreach ($stats['by_language'] as $lang) {
            $lang_name = $lang['language'] === 'ru' ? 'Русский' : 'Узбекский';
            echo "  • $lang_name: " . $lang['count'] . " (" . 
                 round($lang['count'] / $stats['total'] * 100, 1) . "%)\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n=====================================\n";