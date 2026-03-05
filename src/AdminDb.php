<?php
namespace App;

use PDO;
use PDOException;

class AdminDb
{
    private static ?PDO $connection = null;

    const DB_HOST    = '127.0.0.1';
    const DB_PORT    = '8889';
    const DB_NAME    = 'adminresumes';
    const DB_USER    = 'root';
    const DB_PASS    = 'root';
    const DB_CHARSET = 'utf8mb4';

    private function __construct() {}

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    self::DB_HOST,
                    self::DB_PORT,
                    self::DB_NAME,
                    self::DB_CHARSET
                );

                self::$connection = new PDO($dsn, self::DB_USER, self::DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);

                self::log("✅ AdminDB подключена к " . self::DB_NAME);
            } catch (PDOException $e) {
                self::log("❌ Ошибка подключения AdminDB: " . $e->getMessage());
                throw $e;
            }
        }
        return self::$connection;
    }

    public static function getActiveVacancies(): array
    {
        try {
            $sql = "
                SELECT
                    vr.id,
                    vr.status,
                    p.name AS position_name,
                    b.name AS branch_name
                FROM vacancy_requests vr
                LEFT JOIN positions p ON vr.position_id = p.id
                LEFT JOIN branches  b ON vr.branch_id   = b.id
                WHERE vr.position_id IS NOT NULL
                  AND vr.status = 'approved'
                ORDER BY vr.created_at DESC
            ";
            return self::getConnection()->query($sql)->fetchAll();
        } catch (PDOException $e) {
            self::log("❌ Ошибка getActiveVacancies: " . $e->getMessage());
            return [];
        }
    }

    public static function getVacancyById(int $id): ?array
    {
        try {
            $stmt = self::getConnection()->prepare("
                SELECT
                    vr.id,
                    p.name  AS position_name,
                    b.name  AS branch_name
                FROM vacancy_requests vr
                LEFT JOIN positions p ON vr.position_id = p.id
                LEFT JOIN branches  b ON vr.branch_id   = b.id
                WHERE vr.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            self::log("❌ Ошибка getVacancyById($id): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Удалить вакансию и все связанные резюме из resume_bot
     */
    public static function deleteVacancyWithResumes(int $vacancyId): bool
    {
        try {
            self::log("🗑️ Начало удаления вакансии #$vacancyId");

            // 1. Удаляем все резюме из resume_bot БД, связанные с этой вакансией
            $resumeBotConn = self::getResumeBotConnection();
            $stmt = $resumeBotConn->prepare("DELETE FROM resumes WHERE vacancy_id = :vacancy_id");
            $stmt->execute([':vacancy_id' => $vacancyId]);
            $deletedResumes = $stmt->rowCount();
            
            self::log("✅ Удалено резюме из resume_bot: $deletedResumes шт.");

            // 2. Удаляем вакансию из adminresumes (каскадно удалятся логи и уведомления)
            $stmt = self::getConnection()->prepare("DELETE FROM vacancy_requests WHERE id = :id");
            $stmt->execute([':id' => $vacancyId]);

            self::log("✅ Вакансия #$vacancyId удалена из adminresumes");

            return true;
        } catch (PDOException $e) {
            self::log("❌ Ошибка deleteVacancyWithResumes($vacancyId): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получить подключение к resume_bot БД
     */
    private static function getResumeBotConnection(): PDO
    {
        static $resumeBotConn = null;

        if ($resumeBotConn === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    '127.0.0.1',
                    '8889',
                    'resume_bot',
                    'utf8mb4'
                );

                $resumeBotConn = new PDO($dsn, 'root', 'root', [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);

                self::log("✅ Подключение к resume_bot БД успешно");
            } catch (PDOException $e) {
                self::log("❌ Ошибка подключения к resume_bot: " . $e->getMessage());
                throw $e;
            }
        }

        return $resumeBotConn;
    }

    /**
     * Удалить одно резюме из resume_bot
     */
    public static function deleteResume(int $resumeId): bool
    {
        try {
            self::log("🗑️ Удаление резюме #$resumeId из resume_bot");

            $conn = self::getResumeBotConnection();
            $stmt = $conn->prepare("DELETE FROM resumes WHERE id = :id");
            $stmt->execute([':id' => $resumeId]);

            self::log("✅ Резюме #$resumeId удалено");
            return true;
        } catch (PDOException $e) {
            self::log("❌ Ошибка deleteResume($resumeId): " . $e->getMessage());
            return false;
        }
    }

    private static function log(string $message): void
    {
        $log_dir = dirname(__DIR__, 1) . '/logs';
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        $line = '[' . date('Y-m-d H:i:s') . '] [AdminDb] ' . $message . PHP_EOL;
        file_put_contents($log_dir . '/admin_db.log', $line, FILE_APPEND);
        echo $line;
    }
}