<?php
require_once __DIR__ . '/config/database.php';

$vn = isset($_REQUEST['vn']) ? trim($_REQUEST['vn']) : '';
$hn = isset($_REQUEST['hn']) ? trim($_REQUEST['hn']) : '';
$paperSize = isset($_REQUEST['size']) ? trim($_REQUEST['size']) : '50x30';

$visit = null;
$pharmacyNote = null;

if (!empty($vn) || !empty($hn)) {
    if (isMockMode() || empty(getDB())) {
        $visit = [
            'vn' => !empty($vn) ? $vn : '690726003040',
            'hn' => !empty($hn) ? $hn : '660001',
            'patient_name' => 'นาย สมชาย ใจดี',
            'age' => 54,
            'drugallergy' => 'ไม่มีประวัติแพ้ยา'
        ];

        $pharmacyNote = [
            'stop_drug_name' => 'Aspirin 81 mg tab (ยาต้านเกล็ดเลือด)'
        ];
    } else {
        try {
            $db = getDB();
            $sql = "SELECT o1.vn, o1.hn, o1.vstdate, o1.vsttime,
                      Concat(p.pname, p.fname, '  ', p.lname) AS patient_name,
                      p.drugallergy,
                      TIMESTAMPDIFF(YEAR, p.birthday, CURDATE()) AS age
                    FROM ovst o1
                      LEFT OUTER JOIN patient p ON p.hn = o1.hn
                    WHERE " . (!empty($vn) ? "o1.vn = :val" : "o1.hn = :val") . "
                    ORDER BY o1.vstdate DESC LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->execute([':val' => !empty($vn) ? $vn : $hn]);
            $visit = $stmt->fetch();

            if ($visit) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['live_print']) && $_POST['live_print'] == '1') {
                    $pharmacyNote = [
                        'stop_drug_name' => $_POST['stop_drug_name'] ?? '-'
                    ];
                } else {
                    $stmtN = $db->prepare("SELECT stop_drug_name FROM oprint_pharmacy_note WHERE vn = :vn ORDER BY id DESC LIMIT 1");
                    $stmtN->execute([':vn' => $visit['vn']]);
                    $pharmacyNote = $stmtN->fetch();
                }
            }
        } catch (Exception $e) {
            // fallback
        }
    }
}

// Map sizes to CSS width/height
$dimensions = [
    '50x30' => ['w' => '50mm', 'h' => '30mm', 'fz_name' => '11pt', 'fz_base' => '8.5pt'],
    '70x40' => ['w' => '70mm', 'h' => '40mm', 'fz_name' => '13pt', 'fz_base' => '10pt'],
    '80x50' => ['w' => '80mm', 'h' => '50mm', 'fz_name' => '15pt', 'fz_base' => '12pt'],
    '100x50' => ['w' => '100mm', 'h' => '50mm', 'fz_name' => '16pt', 'fz_base' => '13pt']
];

$dim = $dimensions[$paperSize] ?? $dimensions['50x30'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>พิมพ์สติกเกอร์ (TSC TE210) - <?= htmlspecialchars($visit['hn'] ?? '-') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Anuphan', sans-serif;
            background: #e2e8f0;
            color: #000;
        }
        .print-toolbar {
            background: #0f172a;
            color: #fff;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sticker-page {
            background: #fff;
            width: <?= $dim['w'] ?>;
            height: <?= $dim['h'] ?>;
            margin: 10px auto;
            padding: 2mm 3mm;
            border: 1px solid #000;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .row-hn {
            display: flex;
            justify-content: space-between;
            font-size: <?= $dim['fz_base'] ?>;
            font-weight: 800;
            border-bottom: 1px solid #000;
            padding-bottom: 1px;
            margin-bottom: 2px;
        }

        .patient-name {
            font-size: <?= $dim['fz_name'] ?>;
            font-weight: 800;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .allergy-box {
            font-size: <?= $dim['fz_base'] ?>;
            font-weight: 700;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .drug-box {
            font-size: <?= $dim['fz_base'] ?>;
            font-weight: 800;
            background: #000;
            color: #fff;
            padding: 1px 3px;
            border-radius: 2px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 2px;
        }

        @media print {
            body, html {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .print-toolbar, .no-print {
                display: none !important;
            }
            .sticker-page {
                width: <?= $dim['w'] ?> !important;
                height: <?= $dim['h'] ?> !important;
                margin: 0 !important;
                padding: 1mm 2mm !important;
                border: none !important;
                box-shadow: none !important;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            @page {
                size: <?= $dim['w'] ?> <?= $dim['h'] ?>;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <div class="print-toolbar no-print">
        <div style="font-size: 0.9rem;">
            <strong>เครื่องพิมพ์: TSC TE210</strong> | ขนาด: <?= htmlspecialchars($paperSize) ?> mm
        </div>
        <div>
            <button onclick="window.print()" style="background: #10b981; color: white; border: none; padding: 6px 16px; border-radius: 4px; font-weight: bold; cursor: pointer;">
                พิมพ์สติกเกอร์
            </button>
            <button onclick="window.close()" style="background: #64748b; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; margin-left: 8px;">
                ปิดหน้าต่าง
            </button>
        </div>
    </div>

    <?php
        $allergyText = trim($visit['drugallergy'] ?? '');
        if (empty($allergyText) || $allergyText == 'ไม่มีประวัติแพ้ยา' || $allergyText == 'ไม่มี') {
            $allergyDisplay = "แพ้ยา: ไม่มี";
        } else {
            $allergyDisplay = "แพ้ยา: " . $allergyText;
        }
    ?>

    <!-- Sticker Canvas -->
    <div class="sticker-page">
        <!-- Top Row: HN & Age -->
        <div class="row-hn">
            <span>HN: <?= htmlspecialchars($visit['hn'] ?? '-') ?></span>
            <span>อายุ: <?= htmlspecialchars($visit['age'] ?? '-') ?> ปี</span>
        </div>
        
        <!-- Middle: Name -->
        <div class="patient-name">
            <?= htmlspecialchars($visit['patient_name'] ?? 'ไม่พบชื่อผู้ป่วย') ?>
        </div>

        <!-- Middle: Allergy -->
        <div class="allergy-box">
            <?= htmlspecialchars($allergyDisplay) ?>
        </div>

        <!-- Bottom: Stop Drug Name -->
        <?php if (!empty($pharmacyNote['stop_drug_name']) && $pharmacyNote['stop_drug_name'] !== '-'): ?>
            <div class="drug-box">
                หยุด: <?= htmlspecialchars($pharmacyNote['stop_drug_name']) ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto print dialog when loaded
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 600);
        });
    </script>
</body>
</html>
