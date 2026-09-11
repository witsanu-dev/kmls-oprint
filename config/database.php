<?php
/**
 * HOSxP Database Connection Config
 * Eye Patient Screening & OPD Card System - Kamalasai Hospital
 */

// Set Timezone to Asia/Bangkok (Thailand)
date_default_timezone_set('Asia/Bangkok');

// Dynamic Config File path
$configFile = __DIR__ . '/database_config.json';
$dynConfig = [];
if (file_exists($configFile)) {
    $jsonContent = file_get_contents($configFile);
    $dynConfig = json_decode($jsonContent, true) ?: [];
}

define('DB_HOST', !empty($dynConfig['DB_HOST']) ? $dynConfig['DB_HOST'] : '10.250.100.201');
define('DB_PORT', !empty($dynConfig['DB_PORT']) ? $dynConfig['DB_PORT'] : '3306');
define('DB_USER', !empty($dynConfig['DB_USER']) ? $dynConfig['DB_USER'] : 'hxpkt');
define('DB_PASS', isset($dynConfig['DB_PASS']) ? $dynConfig['DB_PASS'] : 'servkt');
define('DB_NAME', !empty($dynConfig['DB_NAME']) ? $dynConfig['DB_NAME'] : 'hos'); // HOSxP default database name
define('DB_CHARSET', 'utf8');

class Database {
    private static $instance = null;
    private $pdo = null;
    private $isMockMode = false;

    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 2, // 2 seconds connection timeout for fast failover
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8, time_zone = '+07:00', max_execution_time = 2000"
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->isMockMode = false;
        } catch (PDOException $e) {
            // If HOSxP DB server connection fails (e.g. offline, outside network), fallback to local/mock mode safely
            $this->isMockMode = true;
            $this->pdo = null;
            error_log("HOSxP Database Connection Error: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function isMock() {
        return $this->isMockMode;
    }
}

// Function to get DB PDO connection easily
function getDB() {
    return Database::getInstance()->getConnection();
}

function isMockMode() {
    return Database::getInstance()->isMock();
}
