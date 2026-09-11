<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method'], JSON_UNESCAPED_UNICODE);
    exit;
}

$vn = isset($_POST['vn']) ? trim($_POST['vn']) : '';
$action = isset($_POST['action']) ? trim($_POST['action']) : 'cancel';

if (empty($vn)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุ VN'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (isMockMode() || empty(getDB())) {
    echo json_encode([
        'status' => 'success',
        'message' => ($action === 'restore' ? 'ยกเลิกการยกเลิกเรียบร้อย' : 'ยกเลิกการคัดกรองเรียบร้อยแล้ว (Mock Mode)')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = getDB();

    // Ensure status column exists
    try {
        $db->exec("ALTER TABLE oprint_pharmacy_note ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER note_remark");
    } catch (Exception $exCol) {
        // Column may already exist, ignore error
    }

    $newStatus = ($action === 'restore') ? 'active' : 'cancelled';
    $msgText = ($action === 'restore') ? 'คืนสถานะรายการคัดกรองเรียบร้อยแล้ว' : 'ยกเลิกรายการคัดกรองเรียบร้อยแล้ว (เก็บประวัติไว้ในระบบ)';

    $stmt = $db->prepare("UPDATE oprint_pharmacy_note SET status = :status, updated_at = NOW() WHERE vn = :vn");
    $stmt->execute([
        ':status' => $newStatus,
        ':vn'     => $vn
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => $msgText,
        'new_status' => $newStatus
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการเปลี่ยนสถานะ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
