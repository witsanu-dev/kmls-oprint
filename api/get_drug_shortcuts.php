<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$results = [
    'aspirin' => [],
    'warfarin' => [],
    'clopidogrel' => []
];

if (!isMockMode() && !empty(getDB())) {
    try {
        $db = getDB();
        $sql = "SELECT icode AS id, CONCAT(name, ' ', IFNULL(strength, '')) AS text
                FROM drugitems
                WHERE (name LIKE '%aspirin%' OR name LIKE '%warfarin%' OR name LIKE '%clopidogrel%')
                ORDER BY name ASC LIMIT 50";
        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll();
        
        foreach ($rows as $row) {
            $nameLower = strtolower($row['text']);
            if (strpos($nameLower, 'aspirin') !== false) {
                $results['aspirin'][] = $row;
            } elseif (strpos($nameLower, 'warfarin') !== false) {
                $results['warfarin'][] = $row;
            } elseif (strpos($nameLower, 'clopidogrel') !== false) {
                $results['clopidogrel'][] = $row;
            }
        }
    } catch (Exception $e) {
        // Fallback to mock or empty
    }
}

ob_clean();
echo json_encode(['status' => 'success', 'data' => $results], JSON_UNESCAPED_UNICODE);
exit;
