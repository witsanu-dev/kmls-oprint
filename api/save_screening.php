<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method'], JSON_UNESCAPED_UNICODE);
    exit;
}

$hn = isset($_POST['hn']) ? trim($_POST['hn']) : '';
if (empty($hn)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุเลข HN ผู้ป่วย'], JSON_UNESCAPED_UNICODE);
    exit;
}

$screen_date = !empty($_POST['screen_date']) ? $_POST['screen_date'] : date('Y-m-d');
$screen_time = !empty($_POST['screen_time']) ? $_POST['screen_time'] : date('H:i:s');

$fields = [
    'hn' => $hn,
    'vn' => $_POST['vn'] ?? '',
    'screen_date' => $screen_date,
    'screen_time' => $screen_time,
    'bps' => $_POST['bps'] ?? '',
    'bpd' => $_POST['bpd'] ?? '',
    'pulse' => $_POST['pulse'] ?? '',
    'weight' => $_POST['weight'] ?? '',
    'height' => $_POST['height'] ?? '',
    'cc' => $_POST['cc'] ?? '',
    'va_ra_distance' => $_POST['va_ra_distance'] ?? '',
    'va_la_distance' => $_POST['va_la_distance'] ?? '',
    'va_ra_with_glass' => $_POST['va_ra_with_glass'] ?? '',
    'va_la_with_glass' => $_POST['va_la_with_glass'] ?? '',
    'va_ra_pinhole' => $_POST['va_ra_pinhole'] ?? '',
    'va_la_pinhole' => $_POST['va_la_pinhole'] ?? '',
    'iop_re' => $_POST['iop_re'] ?? '',
    'iop_le' => $_POST['iop_le'] ?? '',
    'iop_time' => $_POST['iop_time'] ?? date('H:i:s'),
    'cataract_re' => $_POST['cataract_re'] ?? 'normal',
    'cataract_le' => $_POST['cataract_le'] ?? 'normal',
    'dr_re' => $_POST['dr_re'] ?? 'No DR',
    'dr_le' => $_POST['dr_le'] ?? 'No DR',
    'glaucoma_re' => $_POST['glaucoma_re'] ?? 'Normal',
    'glaucoma_le' => $_POST['glaucoma_le'] ?? 'Normal',
    'pterygium_re' => $_POST['pterygium_re'] ?? 'None',
    'pterygium_le' => $_POST['pterygium_le'] ?? 'None',
    'diagnosis_icd10' => $_POST['diagnosis_icd10'] ?? '',
    'doctor_notes' => $_POST['doctor_notes'] ?? '',
    'treatment_plan' => $_POST['treatment_plan'] ?? '',
    'examiner_name' => $_POST['examiner_name'] ?? 'เจ้าหน้าที่คัดกรอง'
];

if (isMockMode() || empty(getDB())) {
    // Return success in mock mode
    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูลคัดกรองตรวจตาเรียบร้อยแล้ว (Mock Mode)',
        'id' => rand(100, 999),
        'data' => $fields
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = getDB();
    
    // Check if table exists, create if not
    $db->exec("CREATE TABLE IF NOT EXISTS `oprint_eye_screening` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `hn` VARCHAR(20) NOT NULL,
      `vn` VARCHAR(20) DEFAULT NULL,
      `screen_date` DATE NOT NULL,
      `screen_time` TIME NOT NULL,
      `bps` VARCHAR(10) DEFAULT NULL,
      `bpd` VARCHAR(10) DEFAULT NULL,
      `pulse` VARCHAR(10) DEFAULT NULL,
      `weight` VARCHAR(10) DEFAULT NULL,
      `height` VARCHAR(10) DEFAULT NULL,
      `cc` TEXT DEFAULT NULL,
      `va_ra_distance` VARCHAR(20) DEFAULT NULL,
      `va_la_distance` VARCHAR(20) DEFAULT NULL,
      `va_ra_with_glass` VARCHAR(20) DEFAULT NULL,
      `va_la_with_glass` VARCHAR(20) DEFAULT NULL,
      `va_ra_pinhole` VARCHAR(20) DEFAULT NULL,
      `va_la_pinhole` VARCHAR(20) DEFAULT NULL,
      `iop_re` VARCHAR(20) DEFAULT NULL,
      `iop_le` VARCHAR(20) DEFAULT NULL,
      `iop_time` TIME DEFAULT NULL,
      `cataract_re` VARCHAR(50) DEFAULT 'normal',
      `cataract_le` VARCHAR(50) DEFAULT 'normal',
      `dr_re` VARCHAR(50) DEFAULT 'No DR',
      `dr_le` VARCHAR(50) DEFAULT 'No DR',
      `glaucoma_re` VARCHAR(50) DEFAULT 'Normal',
      `glaucoma_le` VARCHAR(50) DEFAULT 'Normal',
      `pterygium_re` VARCHAR(50) DEFAULT 'None',
      `pterygium_le` VARCHAR(50) DEFAULT 'None',
      `diagnosis_icd10` VARCHAR(100) DEFAULT NULL,
      `doctor_notes` TEXT DEFAULT NULL,
      `treatment_plan` TEXT DEFAULT NULL,
      `examiner_name` VARCHAR(100) DEFAULT NULL,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX `idx_hn` (`hn`),
      INDEX `idx_date` (`screen_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    $sql = "INSERT INTO oprint_eye_screening (
                hn, vn, screen_date, screen_time, bps, bpd, pulse, weight, height, cc,
                va_ra_distance, va_la_distance, va_ra_with_glass, va_la_with_glass, va_ra_pinhole, va_la_pinhole,
                iop_re, iop_le, iop_time, cataract_re, cataract_le, dr_re, dr_le, glaucoma_re, glaucoma_le,
                pterygium_re, pterygium_le, diagnosis_icd10, doctor_notes, treatment_plan, examiner_name
            ) VALUES (
                :hn, :vn, :screen_date, :screen_time, :bps, :bpd, :pulse, :weight, :height, :cc,
                :va_ra_distance, :va_la_distance, :va_ra_with_glass, :va_la_with_glass, :va_ra_pinhole, :va_la_pinhole,
                :iop_re, :iop_le, :iop_time, :cataract_re, :cataract_le, :dr_re, :dr_le, :glaucoma_re, :glaucoma_le,
                :pterygium_re, :pterygium_le, :diagnosis_icd10, :doctor_notes, :treatment_plan, :examiner_name
            )";

    $stmt = $db->prepare($sql);
    $stmt->execute($fields);
    $insertId = $db->lastInsertId();

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูลคัดกรองและตรวจตาสำเร็จเรียบร้อยแล้ว',
        'id' => $insertId
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
