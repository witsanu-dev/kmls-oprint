<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$term = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['term']) ? trim($_GET['term']) : '');
$results = [];

if (isMockMode() || empty(getDB())) {
    // Demo Mock Data when DB connection is offline
    $mockPatients = [
        [
            'id' => '660001',
            'hn' => '660001',
            'text' => '660001 - นาย สมชาย ใจดี (CID: 1409900123456) อายุ 54 ปี',
            'fname' => 'สมชาย',
            'lname' => 'ใจดี',
            'pname' => 'นาย',
            'cid' => '1409900123456',
            'age' => 54,
            'sex' => 'ชาย',
            'birthday' => '1970-05-15',
            'bloodgrp' => 'O',
            'addr' => '123 ม.1 ต.กมลาไสย อ.กมลาไสย จ.กาฬสินธุ์',
            'drugallergy' => 'ไม่มีประวัติแพ้ยา'
        ],
        [
            'id' => '660002',
            'hn' => '660002',
            'text' => '660002 - นาง สมศรี มีสุข (CID: 3409900876543) อายุ 62 ปี',
            'fname' => 'สมศรี',
            'lname' => 'มีสุข',
            'pname' => 'นาง',
            'cid' => '3409900876543',
            'age' => 62,
            'sex' => 'หญิง',
            'birthday' => '1962-11-20',
            'bloodgrp' => 'B',
            'addr' => '45 ม.3 ต.หลักเมือง อ.กมลาไสย จ.กาฬสินธุ์',
            'drugallergy' => 'Penicillin (ผื่นคัน)'
        ],
        [
            'id' => '660003',
            'hn' => '660003',
            'text' => '660003 - นาย สมศักดิ์ เจริญพร (CID: 1400200554433) อายุ 48 ปี',
            'fname' => 'สมศักดิ์',
            'lname' => 'เจริญพร',
            'pname' => 'นาย',
            'cid' => '1400200554433',
            'age' => 48,
            'sex' => 'ชาย',
            'birthday' => '1976-03-08',
            'bloodgrp' => 'A',
            'addr' => '88 ม.5 ต.โคกสมบูรณ์ อ.กมลาไสย จ.กาฬสินธุ์',
            'drugallergy' => 'ไม่มีประวัติแพ้ยา'
        ]
    ];

    if (!empty($term)) {
        foreach ($mockPatients as $p) {
            if (stripos($p['hn'], $term) !== false || stripos($p['text'], $term) !== false || stripos($p['cid'], $term) !== false) {
                $results[] = $p;
            }
        }
    } else {
        $results = $mockPatients;
    }
} else {
    try {
        $db = getDB();
        $sql = "SELECT hn, fname, lname, pname, cid, birthday, sex, informaddr,
                TIMESTAMPDIFF(YEAR, birthday, CURDATE()) AS age
                FROM patient 
                WHERE hn LIKE :query 
                   OR cid LIKE :query 
                   OR fname LIKE :query 
                   OR lname LIKE :query 
                   OR CONCAT(fname, ' ', lname) LIKE :query
                ORDER BY hn DESC 
                LIMIT 30";
        $stmt = $db->prepare($sql);
        $searchParam = '%' . $term . '%';
        $stmt->bindParam(':query', $searchParam, PDO::PARAM_STR);
        $stmt->execute();
        $patients = $stmt->fetchAll();

        foreach ($patients as $p) {
            $sexStr = ($p['sex'] == '1' ? 'ชาย' : ($p['sex'] == '2' ? 'หญิง' : 'ไม่ระบุ'));
            $fullName = trim(($p['pname'] ?? '') . ' ' . $p['fname'] . ' ' . $p['lname']);
            $results[] = [
                'id' => $p['hn'],
                'hn' => $p['hn'],
                'text' => "HN {$p['hn']} - {$fullName} (CID: {$p['cid']}) อายุ {$p['age']} ปี",
                'fname' => $p['fname'],
                'lname' => $p['lname'],
                'pname' => $p['pname'],
                'cid' => $p['cid'],
                'age' => $p['age'],
                'sex' => $sexStr,
                'birthday' => $p['birthday'],
                'addr' => $p['informaddr']
            ];
        }
    } catch (Exception $e) {
        $results = ['error' => $e->getMessage()];
    }
}

echo json_encode(['results' => $results, 'mock' => isMockMode()], JSON_UNESCAPED_UNICODE);
