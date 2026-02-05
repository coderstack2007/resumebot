<?php
require_once '/Applications/MAMP/htdocs/ResumeBot1/vendor/autoload.php';

use App\Database;

// Простая авторизация
session_start();
$admin_password = 'admin123';

if (!isset($_SESSION['admin_logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === $admin_password) {
            $_SESSION['admin_logged_in'] = true;
        } else {
            $error = 'Неверный пароль!';
        }
    }
    
    if (!isset($_SESSION['admin_logged_in'])) {
        include 'login.php';
        exit;
    }
}

// Выход
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();

// Обработка действий
$message = '';
$message_type = '';

// Удаление резюме
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $resume = $db->getResumeById($_GET['delete']);
    if ($resume && $resume['photo_filename']) {
        $photo_path = __DIR__ . '/../src/images/' . $resume['photo_filename'];
        if (file_exists($photo_path)) {
            unlink($photo_path);
        }
    }
    
    if ($db->deleteResume($_GET['delete'])) {
        $message = 'Резюме успешно удалено!';
        $message_type = 'success';
    } else {
        $message = 'Ошибка при удалении резюме!';
        $message_type = 'error';
    }
}

// Получение данных
$resumes = $db->getAllResumes();
$stats = $db->getStatistics();

// Пагинация
$per_page = 12;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_resumes = count($resumes);
$total_pages = ceil($total_resumes / $per_page);
$offset = ($page - 1) * $per_page;
$resumes_page = array_slice($resumes, $offset, $per_page);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ Панель - Резюме</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 28px;
            color: #667eea;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card i {
            font-size: 40px;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .stat-card h3 {
            font-size: 32px;
            color: #333;
            margin: 10px 0;
        }
        
        .stat-card p {
            color: #666;
            font-size: 14px;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        
        .resume-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .resume-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: all 0.3s;
            position: relative;
        }
        
        .resume-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
        }
        
        .photo-section {
            height: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .photo-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: white;
        }
        
        .resume-id-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.95);
            color: #667eea;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        .info-section {
            padding: 25px;
        }
        
        .name {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .job-title {
            text-align: center;
            color: #667eea;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            padding: 8px;
            background: #f0f3ff;
            border-radius: 8px;
        }
        
        .details {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #555;
        }
        
        .detail-row i {
            width: 20px;
            color: #667eea;
            font-size: 16px;
        }
        
        .detail-row strong {
            color: #333;
            min-width: 70px;
        }
        
        .language-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .language-badge.ru {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .language-badge.uz {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
            transform: scale(1.05);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .pagination a {
            padding: 12px 20px;
            background: #f8f9fa;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            border: 2px solid transparent;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .pagination a:hover,
        .pagination a.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        @media (max-width: 768px) {
            .resume-grid {
                grid-template-columns: 1fr;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-users"></i>
                База Резюме
            </h1>
            <a href="?logout=1" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Выход
            </a>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <i class="fas fa-file-alt"></i>
                <h3><?= $stats['total'] ?></h3>
                <p>Всего резюме</p>
            </div>
            
            <?php foreach ($stats['by_language'] as $lang): ?>
            <div class="stat-card">
                <i class="fas fa-<?= $lang['language'] === 'ru' ? 'flag' : 'globe' ?>"></i>
                <h3><?= $lang['count'] ?></h3>
                <p><?= $lang['language'] === 'ru' ? 'Русский' : 'Узбекский' ?></p>
            </div>
            <?php endforeach; ?>
            
            <div class="stat-card">
                <i class="fas fa-briefcase"></i>
                <h3><?= count($stats['by_job']) ?></h3>
                <p>Типов вакансий</p>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="message <?= $message_type ?>">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $message ?>
        </div>
        <?php endif; ?>
        
        <div class="resume-grid">
            <?php foreach ($resumes_page as $resume): ?>
            <div class="resume-card">
                <div class="photo-section">
                    <span class="resume-id-badge">#<?= $resume['id'] ?></span>
                    <?php if ($resume['photo_filename']): ?>
                       <img src="/ResumeBot1/src/images/<?= $resume['photo_filename'] ?>" 
                            alt="<?= htmlspecialchars($resume['name']) ?>">
                    <?php else: ?>
                        <div class="photo-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="info-section">
                    <span class="language-badge <?= $resume['language'] ?>">
                        <?= $resume['language'] === 'ru' ? '🇷🇺 Русский' : '🇺🇿 Узбекский' ?>
                    </span>
                    
                    <div class="name"><?= htmlspecialchars($resume['name']) ?></div>
                    
                    <div class="job-title">
                        <i class="fas fa-briefcase"></i>
                        <?= htmlspecialchars($resume['job_name_ru']) ?>
                    </div>
                    
                    <div class="details">
                        <div class="detail-row">
                            <i class="fas fa-birthday-cake"></i>
                            <strong>Возраст:</strong>
                            <span><?= $resume['age'] ?> лет</span>
                        </div>
                        
                        <div class="detail-row">
                            <i class="fas fa-phone"></i>
                            <strong>Телефон:</strong>
                            <span><?= htmlspecialchars($resume['phone']) ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <i class="fas fa-map-marker-alt"></i>
                            <strong>Регион:</strong>
                            <span><?= htmlspecialchars($resume['region_name_ru']) ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <i class="fas fa-city"></i>
                            <strong>Город:</strong>
                            <span><?= htmlspecialchars($resume['city_name_ru']) ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <i class="fas fa-calendar"></i>
                            <strong>Дата:</strong>
                            <span><?= date('d.m.Y', strtotime($resume['created_at'])) ?></span>
                        </div>
                    </div>
                    
                    <div class="actions">
                        <a href="?delete=<?= $resume['id'] ?>" 
                           class="btn btn-delete" 
                           onclick="return confirm('Удалить резюме <?= htmlspecialchars($resume['name']) ?>?')">
                            <i class="fas fa-trash"></i>
                            Удалить
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>