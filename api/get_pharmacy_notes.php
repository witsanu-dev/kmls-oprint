<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

try {
    if (isMockMode() || empty(getDB())) {
        echo json_encode(['data' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db = getDB();

    // Ensure status column exists in table (Auto-migration if not existing yet)
    try {
        $db->exec("ALTER TABLE oprint_pharmacy_note ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER note_remark");
    } catch (Exception $exCol) {
        // Column may already exist
    }

    // Safely query status column with fallback
    try {
        $sql = "SELECT id, vn, hn, stop_drug_name, stop_start_date, stop_end_date, consult_doctor_name, consult_time, pharmacist_name, note_remark, IFNULL(status, 'active') AS status, created_at, updated_at
                FROM oprint_pharmacy_note
                ORDER BY updated_at DESC LIMIT 500";
        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $exQuery) {
        // Fallback if status column still not found
        $sql = "SELECT id, vn, hn, stop_drug_name, stop_start_date, stop_end_date, consult_doctor_name, consult_time, pharmacist_name, note_remark, 'active' AS status, created_at, updated_at
                FROM oprint_pharmacy_note
                ORDER BY updated_at DESC LIMIT 500";
        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    ob_clean();
    echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['data' => [], 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
