<?php
require_once __DIR__ . '/config/database.php';

$vn = isset($_REQUEST['vn']) ? trim($_REQUEST['vn']) : '';
$hn = isset($_REQUEST['hn']) ? trim($_REQUEST['hn']) : '';
$paperSize = isset($_REQUEST['size']) ? trim($_REQUEST['size']) : 'a5-portrait';

$visit = null;
$pharmacyNote = null;

if (!empty($vn) || !empty($hn)) {
    if (isMockMode() || empty(getDB())) {
        $visit = [
            'vn' => !empty($vn) ? $vn : '690726003040',
            'hn' => !empty($hn) ? $hn : '660001',
            'vstdate' => date('Y-m-d'),
            'vsttime' => date('H:i:s'),
            'patient_name' => 'นาย สมชาย ใจดี',
            'cid' => '1409900123456',
            'age' => 54,
            'sex' => 'ชาย',
            'birthday' => '1970-05-15',
            'bloodgrp' => 'O',
            'informaddr' => '123 ม.1 ต.กมลาไสย อ.กมลาไสย จ.กาฬสินธุ์',
            'pt_tel' => '081-234-5678',
            'pttype_name' => 'สิทธิหลักประกันสุขภาพแห่งชาติ (บัตรทอง 30 บาท)',
            'drugallergy' => 'ไม่มีประวัติแพ้ยา',
            'patient_clinic' => 'เบาหวาน, ความดันโลหิตสูง (DM, HT)'
        ];

        $pharmacyNote = [
            'stop_drug_icode' => '1000001',
            'stop_drug_name' => 'Aspirin 81 mg tab (ยาต้านเกล็ดเลือด)',
            'stop_start_date' => date('Y-m-d', strtotime('-3 days')),
            'stop_end_date' => date('Y-m-d', strtotime('+4 days')),
            'consult_doctor_code' => '9901',
            'consult_doctor_name' => 'นพ. สมเกียรติ สุขเกษม (รคส.แพทย์)',
            'consult_time' => '10:30',
            'pharmacist_name' => 'ภญ. พิมลพรรณ เภสัชกรดีเด่น',
            'note_remark' => 'แนะนำผู้ป่วยงดยาก่อนเข้ารับการผ่าตัดอย่างน้อย 7 วัน'
        ];
    } else {
        try {
            $db = getDB();
            $sql = "SELECT o1.vn, o1.hn, o1.vstdate, o1.vsttime,
                      Concat(p.pname, p.fname, '  ', p.lname) AS patient_name,
                      p.drugallergy,
                      p.clinic AS patient_clinic,
                      p.hometel AS pt_tel,
                      p.informaddr,
                      p.bloodgrp,
                      p.cid,
                      p.birthday,
                      (CASE WHEN p.sex = '1' THEN 'ชาย' WHEN p.sex = '2' THEN 'หญิง' ELSE 'ไม่ระบุ' END) AS sex,
                      TIMESTAMPDIFF(YEAR, p.birthday, CURDATE()) AS age,
                      p2.name AS pttype_name
                    FROM ovst o1
                      LEFT OUTER JOIN vn_stat v1 ON v1.vn = o1.vn
                      LEFT OUTER JOIN patient p ON p.hn = o1.hn
                      LEFT OUTER JOIN pttype p2 ON p2.pttype = v1.pttype
                    WHERE " . (!empty($vn) ? "o1.vn = :val" : "o1.hn = :val") . "
                    ORDER BY o1.vstdate DESC LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->execute([':val' => !empty($vn) ? $vn : $hn]);
            $visit = $stmt->fetch();

            if ($visit) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['live_print']) && $_POST['live_print'] == '1') {
                    $pharmacyNote = [
                        'stop_drug_name' => $_POST['stop_drug_name'] ?? '-',
                        'stop_start_date' => $_POST['stop_start_date'] ?? '-',
                        'stop_end_date' => $_POST['stop_end_date'] ?? '-',
                        'consult_doctor_name' => $_POST['consult_doctor_name'] ?? '-',
                        'consult_time' => $_POST['consult_time'] ?? '-',
                        'pharmacist_name' => $_POST['pharmacist_name'] ?? '-',
                        'note_remark' => $_POST['note_remark'] ?? '-'
                    ];
                } else {
                    $stmtN = $db->prepare("SELECT * FROM oprint_pharmacy_note WHERE vn = :vn ORDER BY id DESC LIMIT 1");
                    $stmtN->execute([':vn' => $visit['vn']]);
                    $pharmacyNote = $stmtN->fetch();
                }
            }
        } catch (Exception $e) {
            // fallback
        }
    }
}
// Generate Thai date/time for print timestamp
$thaiMonths = [
    1=>'มกราคม',2=>'กุมภาพันธ์',3=>'มีนาคม',4=>'เมษายน',
    5=>'พฤษภาคม',6=>'มิถุนายน',7=>'กรกฎาคม',8=>'สิงหาคม',
    9=>'กันยายน',10=>'ตุลาคม',11=>'พฤศจิกายน',12=>'ธันวาคม'
];
$printDay   = (int)date('j');
$printMonth = $thaiMonths[(int)date('n')];
$printYear  = (int)date('Y') + 543;
$printTime  = date('H:i');
$printedAt  = "พิมพ์เมื่อ: {$printDay} {$printMonth} พ.ศ.{$printYear} เวลา {$printTime} น.";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>พิมพ์ Pharmacy Note OPD Card - โรงพยาบาลกมลาไสย (VN: <?= htmlspecialchars($visit['vn'] ?? '-') ?>)</title>
    <link rel="stylesheet" href="assets/css/print.css">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        @media print {
            @page {
                <?php if ($paperSize == 'a5-portrait'): ?>
                    size: A5 portrait;
                <?php elseif ($paperSize == 'a4-portrait'): ?>
                    size: A4 portrait;
                <?php else: ?>
                    size: A5 landscape;
                <?php endif; ?>
                margin: 4mm;
            }
        }
    </style>
