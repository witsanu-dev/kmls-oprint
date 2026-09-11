<?php
/**
 * API: Test HOSxP Database Connection
 * Accepts JSON / POST parameters or tests defined constants if not provided
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    $data = $_POST;
}

$host = isset($data['host']) && !empty(trim($data['host'])) ? trim($data['host']) : DB_HOST;
$port = isset($data['port']) && !empty(trim($data['port'])) ? trim($data['port']) : DB_PORT;
$user = isset($data['user']) && !empty(trim($data['user'])) ? trim($data['user']) : DB_USER;
$pass = isset($data['pass']) ? $data['pass'] : DB_PASS;
$dbname = isset($data['dbname']) && !empty(trim($data['dbname'])) ? trim($data['dbname']) : DB_NAME;

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_TIMEOUT            => 3,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8, time_zone = '+07:00'"
];

$startTime = microtime(true);

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $responseTime = round((microtime(true) - $startTime) * 1000, 2);

    // Query HOSxP system info to verify database identity
    $sysInfo = "HOSxP Database connected";
    try {
        $stmt = $pdo->query("SELECT sys_value FROM sys_var WHERE sys_name = 'HOSPITALCODE' LIMIT 1");
        $row = $stmt->fetch();
        if ($row) {
            $sysInfo = "HOSxP (Hospital Code: " . $row['sys_value'] . ")";
        }
    } catch (Exception $ex) {
        // Table might differ, default message is fine
    }

    echo json_encode([
        'success' => true,
        'message' => 'เชื่อมต่อฐานข้อมูล HOSxP สำเร็จ!',
        'host' => $host,
        'port' => $port,
        'database' => $dbname,
        'response_time' => $responseTime . ' ms',
        'info' => $sysInfo
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
    echo json_encode([
        'success' => false,
        'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้: ' . $e->getMessage(),
        'host' => $host,
        'port' => $port,
        'database' => $dbname,
        'response_time' => $responseTime . ' ms'
    ], JSON_UNESCAPED_UNICODE);
}
