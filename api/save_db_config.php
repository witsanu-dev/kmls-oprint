<?php
/**
 * API: Save Database Connection Config
 * Saves host, port, user, pass, and dbname to config/database_config.json
 */
header('Content-Type: application/json; charset=utf-8');

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    $data = $_POST;
}

$host   = isset($data['host'])   ? trim($data['host'])   : '';
$port   = isset($data['port'])   ? trim($data['port'])   : '';
$user   = isset($data['user'])   ? trim($data['user'])   : '';
$pass   = isset($data['pass'])   ? $data['pass']          : '';
$dbname = isset($data['dbname']) ? trim($data['dbname']) : '';

if (empty($host) || empty($port) || empty($user) || empty($dbname)) {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณากรอกข้อมูล Host, Port, Username และ Database Name ให้ครบถ้วน'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. First verify if the connection works before saving
$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_TIMEOUT            => 3,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8, time_zone = '+07:00'"
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่สามารถบันทึกได้ เนื่องจากทดสอบเชื่อมต่อไม่สำเร็จ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Save into config/database_config.json
$configDir = __DIR__ . '/../config';
if (!file_exists($configDir)) {
    @mkdir($configDir, 0777, true);
}

$configFile = $configDir . '/database_config.json';

// Try granting write permission if file exists
if (file_exists($configFile)) {
    @chmod($configFile, 0777);
}

$configData = [
    'DB_HOST' => $host,
    'DB_PORT' => $port,
    'DB_USER' => $user,
    'DB_PASS' => $pass,
    'DB_NAME' => $dbname,
    'updated_at' => date('Y-m-d H:i:s')
];

$jsonStr = json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$writeSuccess = @file_put_contents($configFile, $jsonStr);

if ($writeSuccess !== false) {
    @chmod($configFile, 0777);
    echo json_encode([
        'success' => true,
        'message' => 'บันทึกการตั้งค่าการเชื่อมต่อฐานข้อมูลเรียบร้อยแล้ว!'
    ], JSON_UNESCAPED_UNICODE);
} else {
    // Fallback: Try saving to session if file system is completely read-only
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $_SESSION['DB_HOST'] = $host;
    $_SESSION['DB_PORT'] = $port;
    $_SESSION['DB_USER'] = $user;
    $_SESSION['DB_PASS'] = $pass;
    $_SESSION['DB_NAME'] = $dbname;

    echo json_encode([
        'success' => true,
        'message' => 'บันทึกการตั้งค่าเข้าสู่ Session ชั่วคราวเรียบร้อยแล้ว! (คำแนะนำ: สั่ง chmod -R 777 config บนเซิร์ฟเวอร์เพื่อให้บันทึกถาวร)'
    ], JSON_UNESCAPED_UNICODE);
}
