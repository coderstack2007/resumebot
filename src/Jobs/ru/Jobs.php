<?php
namespace App\Jobs\ru;

class Jobs
{
    /**
     * Получить все вакансии
     */
    public static function getJobs(): array
    {
        return [
            1 => 'DevOps Engineer',
            2 => 'Cyber Security Specialist',
            3 => 'Middle Developer',
            4 => 'Senior Developer',
            5 => 'Frontend Developer',
            6 => 'Backend Developer',
            7 => 'Full Stack Developer',
            8 => 'QA Engineer',
            9 => 'Project Manager',
            10 => 'UI/UX Designer'
        ];
    }
    
    /**
     * Получить название вакансии по ID
     */
    public static function getJobName(int $job_id): ?string
    {
        $jobs = self::getJobs();
        return $jobs[$job_id] ?? null;
    }
    
    /**
     * Проверить существует ли вакансия
     */
    public static function jobExists(int $job_id): bool
    {
        return isset(self::getJobs()[$job_id]);
    }
}