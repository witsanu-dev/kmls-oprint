<?php
// Prevent buffer issues
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config/database.php';

$hn = isset($_GET['hn']) ? trim($_GET['hn']) : '';
$current_vn = isset($_GET['current_vn']) ? trim($_GET['current_vn']) : '';

if (empty($hn)) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'HN is required'], JSON_UNESCAPED_UNICODE);
    exit;
}

$visits = [];

// Helper Function: Format Thai Date Time
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
        $formatted .= ' ' . date('H:i', strtotime($timeStr)) . ' น.';
    }
    return $formatted;
}

if (!empty(getDB())) {
    try {
        $db = getDB();

        // Calculate HN Formats
        $numericHn = preg_replace('/[^0-9]/', '', $hn);
        $hnRaw  = $hn;
        $hnInt  = !empty($numericHn) ? (string)intval($numericHn) : $hn;
        $hnPad7 = !empty($numericHn) ? sprintf("%07d", intval($numericHn)) : $hn;
        $hnPad9 = !empty($numericHn) ? sprintf("%09d", intval($numericHn)) : $hn;

        // Query 5 Recent Visits of this specific patient from HOSxP
        $sql = "SELECT o1.vn, o1.vstdate, o1.vsttime,
                       CONCAT(IFNULL(p.pname,''), IFNULL(p.fname,''), ' ', IFNULL(p.lname,'')) AS patient_name,
                       s.bps, s.bpd, s.pulse, s.bw
                FROM ovst o1
                LEFT JOIN patient p ON p.hn = o1.hn
                LEFT JOIN opdscreen s ON s.vn = o1.vn
                WHERE o1.hn = :hn1 OR o1.hn = :hn2 OR o1.hn = :hn3 OR o1.hn = :hn4
                ORDER BY o1.vstdate DESC, o1.vsttime DESC
                LIMIT 5";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':hn1' => $hnRaw,
            ':hn2' => $hnInt,
            ':hn3' => $hnPad7,
            ':hn4' => $hnPad9
        ]);
        $rows = $stmt->fetchAll();

        foreach ($rows as $r) {
            $visits[] = [
                'vn' => $r['vn'],
                'vstdate' => $r['vstdate'],
                'vsttime' => $r['vsttime'],
                'formatted_date' => formatThaiDateTime($r['vstdate'], $r['vsttime']),
                'short_thai_date' => formatThaiDateTime($r['vstdate']),
                'bps' => $r['bps'],
                'bpd' => $r['bpd'],
                'pulse' => $r['pulse'],
                'is_current' => ($r['vn'] === $current_vn)
            ];
        }

        ob_clean();
        echo json_encode(['status' => 'success', 'visits' => $visits, 'mock' => false], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        error_log("get_patient_visits Error: " . $e->getMessage());
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

ob_clean();
echo json_encode(['status' => 'success', 'visits' => [], 'mock' => false], JSON_UNESCAPED_UNICODE);
