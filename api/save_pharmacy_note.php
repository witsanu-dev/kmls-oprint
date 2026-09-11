<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method'], JSON_UNESCAPED_UNICODE);
    exit;
}

$vn = isset($_POST['vn']) ? trim($_POST['vn']) : '';
$hn = isset($_POST['hn']) ? trim($_POST['hn']) : '';

if (empty($vn) || empty($hn)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุ VN และ HN ผู้ป่วย'], JSON_UNESCAPED_UNICODE);
    exit;
}

$fields = [
    'vn' => $vn,
    'hn' => $hn,
    'stop_drug_icode' => $_POST['stop_drug_icode'] ?? '',
    'stop_drug_name' => $_POST['stop_drug_name'] ?? '',
    'stop_start_date' => !empty($_POST['stop_start_date']) ? $_POST['stop_start_date'] : null,
    'stop_end_date' => !empty($_POST['stop_end_date']) ? $_POST['stop_end_date'] : null,
    'consult_doctor_code' => $_POST['consult_doctor_code'] ?? '',
    'consult_doctor_name' => $_POST['consult_doctor_name'] ?? '',
    'consult_time' => $_POST['consult_time'] ?? date('H:i'),
    'pharmacist_name' => $_POST['pharmacist_name'] ?? 'ภญ. พิมลพรรณ เภสัชกรดีเด่น',
    'note_remark' => $_POST['note_remark'] ?? ''
];

if (isMockMode() || empty(getDB())) {
    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูล Pharmacy Note สำเร็จเรียบร้อย (Mock Mode)',
        'data' => $fields
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = getDB();

    // Ensure table exists
    $db->exec("CREATE TABLE IF NOT EXISTS `oprint_pharmacy_note` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `vn` VARCHAR(20) NOT NULL,
      `hn` VARCHAR(20) NOT NULL,
      `stop_drug_icode` VARCHAR(20) DEFAULT NULL,
      `stop_drug_name` VARCHAR(255) DEFAULT NULL,
      `stop_start_date` DATE DEFAULT NULL,
      `stop_end_date` DATE DEFAULT NULL,
      `consult_doctor_code` VARCHAR(20) DEFAULT NULL,
      `consult_doctor_name` VARCHAR(255) DEFAULT NULL,
      `consult_time` TIME DEFAULT NULL,
      `pharmacist_name` VARCHAR(255) DEFAULT NULL,
      `note_remark` TEXT DEFAULT NULL,
      `status` VARCHAR(20) DEFAULT 'active',
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX `idx_vn` (`vn`),
      INDEX `idx_hn` (`hn`),
      INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    // Ensure status column exists if table existed previously
    try {
        $db->exec("ALTER TABLE oprint_pharmacy_note ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER note_remark");
    } catch (Exception $exCol) {}

    // Check if record exists for this VN
    $chkStmt = $db->prepare("SELECT id FROM oprint_pharmacy_note WHERE vn = :vn LIMIT 1");
    $chkStmt->execute([':vn' => $vn]);
    $existing = $chkStmt->fetch();

    if ($existing) {
        $sql = "UPDATE oprint_pharmacy_note SET
                    stop_drug_icode = :stop_drug_icode,
                    stop_drug_name = :stop_drug_name,
                    stop_start_date = :stop_start_date,
                    stop_end_date = :stop_end_date,
                    consult_doctor_code = :consult_doctor_code,
                    consult_doctor_name = :consult_doctor_name,
                    consult_time = :consult_time,
                    pharmacist_name = :pharmacist_name,
                    note_remark = :note_remark,
                    status = 'active',
                    updated_at = NOW()
                WHERE vn = :vn";
    } else {
        $sql = "INSERT INTO oprint_pharmacy_note (
                    vn, hn, stop_drug_icode, stop_drug_name, stop_start_date, stop_end_date,
                    consult_doctor_code, consult_doctor_name, consult_time, pharmacist_name, note_remark
                ) VALUES (
                    :vn, :hn, :stop_drug_icode, :stop_drug_name, :stop_start_date, :stop_end_date,
                    :consult_doctor_code, :consult_doctor_name, :consult_time, :pharmacist_name, :note_remark
                )";
    }

    $stmt = $db->prepare($sql);

    if ($existing) {
        $stmt->execute([
            ':stop_drug_icode'   => $_POST['stop_drug_icode'] ?? '',
            ':stop_drug_name'    => $_POST['stop_drug_name'] ?? '',
            ':stop_start_date'   => !empty($_POST['stop_start_date']) ? $_POST['stop_start_date'] : null,
            ':stop_end_date'     => !empty($_POST['stop_end_date']) ? $_POST['stop_end_date'] : null,
            ':consult_doctor_code' => $_POST['consult_doctor_code'] ?? '',
            ':consult_doctor_name' => $_POST['consult_doctor_name'] ?? '',
            ':consult_time'      => $_POST['consult_time'] ?? date('H:i'),
            ':pharmacist_name'   => $_POST['pharmacist_name'] ?? '',
            ':note_remark'       => $_POST['note_remark'] ?? '',
            ':vn'                => $vn
        ]);
    } else {
        $stmt->execute([
            ':vn'                => $vn,
            ':hn'                => $hn,
            ':stop_drug_icode'   => $_POST['stop_drug_icode'] ?? '',
            ':stop_drug_name'    => $_POST['stop_drug_name'] ?? '',
            ':stop_start_date'   => !empty($_POST['stop_start_date']) ? $_POST['stop_start_date'] : null,
            ':stop_end_date'     => !empty($_POST['stop_end_date']) ? $_POST['stop_end_date'] : null,
            ':consult_doctor_code' => $_POST['consult_doctor_code'] ?? '',
            ':consult_doctor_name' => $_POST['consult_doctor_name'] ?? '',
            ':consult_time'      => $_POST['consult_time'] ?? date('H:i'),
            ':pharmacist_name'   => $_POST['pharmacist_name'] ?? '',
            ':note_remark'       => $_POST['note_remark'] ?? ''
        ]);
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูล Pharmacy Note เรียบร้อยแล้ว'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
