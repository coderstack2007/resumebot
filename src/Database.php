<?php
namespace App;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $connection;
    
    // Настройки подключения к MySQL
    const DB_HOST = 'localhost:8889';
    const DB_NAME = 'resume_bot';
    const DB_USER = 'root';
    const DB_PASS = 'root';
    const DB_CHARSET = 'utf8mb4';
    
    private function __construct()
    {
        try {
            $dsn = "mysql:host=" . self::DB_HOST . ";dbname=" . self::DB_NAME . ";charset=" . self::DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->connection = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            
            $this->createTables();
            $this->seedTables();
            
            echo "✅ База данных MySQL успешно подключена\n";
        } catch (PDOException $e) {
            echo "❌ Ошибка подключения к базе данных: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection()
    {
        return $this->connection;
    }
    
    // ─────────────────────────────────────────────
    // Создание таблиц
    // ─────────────────────────────────────────────
    
    private function createTables()
    {
        // Таблица вакансий
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS jobs (
                id INT PRIMARY KEY,
                name_ru VARCHAR(255) NOT NULL,
                name_uz VARCHAR(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Таблица регионов
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS regions (
                id INT PRIMARY KEY,
                name_ru VARCHAR(255) NOT NULL,
                name_uz VARCHAR(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Таблица городов
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS cities (
                id INT PRIMARY KEY,
                region_id INT NOT NULL,
                name_ru VARCHAR(255) NOT NULL,
                name_uz VARCHAR(255) NOT NULL,
                FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Таблица резюме
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS resumes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                chat_id BIGINT NOT NULL,
                name VARCHAR(255) NOT NULL,
                age INT NOT NULL,
                phone VARCHAR(50) NOT NULL,
                photo_filename VARCHAR(255),
                region_id INT NOT NULL,
                city_id INT NOT NULL,
                job_id INT NOT NULL,
                language VARCHAR(10) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE CASCADE,
                FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE,
                FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
                INDEX idx_chat_id (chat_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    // ─────────────────────────────────────────────
    // Заполнение справочников (один раз)
    // ─────────────────────────────────────────────
    
    private function seedTables()
    {
        $this->seedJobs();
        $this->seedRegions();
        $this->seedCities();
    }

    private function seedJobs()
    {
        $count = $this->connection->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
        if ($count > 0) return;

        $jobs = [
            1  => ['DevOps Engineer',              'DevOps Engineer'],
            2  => ['Cyber Security Specialist',    'Cyber Security Specialist'],
            3  => ['Middle Developer',             'Middle Developer'],
            4  => ['Senior Developer',             'Senior Developer'],
            5  => ['Frontend Developer',           'Frontend Developer'],
            6  => ['Backend Developer',            'Backend Developer'],
            7  => ['Full Stack Developer',         'Full Stack Developer'],
            8  => ['QA Engineer',                  'QA Engineer'],
            9  => ['Project Manager',              'Project Manager'],
            10 => ['UI/UX Designer',               'UI/UX Designer'],
        ];

        $stmt = $this->connection->prepare(
            "INSERT INTO jobs (id, name_ru, name_uz) VALUES (:id, :name_ru, :name_uz)"
        );

        foreach ($jobs as $id => [$name_ru, $name_uz]) {
            $stmt->execute([':id' => $id, ':name_ru' => $name_ru, ':name_uz' => $name_uz]);
        }

        echo "✅ Заполнена таблица jobs\n";
    }

    private function seedRegions()
    {
        $count = $this->connection->query("SELECT COUNT(*) FROM regions")->fetchColumn();
        if ($count > 0) return;

        $regions = [
            1  => ['Ташкент',                        'Toshkent'],
            2  => ['Ташкентская область',            'Toshkent viloyati'],
            3  => ['Андижанская область',            'Andijon viloyati'],
            4  => ['Бухарская область',              'Buxoro viloyati'],
            5  => ['Джизакская область',             'Jizzax viloyati'],
            6  => ['Кашкадарьинская область',        'Qashqadaryo viloyati'],
            7  => ['Навоийская область',             'Navoiy viloyati'],
            8  => ['Наманганская область',           'Namangan viloyati'],
            9  => ['Самаркандская область',          'Samarqand viloyati'],
            10 => ['Сурхандарьинская область',       'Surxondaryo viloyati'],
            11 => ['Сырдарьинская область',          'Sirdaryo viloyati'],
            12 => ['Ферганская область',             "Farg'ona viloyati"],
            13 => ['Хорезмская область',             'Xorazm viloyati'],
            14 => ['Республика Каракалпакстан',      "Qoraqalpog'iston Respublikasi"],
        ];

        $stmt = $this->connection->prepare(
            "INSERT INTO regions (id, name_ru, name_uz) VALUES (:id, :name_ru, :name_uz)"
        );

        foreach ($regions as $id => [$name_ru, $name_uz]) {
            $stmt->execute([':id' => $id, ':name_ru' => $name_ru, ':name_uz' => $name_uz]);
        }

        echo "✅ Заполнена таблица regions\n";
    }

    private function seedCities()
    {
        $count = $this->connection->query("SELECT COUNT(*) FROM cities")->fetchColumn();
        if ($count > 0) {
            echo "ℹ️ Таблица cities уже заполнена ($count записей)\n";
            return;
        }

        $cities = [
            // Регион 1 — Ташкент
            101 => [1, 'Чиланзар',       'Chilonzor'],
            102 => [1, 'Юнусабад',       'Yunusobod'],
            103 => [1, 'Мирабад',        'Mirobod'],
            104 => [1, 'Сергели',        'Sergeli'],
            105 => [1, 'Алмазар',        'Olmazor'],
            106 => [1, 'Яккасарай',      'Yakkasaroy'],
            107 => [1, 'Шайхантахур',    'Shayhontohur'],
            108 => [1, 'Учтепа',         'Uchtepa'],
            109 => [1, 'Бектемир',       'Bektemir'],
            110 => [1, 'Яшнабад',        'Yashnobod'],
            111 => [1, 'Мирзо-Улугбек',  "Mirzo Ulug'bek"],

            // Регион 2 — Ташкентская область
            201 => [2, 'Ангрен',         'Angren'],
            202 => [2, 'Алмалык',        'Olmaliq'],
            203 => [2, 'Бекабад',        'Bekobod'],
            204 => [2, 'Чирчик',         'Chirchiq'],
            205 => [2, 'Янгиюль',        "Yangiyo'l"],
            206 => [2, 'Нурафшон',       'Nurafshon'],
            207 => [2, 'Ахангаран',      'Ohangaron'],
            208 => [2, 'Паркент',        'Parkent'],
            209 => [2, 'Бука',           "Bo'ka"],
            210 => [2, 'Газалкент',      "G'azalkent"],

            // Регион 3 — Андижанская область
            301 => [3, 'Андижан',        'Andijon'],
            302 => [3, 'Асака',          'Asaka'],
            303 => [3, 'Шахрихан',       'Shahrixon'],
            304 => [3, 'Ханабад',        'Xonobod'],
            305 => [3, 'Пайтуг',         "Paytug'"],
            306 => [3, 'Мархамат',       'Marhamat'],
            307 => [3, 'Кургантепа',     "Qo'rg'ontepa"],
            308 => [3, 'Ходжаабад',      "Xo'jaobod"],
            309 => [3, 'Балыкчи',        'Baliqchi'],
            310 => [3, 'Избоскан',       'Izboskan'],

            // Регион 4 — Бухарская область
            401 => [4, 'Бухара',         'Buxoro'],
            402 => [4, 'Каган',          'Kogon'],
            403 => [4, 'Галляарал',      "G'allaoral"],
            404 => [4, 'Газли',          "G'azli"],
            405 => [4, 'Каракуль',       "Qorako'l"],
            406 => [4, 'Вабкент',        'Vobkent'],
            407 => [4, 'Жондор',         'Jondor'],
            408 => [4, 'Ромитан',        'Romitan'],
            409 => [4, 'Шафиркан',       'Shofirkon'],
            410 => [4, 'Гиждуван',       "G'ijduvon"],

            // Регион 5 — Джизакская область
            501 => [5, 'Джизак',         'Jizzax'],
            502 => [5, 'Галляарал',      "G'allaoral"],
            503 => [5, 'Дустлик',        "Do'stlik"],
            504 => [5, 'Пахтакор',       'Paxtakor'],
            505 => [5, 'Зафарабад',      'Zafarobod'],
            506 => [5, 'Зарбдор',        'Zarbdor'],
            507 => [5, 'Зомин',          'Zomin'],
            508 => [5, 'Бахмал',         'Baxmal'],
            509 => [5, 'Фориш',          'Forish'],
            510 => [5, 'Мирзачуль',      "Mirzacho'l"],

            // Регион 6 — Кашкадарьинская область
            601 => [6, 'Карши',          'Qarshi'],
            602 => [6, 'Шахрисабз',      'Shahrisabz'],
            603 => [6, 'Китаб',          'Kitob'],
            604 => [6, 'Гузар',          "G'uzor"],
            605 => [6, 'Мубарек',        'Muborak'],
            606 => [6, 'Касан',          'Koson'],
            607 => [6, 'Камаши',         'Kamashi'],
            608 => [6, 'Яккабаг',        "Yakkabog'"],
            609 => [6, 'Чиракчи',        'Chiroqchi'],
            610 => [6, 'Нишан',          'Nishon'],

            // Регион 7 — Навоийская область
            701 => [7, 'Навои',          'Navoiy'],
            702 => [7, 'Зарафшан',       'Zarafshon'],
            703 => [7, 'Кармана',        'Karmana'],
            704 => [7, 'Учкудук',        'Uchquduq'],
            705 => [7, 'Нурата',         'Nurota'],
            706 => [7, 'Кызылтепа',      'Qiziltepa'],
            707 => [7, 'Хатырчи',        'Xatirchi'],
            708 => [7, 'Тамдыбулак',     'Tamdibuloq'],
            709 => [7, 'Конимех',        'Konimex'],
            710 => [7, 'Навбахор',       'Navbahor'],

            // Регион 8 — Наманганская область
            801 => [8, 'Наманган',       'Namangan'],
            802 => [8, 'Хаккулабад',     'Xaqqulobod'],
            803 => [8, 'Чуст',           'Chust'],
            804 => [8, 'Касансай',       'Kosonsoy'],
            805 => [8, 'Туракурган',     "To'raqo'rg'on"],
            806 => [8, 'Учкурган',       "Uchqo'rg'on"],
            807 => [8, 'Пап',            'Pop'],
            808 => [8, 'Мингбулак',      'Mingbuloq'],
            809 => [8, 'Янгикурган',     "Yangiqo'rg'on"],
            810 => [8, 'Чартак',         'Chortoq'],

            // Регион 9 — Самаркандская область
            901 => [9, 'Самарканд',      'Samarqand'],
            902 => [9, 'Каттакурган',    "Kattaqo'rg'on"],
            903 => [9, 'Булунгур',       "Bulung'ur"],
            904 => [9, 'Джамбай',        'Jomboy'],
            905 => [9, 'Иштыхан',        'Ishtixon'],
            906 => [9, 'Акдарья',        'Oqdaryo'],
            907 => [9, 'Пайарык',        'Payariq'],
            908 => [9, 'Ургут',          'Urgut'],
            909 => [9, 'Нурабад',        'Nurobod'],
            910 => [9, 'Челек',          'Chelak'],

            // Регион 10 — Сурхандарьинская область
            1001 => [10, 'Термез',       'Termiz'],
            1002 => [10, 'Денау',        'Denov'],
            1003 => [10, 'Шурчи',        "Sho'rchi"],
            1004 => [10, 'Байсун',       'Boysun'],
            1005 => [10, 'Кумкурган',    "Qumqo'rg'on"],
            1006 => [10, 'Джаркурган',   "Jarqo'rg'on"],
            1007 => [10, 'Алтынсай',     'Oltinsoy'],
            1008 => [10, 'Шаргун',       "Sharg'un"],
            1009 => [10, 'Узун',         'Uzun'],
            1010 => [10, 'Сариасия',     'Sariosiyo'],

            // Регион 11 — Сырдарьинская область
            1101 => [11, 'Гулистан',     'Guliston'],
            1102 => [11, 'Янгиер',       'Yangiyer'],
            1103 => [11, 'Сырдарья',     'Sirdaryo'],
            1104 => [11, 'Бахт',         'Baxt'],
            1105 => [11, 'Шараф-Рашидов','Sharaf Rashidov'],
            1106 => [11, 'Акалтын',      'Oqoltin'],
            1107 => [11, 'Мирзаабад',    'Mirzaobod'],
            1108 => [11, 'Сайхунабад',   'Sayxunobod'],
            1109 => [11, 'Хаваст',       'Xovos'],
            1110 => [11, 'Дустлик',      "Do'stlik"],

            // Регион 12 — Ферганская область
            1201 => [12, 'Фергана',      "Farg'ona"],
            1202 => [12, 'Маргилан',     "Marg'ilon"],
            1203 => [12, 'Коканд',       "Qo'qon"],
            1204 => [12, 'Кувасай',      'Quvasoy'],
            1205 => [12, 'Риштан',       'Rishton'],
            1206 => [12, 'Яйпан',        'Yaypan'],
            1207 => [12, 'Бешарик',      'Beshariq'],
            1208 => [12, 'Кува',         'Quva'],
            1209 => [12, 'Учкуприк',     "Uchko'prik"],
            1210 => [12, 'Тошлок',       'Toshloq'],

            // Регион 13 — Хорезмская область
            1301 => [13, 'Ургенч',       'Urganch'],
            1302 => [13, 'Хива',         'Xiva'],
            1303 => [13, 'Питняк',       'Pitnak'],
            1304 => [13, 'Шават',        'Shovot'],
            1305 => [13, 'Хазорасп',     'Xazorasp'],
            1306 => [13, 'Ханка',        'Xonqa'],
            1307 => [13, 'Гурлен',       'Gurlan'],
            1308 => [13, 'Богот',        "Bog'ot"],
            1309 => [13, 'Янгиарык',     'Yangiariq'],
            1310 => [13, 'Кошкупир',     "Qo'shko'pir"],

            // Регион 14 — Каракалпакстан
            1401 => [14, 'Нукус',        'Nukus'],
            1402 => [14, 'Турткуль',     "To'rtko'l"],
            1403 => [14, 'Беруни',       'Beruniy'],
            1404 => [14, 'Кунград',      "Qo'ng'irot"],
            1405 => [14, 'Муйнак',       "Mo'ynoq"],
            1406 => [14, 'Тахиаташ',     'Taxiatosh'],
            1407 => [14, 'Ходжейли',     "Xo'jayli"],
            1408 => [14, 'Чимбай',       'Chimboy'],
            1409 => [14, 'Караузяк',     "Qorao'zak"],
            1410 => [14, 'Шуманай',      'Shumanay'],
        ];

        $stmt = $this->connection->prepare(
            "INSERT INTO cities (id, region_id, name_ru, name_uz) VALUES (:id, :region_id, :name_ru, :name_uz)"
        );

        $success_count = 0;
        $error_count = 0;

        foreach ($cities as $id => [$region_id, $name_ru, $name_uz]) {
            try {
                $stmt->execute([
                    ':id'        => $id,
                    ':region_id' => $region_id,
                    ':name_ru'   => $name_ru,
                    ':name_uz'   => $name_uz,
                ]);
                $success_count++;
            } catch (PDOException $e) {
                $error_count++;
                echo "❌ Ошибка при вставке города ID $id ($name_ru): " . $e->getMessage() . "\n";
            }
        }

        echo "✅ Заполнена таблица cities: $success_count успешно, $error_count ошибок\n";
    }
    
    // ─────────────────────────────────────────────
    // CRUD для резюме
    // ─────────────────────────────────────────────
    
    public function saveResume($data)
    {
        try {
            $sql = "INSERT INTO resumes (
                chat_id, name, age, phone, photo_filename,
                region_id, city_id, job_id, language
            ) VALUES (
                :chat_id, :name, :age, :phone, :photo_filename,
                :region_id, :city_id, :job_id, :language
            )";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                ':chat_id'        => $data['chat_id'],
                ':name'           => $data['name'],
                ':age'            => $data['age'],
                ':phone'          => $data['phone'],
                ':photo_filename' => $data['photo_filename'] ?? null,
                ':region_id'      => $data['region_id'],
                ':city_id'        => $data['city_id'],
                ':job_id'         => $data['job_id'],
                ':language'       => $data['language'],
            ]);
            
            $resume_id = $this->connection->lastInsertId();
            echo "✅ Резюме #$resume_id сохранено в базу данных\n";
            
            return $resume_id;
        } catch (PDOException $e) {
            echo "❌ Ошибка при сохранении резюме: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function getAllResumes()
    {
        try {
            $sql = "
                SELECT
                    r.id,
                    r.chat_id,
                    r.name,
                    r.age,
                    r.phone,
                    r.photo_filename,
                    r.region_id,
                    reg.name_ru AS region_name_ru,
                    reg.name_uz AS region_name_uz,
                    r.city_id,
                    c.name_ru   AS city_name_ru,
                    c.name_uz   AS city_name_uz,
                    r.job_id,
                    j.name_ru   AS job_name_ru,
                    j.name_uz   AS job_name_uz,
                    r.language,
                    r.created_at
                FROM resumes r
                JOIN regions reg ON r.region_id = reg.id
                JOIN cities  c   ON r.city_id   = c.id
                JOIN jobs    j   ON r.job_id    = j.id
                ORDER BY r.created_at DESC
            ";
            $stmt = $this->connection->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "❌ Ошибка при получении резюме: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
    public function getResumesByChatId($chat_id)
    {
        try {
            $sql = "
                SELECT
                    r.id,
                    r.chat_id,
                    r.name,
                    r.age,
                    r.phone,
                    r.photo_filename,
                    r.region_id,
                    reg.name_ru AS region_name_ru,
                    reg.name_uz AS region_name_uz,
                    r.city_id,
                    c.name_ru   AS city_name_ru,
                    c.name_uz   AS city_name_uz,
                    r.job_id,
                    j.name_ru   AS job_name_ru,
                    j.name_uz   AS job_name_uz,
                    r.language,
                    r.created_at
                FROM resumes r
                JOIN regions reg ON r.region_id = reg.id
                JOIN cities  c   ON r.city_id   = c.id
                JOIN jobs    j   ON r.job_id    = j.id
                WHERE r.chat_id = :chat_id
                ORDER BY r.created_at DESC
            ";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([':chat_id' => $chat_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "❌ Ошибка при получении резюме: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
    public function getResumeById($id)
    {
        try {
            $sql = "
                SELECT
                    r.id,
                    r.chat_id,
                    r.name,
                    r.age,
                    r.phone,
                    r.photo_filename,
                    r.region_id,
                    reg.name_ru AS region_name_ru,
                    reg.name_uz AS region_name_uz,
                    r.city_id,
                    c.name_ru   AS city_name_ru,
                    c.name_uz   AS city_name_uz,
                    r.job_id,
                    j.name_ru   AS job_name_ru,
                    j.name_uz   AS job_name_uz,
                    r.language,
                    r.created_at
                FROM resumes r
                JOIN regions reg ON r.region_id = reg.id
                JOIN cities  c   ON r.city_id   = c.id
                JOIN jobs    j   ON r.job_id    = j.id
                WHERE r.id = :id
            ";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "❌ Ошибка при получении резюме: " . $e->getMessage() . "\n";
            return null;
        }
    }
    
    public function updateResume($id, $data)
    {
        try {
            $sql = "UPDATE resumes SET 
                name = :name,
                age = :age,
                phone = :phone,
                region_id = :region_id,
                city_id = :city_id,
                job_id = :job_id
                WHERE id = :id";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                ':id'         => $id,
                ':name'       => $data['name'],
                ':age'        => $data['age'],
                ':phone'      => $data['phone'],
                ':region_id'  => $data['region_id'],
                ':city_id'    => $data['city_id'],
                ':job_id'     => $data['job_id'],
            ]);
            
            echo "✅ Резюме #$id обновлено\n";
            return true;
        } catch (PDOException $e) {
            echo "❌ Ошибка при обновлении резюме: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
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
    
    // ─────────────────────────────────────────────
    // Статистика
    // ─────────────────────────────────────────────
    
    public function getStatistics()
    {
        try {
            $stats = [];
            
            $stats['total'] = $this->connection
                ->query("SELECT COUNT(*) FROM resumes")
                ->fetchColumn();
            
            $stats['by_language'] = $this->connection
                ->query("SELECT language, COUNT(*) as count FROM resumes GROUP BY language")
                ->fetchAll(PDO::FETCH_ASSOC);
            
            $stats['by_job'] = $this->connection
                ->query("
                    SELECT j.name_ru AS job_name, COUNT(*) as count
                    FROM resumes r
                    JOIN jobs j ON r.job_id = j.id
                    GROUP BY r.job_id
                    ORDER BY count DESC
                ")
                ->fetchAll(PDO::FETCH_ASSOC);
            
            $stats['by_city'] = $this->connection
                ->query("
                    SELECT c.name_ru AS city_name, COUNT(*) as count
                    FROM resumes r
                    JOIN cities c ON r.city_id = c.id
                    GROUP BY r.city_id
                    ORDER BY count DESC
                    LIMIT 10
                ")
                ->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch (PDOException $e) {
            echo "❌ Ошибка при получении статистики: " . $e->getMessage() . "\n";
            return null;
        }
    }

    public function getJobName(int $job_id, string $lang = 'ru'): ?string
    {
        $col = $lang === 'uz' ? 'name_uz' : 'name_ru';
        return $this->connection
            ->query("SELECT $col FROM jobs WHERE id = $job_id")
            ->fetchColumn();
    }

    public function getRegionName(int $region_id, string $lang = 'ru'): ?string
    {
        $col = $lang === 'uz' ? 'name_uz' : 'name_ru';
        return $this->connection
            ->query("SELECT $col FROM regions WHERE id = $region_id")
            ->fetchColumn();
    }

    public function getCityName(int $city_id, string $lang = 'ru'): ?string
    {
        $col = $lang === 'uz' ? 'name_uz' : 'name_ru';
        return $this->connection
            ->query("SELECT $col FROM cities WHERE id = $city_id")
            ->fetchColumn();
    }
    
    public function getAllRegions()
    {
        return $this->connection->query("SELECT * FROM regions ORDER BY name_ru")->fetchAll();
    }
    
    public function getCitiesByRegion($region_id)
    {
        $stmt = $this->connection->prepare("SELECT * FROM cities WHERE region_id = :region_id ORDER BY name_ru");
        $stmt->execute([':region_id' => $region_id]);
        return $stmt->fetchAll();
    }
    
    public function getAllJobs()
    {
        return $this->connection->query("SELECT * FROM jobs ORDER BY name_ru")->fetchAll();
    }
}