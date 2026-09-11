<?php
// Prevent any buffer output or warning messages before JSON header
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config/database.php';

$term = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['term']) ? trim($_GET['term']) : '');
$results = [];

if (!empty(getDB())) {
    try {
        $db = getDB();
        $visits = [];
        
        if (empty($term)) {
            // ดึง 30 visit ล่าสุดเรียงตามวันเวลาตรวจแบบรวดเร็วที่สุด
            $sql = "SELECT o1.vn, o1.hn, o1.vstdate, o1.vsttime,
                           CONCAT(IFNULL(p.pname,''), IFNULL(p.fname,''), ' ', IFNULL(p.lname,'')) AS patient_name,
                           p.cid, TIMESTAMPDIFF(YEAR, p.birthday, CURDATE()) AS age
                    FROM ovst o1
                    LEFT JOIN patient p ON p.hn = o1.hn
                    ORDER BY o1.vstdate DESC, o1.vsttime DESC
                    LIMIT 30";
            $stmt = $db->query($sql);
            $visits = $stmt->fetchAll();
        } else {
            $cleanTerm = trim($term);
            $numericTerm = preg_replace('/[^0-9]/', '', $cleanTerm);

            // แยกคำค้นหาตามช่องว่างกรณีพิมพ์ "วิษณุ ศรีโยธา"
            $nameParts = preg_split('/\s+/', $cleanTerm);
            $fname = isset($nameParts[0]) ? $nameParts[0] : $cleanTerm;
            $lname = isset($nameParts[1]) ? $nameParts[1] : '';

            // รูปแบบ HN หลากหลาย
            $hnRaw  = $cleanTerm;
            $hnInt  = !empty($numericTerm) ? (string)intval($numericTerm) : $cleanTerm;
            $hnPad7 = !empty($numericTerm) ? sprintf("%07d", intval($numericTerm)) : $cleanTerm;
            $hnPad9 = !empty($numericTerm) ? sprintf("%09d", intval($numericTerm)) : $cleanTerm;

            // 1. ค้นหาจาก patient ก่อน เพื่อหา HN ทั้งหมดของคนไข้รายนี้ (ไม่จำกัดเฉพาะต้องมี visit วันนี้)
            $matchingHns = [];
            
            // ค้นหา patient
            $pSql = "SELECT hn FROM patient 
                     WHERE hn = :hn1 OR hn = :hn2 OR hn = :hn3 OR hn = :hn4 
                        OR cid = :cid 
                        OR cid LIKE :cid_like";
            $pParams = [
                ':hn1'      => $hnRaw,
                ':hn2'      => $hnInt,
                ':hn3'      => $hnPad7,
                ':hn4'      => $hnPad9,
                ':cid'      => $numericTerm,
                ':cid_like' => '%' . $cleanTerm . '%'
            ];

            if (!empty($lname)) {
                $pSql .= " OR (fname LIKE :fname AND lname LIKE :lname)";
                $pParams[':fname'] = '%' . $fname . '%';
                $pParams[':lname'] = '%' . $lname . '%';
            } else {
                $pSql .= " OR fname LIKE :fname OR lname LIKE :lname OR CONCAT(pname, fname, ' ', lname) LIKE :fullname";
                $pParams[':fname']    = '%' . $cleanTerm . '%';
                $pParams[':lname']    = '%' . $cleanTerm . '%';
                $pParams[':fullname'] = '%' . $cleanTerm . '%';
            }

            $pStmt = $db->prepare($pSql);
            $pStmt->execute($pParams);
            $pRows = $pStmt->fetchAll();

            foreach ($pRows as $pr) {
                if (!empty($pr['hn'])) {
                    $matchingHns[] = $pr['hn'];
                }
            }

            // 2. ดึง visit ล่าสุดของ HN ที่เจอ หรือค้นด้วย VN ตรงๆ
            if (!empty($matchingHns)) {
                // Construct IN clause dynamically
                $inClause = implode(',', array_fill(0, count($matchingHns), '?'));
                $vSql = "SELECT o1.vn, o1.hn, o1.vstdate, o1.vsttime,
                                CONCAT(IFNULL(p.pname,''), IFNULL(p.fname,''), ' ', IFNULL(p.lname,'')) AS patient_name,
                                p.cid, TIMESTAMPDIFF(YEAR, p.birthday, CURDATE()) AS age
                         FROM ovst o1
                         LEFT JOIN patient p ON p.hn = o1.hn
                         WHERE o1.hn IN ($inClause)
                         ORDER BY o1.vstdate DESC, o1.vsttime DESC
                         LIMIT 30";
                $vStmt = $db->prepare($vSql);
                $vStmt->execute($matchingHns);
                $visits = $vStmt->fetchAll();
            }

            // 3. ถ้ายังไม่พบจาก patient (เผื่อกรณีไม่มีในตาราง patient แต่มีใน ovst หรือค้นด้วย VN)
            if (empty($visits)) {
                $vSql2 = "SELECT o1.vn, o1.hn, o1.vstdate, o1.vsttime,
                                 CONCAT(IFNULL(p.pname,''), IFNULL(p.fname,''), ' ', IFNULL(p.lname,'')) AS patient_name,
                                 p.cid, TIMESTAMPDIFF(YEAR, p.birthday, CURDATE()) AS age
                          FROM ovst o1
                          LEFT JOIN patient p ON p.hn = o1.hn
                          WHERE o1.vn LIKE :q_vn OR o1.hn = :hn1 OR o1.hn = :hn2 OR o1.hn = :hn3
                          ORDER BY o1.vstdate DESC, o1.vsttime DESC
                          LIMIT 30";
                $vStmt2 = $db->prepare($vSql2);
                $vStmt2->execute([
                    ':q_vn' => '%' . $cleanTerm . '%',
                    ':hn1'  => $hnRaw,
                    ':hn2'  => $hnInt,
                    ':hn3'  => $hnPad7
                ]);
                $visits = $vStmt2->fetchAll();
            }
        }

        if (!empty($visits)) {
            // Bulk-check which VNs already have a pharmacy note and their status
            $vnList = array_column($visits, 'vn');
            $inPlaceholders = implode(',', array_fill(0, count($vnList), '?'));
            
            // Check status column if exists
            try {
                $noteStmt = $db->prepare("SELECT vn, IFNULL(status, 'active') AS note_status FROM oprint_pharmacy_note WHERE vn IN ($inPlaceholders)");
                $noteStmt->execute($vnList);
                $noteRows = $noteStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $eStatus) {
                $noteStmt = $db->prepare("SELECT vn, 'active' AS note_status FROM oprint_pharmacy_note WHERE vn IN ($inPlaceholders)");
                $noteStmt->execute($vnList);
                $noteRows = $noteStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $notedMap = [];
            foreach ($noteRows as $nr) {
                $notedMap[$nr['vn']] = $nr['note_status'];
            }

            foreach ($visits as $v) {
                $pName = trim($v['patient_name']) ?: 'ไม่ระบุชื่อ';
                $nStatus = isset($notedMap[$v['vn']]) ? $notedMap[$v['vn']] : null;
                $results[] = [
                    'id'           => $v['vn'],
                    'vn'           => $v['vn'],
                    'hn'           => $v['hn'],
                    'text'         => "VN: {$v['vn']} | HN: {$v['hn']} | {$pName} | วันที่: {$v['vstdate']} {$v['vsttime']}",
                    'vstdate'      => $v['vstdate'],
                    'vsttime'      => $v['vsttime'],
                    'patient_name' => $pName,
                    'cid'          => $v['cid'],
                    'age'          => $v['age'],
                    'has_note'     => ($nStatus !== null && $nStatus !== 'cancelled'),
                    'note_status'  => $nStatus // 'active', 'cancelled', or null
                ];
            }
        }

        ob_clean();
        echo json_encode(['results' => $results, 'mock' => false], JSON_UNESCAPED_UNICODE);
        exit;


    } catch (Exception $e) {
        error_log("Production HOSxP Search Exception: " . $e->getMessage());
        ob_clean();
        echo json_encode(['results' => [], 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

ob_clean();
echo json_encode(['results' => [], 'mock' => false], JSON_UNESCAPED_UNICODE);
