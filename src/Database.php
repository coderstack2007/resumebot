<?php
namespace App;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $connection;
    
    // Путь к файлу базы данных
    const DB_PATH = __DIR__ . '/../database/resumes.db';
    
    private function __construct()
    {
        try {
            // Создаем директорию для базы данных, если её нет
            $db_dir = dirname(self::DB_PATH);
            if (!file_exists($db_dir)) {
                mkdir($db_dir, 0777, true);
            }
            
            // Подключаемся к SQLite
            $this->connection = new PDO('sqlite:' . self::DB_PATH);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Создаем таблицу, если её нет
            $this->createTable();
            
            echo "✅ База данных успешно подключена\n";
        } catch (PDOException $e) {
            echo "❌ Ошибка подключения к базе данных: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    /**
     * Получение единственного экземпляра класса (Singleton)
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Получение соединения с базой данных
     */
    public function getConnection()
    {
        return $this->connection;
    }
    
    /**
     * Создание таблицы для хранения резюме
     */
    private function createTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS resumes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chat_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            age INTEGER NOT NULL,
            phone TEXT NOT NULL,
            photo_filename TEXT,
            region_id INTEGER NOT NULL,
            region_name TEXT NOT NULL,
            city_id INTEGER NOT NULL,
            city_name TEXT NOT NULL,
            job_id INTEGER NOT NULL,
            job_name TEXT NOT NULL,
            language TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->connection->exec($sql);
    }
    
    /**
     * Сохранение резюме в базу данных
     */
    public function saveResume($data)
    {
        try {
            $sql = "INSERT INTO resumes (
                chat_id, name, age, phone, photo_filename, 
                region_id, region_name, city_id, city_name, 
                job_id, job_name, language
            ) VALUES (
                :chat_id, :name, :age, :phone, :photo_filename,
                :region_id, :region_name, :city_id, :city_name,
                :job_id, :job_name, :language
            )";
            
            $stmt = $this->connection->prepare($sql);
            
            $stmt->execute([
                ':chat_id' => $data['chat_id'],
                ':name' => $data['name'],
                ':age' => $data['age'],
                ':phone' => $data['phone'],
                ':photo_filename' => $data['photo_filename'] ?? null,
                ':region_id' => $data['region_id'],
                ':region_name' => $data['region_name'],
                ':city_id' => $data['city_id'],
                ':city_name' => $data['city_name'],
                ':job_id' => $data['job_id'],
                ':job_name' => $data['job_name'],
                ':language' => $data['language']
            ]);
            
            $resume_id = $this->connection->lastInsertId();
            echo "✅ Резюме #$resume_id сохранено в базу данных\n";
            
            return $resume_id;
        } catch (PDOException $e) {
            echo "❌ Ошибка при сохранении резюме: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Получение всех резюме
     */
    public function getAllResumes()
    {
        try {
            $sql = "SELECT * FROM resumes ORDER BY created_at DESC";
            $stmt = $this->connection->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "❌ Ошибка при получении резюме: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
    /**
     * Получение резюме по chat_id
     */
    public function getResumesByChatId($chat_id)
    {
        try {
            $sql = "SELECT * FROM resumes WHERE chat_id = :chat_id ORDER BY created_at DESC";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([':chat_id' => $chat_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "❌ Ошибка при получении резюме: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
    /**
     * Получение резюме по ID
     */
    public function getResumeById($id)
    {
        try {
            $sql = "SELECT * FROM resumes WHERE id = :id";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "❌ Ошибка при получении резюме: " . $e->getMessage() . "\n";
            return null;
        }
    }
    
    /**
     * Удаление резюме по ID
     */
    public function deleteResume($id)
    {
        try {
            $sql = "DELETE FROM resumes WHERE id = :id";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([':id' => $id]);
            echo "✅ Резюме #$id удалено из базы данных\n";
            return true;
        } catch (PDOException $e) {
            echo "❌ Ошибка при удалении резюме: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Получение статистики по резюме
     */
    public function getStatistics()
    {
        try {
            $stats = [];
            
            // Общее количество резюме
            $sql = "SELECT COUNT(*) as total FROM resumes";
            $stmt = $this->connection->query($sql);
            $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Количество по языкам
            $sql = "SELECT language, COUNT(*) as count FROM resumes GROUP BY language";
            $stmt = $this->connection->query($sql);
            $stats['by_language'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Количество по вакансиям
            $sql = "SELECT job_name, COUNT(*) as count FROM resumes GROUP BY job_name ORDER BY count DESC";
            $stmt = $this->connection->query($sql);
            $stats['by_job'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Количество по городам
            $sql = "SELECT city_name, COUNT(*) as count FROM resumes GROUP BY city_name ORDER BY count DESC";
            $stmt = $this->connection->query($sql);
            $stats['by_city'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch (PDOException $e) {
            echo "❌ Ошибка при получении статистики: " . $e->getMessage() . "\n";
            return null;
        }
    }
}