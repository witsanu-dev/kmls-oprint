<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$hn = isset($_GET['hn']) ? trim($_GET['hn']) : '';

if (empty($hn)) {
    echo json_encode(['status' => 'error', 'message' => 'HN is required'], JSON_UNESCAPED_UNICODE);
    exit;
}

$patientInfo = null;
$historyList = [];

if (isMockMode() || empty(getDB())) {
    // Mock patient details
    $patientInfo = [
        'hn' => $hn,
        'fname' => 'สมชาย',
        'lname' => 'ใจดี',
        'pname' => 'นาย',
        'fullname' => 'นาย สมชาย ใจดี',
        'cid' => '1409900123456',
        'age' => 54,
        'sex' => 'ชาย',
        'birthday' => '1970-05-15',
        'bloodgrp' => 'O',
        'addr' => '123 ม.1 ต.กมลาไสย อ.กมลาไสย จ.กาฬสินธุ์',
        'drugallergy' => 'ไม่มีประวัติแพ้ยา',
        'bps' => '128',
        'bpd' => '82',
        'pulse' => '76',
        'weight' => '65.5',
        'height' => '168'
    ];

    $historyList = [
        [
            'id' => 1,
            'screen_date' => date('Y-m-d'),
            'screen_time' => date('H:i:s'),
            'va_ra_distance' => '6/12',
            'va_la_distance' => '6/9',
            'va_ra_with_glass' => '6/6',
            'va_la_with_glass' => '6/6',
            'va_ra_pinhole' => '6/6',
            'va_la_pinhole' => '6/6',
            'iop_re' => '14',
            'iop_le' => '15',
            'cataract_re' => 'mild',
            'cataract_le' => 'normal',
            'dr_re' => 'No DR',
            'dr_le' => 'No DR',
            'diagnosis_icd10' => 'H25.9 - Senile cataract, unspecified',
            'doctor_notes' => 'ตามัวข้างขวาเล็กน้อย ตรวจพบต้อกระจกระยะเริ่มต้น นัดติดตามอาการ 6 เดือน',
            'examiner_name' => 'พญ. พิมพรรณ สายตาดี'
        ]
    ];
} else {
    try {
        $db = getDB();

        // 1. Get HOSxP Demographics
        $stmtP = $db->prepare("SELECT hn, fname, lname, pname, cid, birthday, sex, informaddr, bloodgrp, drugallergy,
                                TIMESTAMPDIFF(YEAR, birthday, CURDATE()) AS age 
                                FROM patient WHERE hn = :hn LIMIT 1");
        $stmtP->execute([':hn' => $hn]);
        $p = $stmtP->fetch();

        if ($p) {
            $sexStr = ($p['sex'] == '1' ? 'ชาย' : ($p['sex'] == '2' ? 'หญิง' : 'ไม่ระบุ'));
            $fullName = trim(($p['pname'] ?? '') . ' ' . $p['fname'] . ' ' . $p['lname']);
            $patientInfo = [
                'hn' => $p['hn'],
                'fname' => $p['fname'],
                'lname' => $p['lname'],
                'pname' => $p['pname'],
                'fullname' => $fullName,
                'cid' => $p['cid'],
                'age' => $p['age'],
                'sex' => $sexStr,
                'birthday' => $p['birthday'],
                'bloodgrp' => $p['bloodgrp'] ?? '-',
                'addr' => $p['informaddr'] ?? '-',
                'drugallergy' => $p['drugallergy'] ?? 'ไม่มีประวัติแพ้ยา'
            ];

            // 2. Fetch Latest Vital Signs from opdscreen if available
            try {
                $stmtVs = $db->prepare("SELECT bps, bpd, pulse, bw AS weight, height FROM opdscreen WHERE hn = :hn ORDER BY vstdate DESC, vsttime DESC LIMIT 1");
                $stmtVs->execute([':hn' => $hn]);
                $vs = $stmtVs->fetch();
                if ($vs) {
                    $patientInfo['bps'] = $vs['bps'] ?? '';
                    $patientInfo['bpd'] = $vs['bpd'] ?? '';
                    $patientInfo['pulse'] = $vs['pulse'] ?? '';
                    $patientInfo['weight'] = $vs['weight'] ?? '';
                    $patientInfo['height'] = $vs['height'] ?? '';
                }
            } catch (Exception $ex) {
                // opdscreen table optional fallback
            }

            // 3. Fetch Eye Screening History
            try {
                $stmtH = $db->prepare("SELECT * FROM oprint_eye_screening WHERE hn = :hn ORDER BY screen_date DESC, screen_time DESC");
                $stmtH->execute([':hn' => $hn]);
                $historyList = $stmtH->fetchAll();
            } catch (Exception $ex) {
                $historyList = [];
            }
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

echo json_encode([
    'status' => 'success',
    'patient' => $patientInfo,
    'history' => $historyList,
    'mock' => isMockMode()
], JSON_UNESCAPED_UNICODE);