</head>
<body>

    <!-- Print Control Toolbar -->
    <div class="print-toolbar no-print" style="background: #0f172a; color: white; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <strong>ขนาดกระดาษพิมพ์:</strong> 
            <select id="paperSizeSelect" onchange="changePaperSize(this.value)" style="padding: 4px 8px; border-radius: 4px;">
                <option value="a5-portrait" <?= $paperSize == 'a5-portrait' ? 'selected' : '' ?>>A5 แนวตั้ง (ค่าเริ่มต้น)</option>
                <option value="a5-landscape" <?= $paperSize == 'a5-landscape' ? 'selected' : '' ?>>A5 แนวนอน</option>
                <option value="a4-portrait" <?= $paperSize == 'a4-portrait' ? 'selected' : '' ?>>A4 แนวตั้ง</option>
            </select>
        </div>
        <div>
            <button onclick="window.print()" style="background: #f97316; color: white; border: none; padding: 6px 18px; border-radius: 4px; font-weight: bold; cursor: pointer;">
                ปริ้นท์ใบ OPD Card (Pharmacy Note)
            </button>
            <button onclick="window.close()" style="background: #64748b; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; margin-left: 8px;">
                ปิดหน้าต่าง
            </button>
        </div>
    </div>

    <!-- OPD Document Sheet -->
    <div class="opd-page page-size-<?= htmlspecialchars($paperSize) ?>">

        <!-- OPD Header -->
        <div class="opd-header">
            <div>
                <div class="hospital-title">โรงพยาบาลกมลาไสย (Kamalasai Hospital)</div>
                <div class="sub-title">ใบตรวจรักษาผู้ป่วยนอก — คำสั่งหยุดยาละลายลิ่มเลือด (Pharmacy Note)</div>
                <div style="font-size: 7.5pt; color: #64748b; margin-top: 2px;"><?= htmlspecialchars($printedAt) ?></div>
            </div>
            <div style="text-align: right;">
                <div class="hn-print-badge">HN: <?= htmlspecialchars($visit['hn'] ?? '-') ?></div>
                <div style="font-size: 7.5pt; margin-top: 2px; font-weight: bold;">VN: <?= htmlspecialchars($visit['vn'] ?? '-') ?></div>
            </div>
        </div>

        <!-- Patient Demographics Summary -->
        <div class="patient-box">
            <div class="row-flex">
                <div class="col" style="flex: 2.5;"><span class="label-bold">ชื่อ-สกุล:</span> <?= htmlspecialchars($visit['patient_name'] ?? '-') ?></div>
                <div class="col"><span class="label-bold">อายุ:</span> <?= htmlspecialchars($visit['age'] ?? '-') ?> ปี</div>
                <div class="col"><span class="label-bold">เพศ:</span> <?= htmlspecialchars($visit['sex'] ?? '-') ?></div>
                <div class="col"><span class="label-bold">กรุ๊ปเลือด:</span> <?= htmlspecialchars($visit['bloodgrp'] ?? '-') ?></div>
                <div class="col" style="flex: 1.8;"><span class="label-bold">CID:</span> <?= htmlspecialchars($visit['cid'] ?? '-') ?></div>
            </div>
            <div class="row-flex" style="margin-top: 2px;">
                <div class="col" style="flex: 2.5;"><span class="label-bold">สิทธิ:</span> <?= htmlspecialchars($visit['pttype_name'] ?? '-') ?></div>
                <div class="col"><span class="label-bold">โทร:</span> <?= htmlspecialchars($visit['pt_tel'] ?? '-') ?></div>
                <div class="col" style="flex: 1.8;"><span class="label-bold">วันที่ตรวจ:</span> <?= htmlspecialchars($visit['vstdate'] ?? date('Y-m-d')) ?></div>
            </div>
            <div class="row-flex" style="margin-top: 2px; border-top: 1px dashed #cbd5e1; padding-top: 2px;">
                <div class="col" style="flex: 2;"><span class="label-bold">ที่อยู่:</span> <?= htmlspecialchars($visit['informaddr'] ?? '-') ?></div>
                <div class="col"><span class="label-bold">โรคประจำตัว:</span> <?= htmlspecialchars($visit['patient_clinic'] ?? 'ไม่มี') ?></div>
                <div class="col"><span class="label-bold">แพ้ยา:</span> <span style="color:#dc2626;font-weight:700;"><?= htmlspecialchars($visit['drugallergy'] ?? 'ไม่มี') ?></span></div>
            </div>
        </div>

        <!-- Pharmacy Note Table -->
        <table class="pharmacy-table">
            <thead>
                <tr>
                    <th style="width: 38%;">รายการยาที่ให้หยุด</th>
                    <th style="width: 25%;">ระยะเวลาหยุดยา</th>
                    <th style="width: 37%;">รคส.แพทย์ / เวลา Consult / หมายเหตุ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight: 800; color: #0f172a; font-size: 10pt;">
                        <?= htmlspecialchars($pharmacyNote['stop_drug_name'] ?? 'ไม่มีข้อมูลระบุยา') ?>
                    </td>
                    <td>
                        <span class="label-bold">ตั้งแต่:</span> <?= htmlspecialchars($pharmacyNote['stop_start_date'] ?? '-') ?><br>
                        <span class="label-bold">ถึงวันที่:</span> <?= htmlspecialchars($pharmacyNote['stop_end_date'] ?? '-') ?>
                    </td>
                    <td>
                        <span class="label-bold">รคส.แพทย์:</span> <?= htmlspecialchars($pharmacyNote['consult_doctor_name'] ?? '-') ?><br>
                        <span class="label-bold">เวลา:</span> <?= htmlspecialchars($pharmacyNote['consult_time'] ?? '-') ?><br>
                        <span style="font-size:7.5pt;"><?= htmlspecialchars($pharmacyNote['note_remark'] ?? '-') ?></span>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Barcode & Signature Block -->
        <div class="bottom-row">
            <div>
                <svg id="barcodePrintSvg"></svg>
            </div>
            <div class="signature-block">
                <div class="signature-slot">
                    <div class="sig-label">ลงชื่อเภสัชกรผู้บันทึก</div>
                    <span class="signature-line"></span>
                    <div class="sig-name">(<?= htmlspecialchars($pharmacyNote['pharmacist_name'] ?? '...................................') ?>)</div>
                </div>
                <div class="signature-slot">
                    <div class="sig-label">ลายเซ็น รคส.แพทย์ผู้สั่ง</div>
                    <span class="signature-line"></span>
                    <div class="sig-name">(<?= htmlspecialchars($pharmacyNote['consult_doctor_name'] ?? '...................................') ?>)</div>
                </div>
            </div>
        </div>


    </div>


    <script>
        // Render VN Barcode on print page
        if ("<?= htmlspecialchars($visit['vn'] ?? '') ?>" !== '') {
            JsBarcode("#barcodePrintSvg", "<?= htmlspecialchars($visit['vn'] ?? '') ?>", {
                format: "CODE128",
                lineColor: "#000000",
                width: 1.4,
                height: 28,
                displayValue: true,
                fontSize: 9,
                fontOptions: "bold"
            });
        }

        function changePaperSize(size) {
            window.location.href = 'print_opd.php?vn=<?= urlencode($vn) ?>&size=' + size;
        }

        // Auto print dialog when loaded
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 600);
        });
    </script>
</body>
</html>
