<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$type = isset($_GET['type']) ? trim($_GET['type']) : 'drugs';
$term = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['term']) ? trim($_GET['term']) : '');
$results = [];

if (isMockMode() || empty(getDB())) {
    if ($type === 'drugs') {
        $drugs = [
            ['id' => '1000001', 'text' => 'Aspirin 81 mg tab (ยาต้านเกล็ดเลือด)'],
            ['id' => '1000002', 'text' => 'Aspirin 300 mg tab (ยาต้านเกล็ดเลือด)'],
            ['id' => '1000003', 'text' => 'Clopidogrel 75 mg tab (Plavix)'],
            ['id' => '1000004', 'text' => 'Warfarin 2 mg tab (ยาต้านการแข็งตัวของเลือด)'],
            ['id' => '1000005', 'text' => 'Warfarin 3 mg tab (Coumadin)'],
            ['id' => '1000006', 'text' => 'Warfarin 5 mg tab'],
            ['id' => '1000007', 'text' => 'Rivaroxaban 15 mg tab (Xarelto)'],
            ['id' => '1000008', 'text' => 'Rivaroxaban 20 mg tab (Xarelto)'],
            ['id' => '1000009', 'text' => 'Apixaban 2.5 mg tab (Eliquis)'],
            ['id' => '1000010', 'text' => 'Apixaban 5 mg tab (Eliquis)'],
            ['id' => '1000011', 'text' => 'Dabigatran 110 mg cap (Pradaxa)']
        ];
        foreach ($drugs as $d) {
            if (empty($term) || stripos($d['text'], $term) !== false) {
                $results[] = $d;
            }
        }
    } else {
        $doctors = [
            ['id' => '9901', 'text' => 'นพ. สมเกียรติ สุขเกษม (รคส.แพทย์ / จักษุแพทย์)'],
            ['id' => '9902', 'text' => 'พญ. พิมพรรณ สายตาดี (จักษุแพทย์)'],
            ['id' => '9903', 'text' => 'นพ. อภิชาติ วงศ์สว่าง (อายุรแพทย์)'],
            ['id' => '9904', 'text' => 'ภญ. พิมลพรรณ เภสัชกรดีเด่น (เภสัชกรประจำห้องยา)'],
            ['id' => '9905', 'text' => 'ภก. ธนกร เมธาวี (เภสัชกรประจำห้องยา)']
        ];
        foreach ($doctors as $doc) {
            if (empty($term) || stripos($doc['text'], $term) !== false) {
                $results[] = $doc;
            }
        }
    }
} else {
    try {
        $db = getDB();
        if ($type === 'drugs') {
            $sql = "SELECT icode AS id, CONCAT(name, ' ', IFNULL(strength, '')) AS text
                    FROM drugitems
                    WHERE (name LIKE :q1 OR icode LIKE :q2)
                    ORDER BY name ASC LIMIT 50";
            $stmt = $db->prepare($sql);
            $searchParam = '%' . $term . '%';
            $stmt->bindParam(':q1', $searchParam, PDO::PARAM_STR);
            $stmt->bindParam(':q2', $searchParam, PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll();
        } else {
            $sql = "SELECT code AS id, name AS text
                    FROM doctor
                    WHERE (name LIKE :q1 OR code LIKE :q2)
                    ORDER BY name ASC LIMIT 50";
            $stmt = $db->prepare($sql);
            $searchParam = '%' . $term . '%';
            $stmt->bindParam(':q1', $searchParam, PDO::PARAM_STR);
            $stmt->bindParam(':q2', $searchParam, PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        $results = ['error' => $e->getMessage()];
    }
}

ob_clean();
echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
exit;
