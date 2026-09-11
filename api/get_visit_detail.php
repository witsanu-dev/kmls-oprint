<?php
// Prevent any buffer output or warning messages before JSON header
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config/database.php';

$vn = isset($_GET['vn']) ? trim($_GET['vn']) : '';
$hn = isset($_GET['hn']) ? trim($_GET['hn']) : '';

if (empty($vn) && empty($hn)) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'VN or HN is required'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper Function: Format Thai Date Time (e.g., 10 กันยายน 2569 21:01 น.)
function formatThaiDateTime($dateStr, $timeStr = '') {
    if (empty($dateStr) || $dateStr === '0000-00-00') return '-';
    $thaiMonths = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    $ts = strtotime($dateStr);
    if (!$ts) return $dateStr;

    $day = date('j', $ts);
    $monthNum = (int)date('n', $ts);
    $year = (int)date('Y', $ts) + 543;
    $monthThai = isset($thaiMonths[$monthNum]) ? $thaiMonths[$monthNum] : '';

    $formatted = "{$day} {$monthThai} {$year}";
    if (!empty($timeStr) && $timeStr !== '00:00:00') {
        $formatted .= ' เวลา ' . date('H:i', strtotime($timeStr)) . ' น.';
    }
    return $formatted;
}

$visitData = null;
$pharmacyNote = null;

// PRODUCTION ONLY: Query HOSxP Live Data Directly
if (!empty(getDB())) {
    try {
        $db = getDB();

        // คำนวณรูปแบบ HN หลากหลายแบบ
        $cleanVal = !empty($vn) ? $vn : $hn;
        $numericVal = preg_replace('/[^0-9]/', '', $cleanVal);
        $hnRaw  = $cleanVal;
        $hnInt  = !empty($numericVal) ? (string)intval($numericVal) : $cleanVal;
        $hnPad7 = !empty($numericVal) ? sprintf("%07d", intval($numericVal)) : $cleanVal;
        $hnPad9 = !empty($numericVal) ? sprintf("%09d", intval($numericVal)) : $cleanVal;

        if (!empty($vn)) {
            $whereClause = "o1.vn = :val";
            $params = [':val' => $vn];
        } else {
            $whereClause = "(o1.hn = :hn1 OR o1.hn = :hn2 OR o1.hn = :hn3 OR o1.hn = :hn4)";
            $params = [
                ':hn1' => $hnRaw,
                ':hn2' => $hnInt,
                ':hn3' => $hnPad7,
                ':hn4' => $hnPad9
            ];
        }

        $sql = "SELECT o1.vn, o1.hn, o1.vstdate, o1.vsttime,
                  CONCAT(IFNULL(p.pname,''), IFNULL(p.fname,''), '  ', IFNULL(p.lname,'')) AS patient_name,
                  IFNULL(p.drugallergy, 'ไม่มีประวัติแพ้ยา') AS drugallergy,
                  IFNULL(p.clinic, 'ไม่มีข้อมูลโรคประจำตัว') AS patient_clinic,
                  p.hometel AS pt_tel,
                  p.informaddr,
                  p.bloodgrp,
                  p.cid,
                  p.birthday,
                  (CASE WHEN p.sex = '1' THEN 'ชาย' WHEN p.sex = '2' THEN 'หญิง' ELSE 'ไม่ระบุ' END) AS sex,
                  TIMESTAMPDIFF(YEAR, p.birthday, CURDATE()) AS age,
                  IFNULL(p2.name, 'ไม่ระบุสิทธิ') AS pttype_name,
                  s.bps, s.bpd, s.pulse, s.bw, s.height
                FROM ovst o1
                  LEFT OUTER JOIN vn_stat v1 ON v1.vn = o1.vn
                  LEFT OUTER JOIN opdscreen s ON s.vn = o1.vn
                  LEFT OUTER JOIN patient p ON p.hn = o1.hn
                  LEFT OUTER JOIN pttype p2 ON p2.pttype = v1.pttype
                WHERE {$whereClause}
                ORDER BY o1.vn DESC LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $visitData = $stmt->fetch();

        if ($visitData) {
            // Add formatted Thai date time
            $visitData['vstdate_thai'] = formatThaiDateTime($visitData['vstdate'], $visitData['vsttime']);

            // Fetch Existing Pharmacy Note if recorded
            try {
                $stmtNote = $db->prepare("SELECT * FROM oprint_pharmacy_note WHERE vn = :vn ORDER BY id DESC LIMIT 1");
                $stmtNote->execute([':vn' => $visitData['vn']]);
                $pharmacyNote = $stmtNote->fetch() ?: null;
            } catch (Exception $ex) {
                $pharmacyNote = null;
            }

            ob_clean();
            echo json_encode([
                'status' => 'success',
                'visit' => $visitData,
                'pharmacy_note' => $pharmacyNote,
                'mock' => false
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            ob_clean();
            echo json_encode([
                'status' => 'error',
                'message' => "ไม่พบข้อมูล Visit (VN: {$vn} / HN: {$hn}) ในระบบ",
                'mock' => false
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (Exception $e) {
        error_log("get_visit_detail error: " . $e->getMessage());
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูลระบบ: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

ob_clean();
echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลระบบได้'], JSON_UNESCAPED_UNICODE);
