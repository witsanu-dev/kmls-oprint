<?php
require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบบันทึก Pharmacy Note & OPD Card - โรงพยาบาลกมลาไสย</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts: Anuphan -->
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Lucide Icons (CDN) -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Custom Vibrant Orange/White Theme -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Top Minimal Navigation Bar -->
    <nav class="navbar-custom">
        <a href="index.php" class="navbar-brand navbar-brand-responsive">
            <div class="brand-icon flex-shrink-0">
                <i data-lucide="eye" style="width:22px;height:22px;color:white;"></i>
            </div>
            <div class="brand-text-wrap">
                <div class="navbar-title">
                    โครงการ <span style="color: #f97316;">“ราษฎรสุข พลานามัยสมบูรณ์”</span>
                </div>
                <div class="brand-sub">
                    <span class="brand-sub-highlight">แพทย์พระราชทาน</span><span class="brand-sub-sep">|</span>ตรวจคัดกรองและผ่าตัดรักษาต้อกระจก
                </div>
            </div>
        </a>
        <div class="navbar-actions">
            <div id="dbStatusBadge" onclick="openDbConfigModal()" title="คลิกเพื่อตั้งค่าและทดสอบการเชื่อมต่อฐานข้อมูล HOSxP">
                <?php if (isMockMode()): ?>
                    <span class="db-badge-warn shadow-sm"><i data-lucide="wifi-off" style="width:13px;height:13px;"></i><span class="badge-label"> Mock/Offline</span></span>
                <?php else: ?>
                    <span class="db-badge-teal shadow-sm"><i data-lucide="database" style="width:13px;height:13px;"></i><span class="badge-label"> HIS Connected</span></span>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Wrapper Container -->
    <div class="main-wrapper">

        <!-- Top System Banner Above Search Card -->
        <div class="d-flex align-items-center justify-content-between mb-3 px-1">
            <div class="d-flex align-items-center gap-2">
                <span class="badge" style="background: linear-gradient(135deg, #f97316, #ea580c); color: white; padding: 6px 12px; font-size: 0.85rem; font-weight: 600; border-radius: 6px;">
                    <i data-lucide="pill" style="width:14px;height:14px;margin-top:-2px;"></i> Pharmacy Note
                </span>
                <span class="fw-bold text-dark" style="font-size: 1.05rem;">บันทึกคำสั่งหยุดยาละลายลิ่มเลือด</span>
            </div>
            <div class="text-muted small fw-semibold">
                <i data-lucide="building-2" style="width:14px;height:14px;color:#f97316;"></i> โรงพยาบาลกมลาไสย
            </div>
        </div>

        <!-- 1. Real-Time Visit Search Header -->
        <div class="card-custom search-card rounded-md">
            <div class="card-body-custom">
                <div class="row align-items-end g-3">
                    <div class="col-md-7">
                        <label for="visitSearchSelect" class="form-label" style="font-size: 0.95rem;">
                            <i data-lucide="search" style="width:17px;height:17px;color:#f97316;"></i>
                            ค้นหา Visit / ผู้รับบริการ Real-Time
                        </label>
                        <select id="visitSearchSelect" style="width:100%;"></select>
                        <div class="form-text">ค้นหาด้วย VN (เช่น <code>690726003040</code>), HN, เลขบัตรประชาชน หรือ ชื่อ-นามสกุลผู้รับบริการ</div>
                    </div>
                    <div class="col-md-5">
                        <div class="d-flex gap-2 justify-content-end align-items-end">
                            <div style="width: 175px;">
                                <label for="paperSizeSelectMain" class="form-label mb-1 small" style="color:#64748b;font-size:0.8rem;">
                                    <i data-lucide="file-text" style="width:12px;height:12px;color:#f97316;"></i> ขนาดกระดาษพิมพ์
                                </label>
                                <select id="paperSizeSelectMain" class="form-select select2-paper-size" style="width:100%;">
                                    <option value="a5-portrait" selected>A5 แนวตั้ง</option>
                                    <option value="a5-landscape">A5 แนวนอน</option>
                                    <option value="a4-portrait">A4 แนวตั้ง</option>
                                </select>
                            </div>
                            <div>
                                <button id="btnPrintOPD" class="btn btn-print-minimal" onclick="printOPD()" disabled>
                                    <i data-lucide="printer"></i> พิมพ์ OPD Card
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Prominent Patient Info & Demographics Banner -->
        <div class="card-custom rounded-md">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <div class="title">
                    <i data-lucide="user-check"></i> ข้อมูลผู้รับบริการและ Visit
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-md py-1 px-2 d-none align-items-center justify-content-center shadow-xs" id="btnClearAllForm" style="display: none !important; border-color: #cbd5e1;" onclick="clearAllFormAndSearch()" title="ล้างค่าการค้นหาและแบบฟอร์มทั้งหมด">
                    <i data-lucide="user-x" style="width:16px;height:16px;color:#ef4444;"></i>
                </button>
            </div>
            <div class="card-body-custom">
                <!-- Recent Visits Quick Switcher Bar -->
                <div id="visitHistoryContainer" style="display: none; background: #fff7ed; padding: 10px 14px; border-radius: 6px; border: 1px solid #ffedd5; margin-bottom: 15px;"></div>

                <div class="row g-3 mb-3 align-items-center">
                    <div class="col-md-3">
                        <div class="hn-display-box rounded-md">
                            <div class="hn-label mb-1">HN ผู้รับบริการ</div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="hn-number" id="display_hn">-</div>
                                <button type="button" id="btnCopyHn" class="btn btn-sm btn-copy-hn rounded-md p-0 d-flex align-items-center justify-content-center" title="คัดลอก HN" style="width:26px;height:26px;background:rgba(255,255,255,0.22);border:none;color:#fff;cursor:pointer;transition:all 0.2s;z-index:2;" onclick="copyHnToClipboard()">
                                    <i data-lucide="copy" style="width:14px;height:14px;"></i>
                                </button>
                            </div>
                            <i data-lucide="id-card" class="hn-watermark-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="patient-info-item">
                            <span class="label"><i data-lucide="user"></i> ชื่อ - นามสกุล</span>
                            <span class="value" id="display_name" style="font-size:1.15rem;">-</span>
                        </div>
                        <div class="patient-info-item mt-2">
                            <span class="label"><i data-lucide="shield-check"></i> สิทธิการรักษา</span>
                            <span class="value" id="display_pttype">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="barcode-box rounded-md">
                            <div class="small mb-1" style="color:#64748b;font-weight:600;">
                                <i data-lucide="scan-barcode" style="width:14px;height:14px;"></i>
                                VN: <strong id="display_vn">-</strong>
                            </div>
                            <svg id="vnBarcodeSvg"></svg>
                        </div>
                    </div>
                </div>

                <div class="patient-banner rounded-md">
                    <div class="patient-info-item">
                        <span class="label"><i data-lucide="calendar"></i> วันที่/เวลาตรวจ</span>
                        <span class="value" id="display_vstdate">-</span>
                    </div>
                    <div class="patient-info-item">
                        <span class="label"><i data-lucide="credit-card"></i> เลขบัตรประชาชน (CID)</span>
                        <span class="value" id="display_cid">-</span>
                    </div>
                    <div class="patient-info-item">
                        <span class="label"><i data-lucide="users"></i> อายุ / เพศ / กรุ๊ปเลือด</span>
                        <span class="value"><span id="display_age">-</span> (<span id="display_sex">-</span>) | <span id="display_bloodgrp">-</span></span>
                    </div>
                    <div class="patient-info-item">
                        <span class="label"><i data-lucide="phone"></i> เบอร์โทรศัพท์</span>
                        <span class="value" id="display_tel">-</span>
                    </div>
                    <div class="patient-info-item">
                        <span class="label"><i data-lucide="map-pin"></i> ที่อยู่ปัจจุบัน</span>
                        <span class="value" id="display_addr">-</span>
                    </div>
                    <div class="patient-info-item">
                        <span class="label"><i data-lucide="heart-pulse"></i> โรคประจำตัว</span>
                        <span class="value" id="display_clinic">-</span>
                    </div>
                    <div class="patient-info-item">
                        <span class="label"><i data-lucide="triangle-alert"></i> ประวัติการแพ้ยา</span>
                        <span class="badge-allergy rounded-md" id="display_allergy">ไม่มีประวัติแพ้ยา</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Pharmacy Note Input Form & Live OPD Preview -->
        <form id="pharmacyForm">
            <input type="hidden" id="form_vn" name="vn" value="">
            <input type="hidden" id="form_hn" name="hn" value="">
            <input type="hidden" id="stop_drug_name_hidden" name="stop_drug_name" value="">
            <input type="hidden" id="consult_doctor_name_hidden" name="consult_doctor_name" value="">

            <div class="row">
                
                <!-- Left Column: Pharmacy Note Inputs -->
                <div class="col-lg-7">
                    <div class="card-custom rounded-md">
                        <div class="card-header-custom">
                            <div class="title">
                                <i data-lucide="file-input"></i> กรอกข้อมูล Pharmacy Note (คำสั่งหยุดยา)
                            </div>
                        </div>
                        <div class="card-body-custom">
                            
                            <!-- Stop Anticoagulant Drug Select -->
                            <div class="mb-3">
                                <label class="form-label">
                                    <i data-lucide="alert-circle" style="color: #ef4444;"></i> ให้หยุดยาละลายลิ่มเลือด / ยาต้านเกล็ดเลือด
                                </label>
                                <select id="stop_drug_select" name="stop_drug_icode" class="form-select select2-single rounded-md" disabled></select>
                                <div id="drug_shortcuts_container" class="mt-2 d-flex flex-wrap gap-1"></div>
                                <div class="form-text small mt-1">ค้นหาจากตาราง <code>drugitems</code> (เช่น Aspirin, Clopidogrel, Warfarin, Rivaroxaban)</div>
                            </div>

                            <!-- Date Range Picker -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i data-lucide="calendar" style="color: #f97316;"></i> ตั้งแต่วันที่ (เริ่มต้นหยุดยา)
                                    </label>
                                    <input type="date" class="form-control rounded-md" id="stop_start_date" name="stop_start_date" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i data-lucide="calendar-off" style="color: #ef4444;"></i> ถึงวันที่ (สิ้นสุดการหยุดยา)
                                    </label>
                                    <input type="date" class="form-control rounded-md" id="stop_end_date" name="stop_end_date" disabled>
                                </div>
                            </div>

                            <!-- Consulting Doctor & Consult Time -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="form-label">
                                        <i data-lucide="stethoscope" style="color: #2563eb;"></i> รคส.แพทย์ (แพทย์ผู้ให้คำปรึกษา)
                                    </label>
                                    <select id="consult_doctor_select" name="consult_doctor_code" class="form-select select2-single rounded-md" disabled></select>
                                    <div class="form-text small">เลือกรายชื่อแพทย์จากตาราง <code>doctor</code></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label mb-0">
                                            <i data-lucide="clock" style="color: #f97316;"></i> เวลา Consult
                                        </label>
                                        <span class="badge" id="btnNowTime" style="background-color: #fff7ed; color: #ea580c; border: 1px solid #ea580c; cursor: pointer; pointer-events: none; opacity: 0.6;">ตอนนี้</span>
                                    </div>
                                    <input type="time" class="form-control rounded-md" id="consult_time" name="consult_time" value="<?= date('H:i') ?>" disabled>
                                </div>
                            </div>

                            <!-- Recording Pharmacist -->
                            <div class="mb-3">
                                <label class="form-label">
                                    <i data-lucide="user-check" style="color: #10b981;"></i> เภสัชกรผู้บันทึกข้อมูล
                                </label>
                                <select id="pharmacist_select" name="pharmacist_code" class="form-select select2-single rounded-md" disabled></select>
                                <input type="hidden" id="pharmacist_name_hidden" name="pharmacist_name" value="">
                                <div class="form-text small">เลือกจากรายชื่อแพทย์/เภสัชกรในตาราง <code>doctor</code></div>
                            </div>

                            <!-- Remarks / Notes -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0">
                                        <i data-lucide="edit-3"></i> คำแนะนำเพิ่มเติม / หมายเหตุ
                                    </label>
                                    <div class="d-flex gap-1 flex-wrap align-items-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-md py-0 px-2 remark-shortcut-btn" style="font-size:0.75rem;" data-text="สังเกตอาการเลือดออกผิดปกติ และหยุดยาก่อนผ่าตัดต้อกระจกตามกำหนดเคร่งครัด" disabled title="สังเกตอาการเลือดออกผิดปกติ และหยุดยาก่อนผ่าตัดต้อกระจกตามกำหนดเคร่งครัด">
                                            <i data-lucide="alert-triangle" style="width:11px;height:11px;"></i> สังเกตเลือดออก
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-warning text-dark rounded-md py-0 px-2 remark-shortcut-btn" style="font-size:0.75rem;" data-text="งดยาก่อนวันผ่าตัด 7 วัน และเริ่มกินยาใหม่หลังผ่าตัดตามคำสั่งแพทย์" disabled title="งดยาก่อนวันผ่าตัด 7 วัน และเริ่มกินยาใหม่หลังผ่าตัดตามคำสั่งแพทย์">
                                            <i data-lucide="calendar-x" style="width:11px;height:11px;"></i> งดยา 7 วันก่อนผ่า
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success rounded-md py-0 px-2 remark-shortcut-btn" style="font-size:0.75rem;" data-text="หากมีภาวะจมูกอักเสบ/เลือดออกในตา ให้รีบพบแพทย์ก่อนวันนัดผ่าตัด" disabled title="หากมีภาวะจมูกอักเสบ/เลือดออกในตา ให้รีบพบแพทย์ก่อนวันนัดผ่าตัด">
                                            <i data-lucide="activity" style="width:11px;height:11px;"></i> ผิดปกติพบแพทย์
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-md py-0 px-1.5 clear-remark-btn" id="btnClearRemark" style="font-size:0.75rem;" disabled title="ล้างข้อความหมายเหตุ">
                                            <i data-lucide="x" style="width:11px;height:11px;color:#ef4444;"></i>
                                        </button>
                                    </div>
                                </div>
                                <textarea class="form-control rounded-md" id="note_remark" name="note_remark" rows="2" placeholder="ระบุข้อควรระวัง หรือคำแนะนำการสังเกตอาการเลือดออกผิดปกติ..." disabled></textarea>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="submit" id="btnSave" class="btn btn-orange rounded-md" disabled>
                                    <i data-lucide="save"></i> บันทึก Pharmacy Note
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Right Column: Live OPD Card Preview -->
                <div class="col-lg-5">
                    <div class="card-custom rounded-md">
                        <div class="card-header-custom">
                            <div class="title">
                                <i data-lucide="eye" style="color: #f97316;"></i> แสดงตัวอย่าง OPD Card (รพ.กมลาไสย)
                            </div>
                        </div>
                        <div class="card-body-custom">
                            <div class="preview-doc rounded-md">
                                <div class="preview-hospital">
                                    <strong style="font-size:1rem;color:#0f172a;">โครงการ “ราษฎรสุข พลานามัยสมบูรณ์” โดยแพทย์พระราชทาน</strong><br>
                                    <small style="color:#f97316;font-weight:600;">ตรวจคัดกรองและผ่าตัดรักษาต้อกระจก &mdash; Pharmacy Note</small>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span style="font-size:0.9rem;"><strong style="color:#f97316;">HN:</strong> <span id="prev_hn" style="font-weight:800;font-size:1.05rem;color:#f97316;">-</span></span>
                                    <span style="font-size:0.9rem;"><strong>VN:</strong> <span id="prev_vn" style="font-weight:600;">-</span></span>
                                </div>
                                <div class="mb-1" style="font-size:0.9rem;"><strong>ผู้รับบริการ:</strong> <span id="prev_name">-</span></div>
                                <div class="mb-2" style="font-size:0.85rem;color:#64748b;"><strong>สิทธิ:</strong> <span id="prev_pttype">-</span></div>

                                <div class="preview-drug-box rounded-md">
                                    <div class="drug-title">
                                        <i data-lucide="shield-alert" style="width:14px;height:14px;"></i>
                                        คำสั่งหยุดยาละลายลิ่มเลือด:
                                    </div>
                                    <div id="prev_drug" style="font-weight:700;color:#0f172a;font-size:0.95rem;">-</div>
                                    <div style="font-size:0.82rem;color:#64748b;margin-top:4px;">
                                        <i data-lucide="calendar-range" style="width:13px;height:13px;"></i>
                                        <strong>ช่วงวัน:</strong> <span id="prev_date_range">-</span>
                                    </div>
                                </div>

                                <div style="font-size:0.85rem;">
                                    <div><strong>รคส.แพทย์:</strong> <span id="prev_doctor" style="color:#334155;">-</span></div>
                                    <div class="mt-1"><strong>เภสัชกร:</strong> <span id="prev_pharmacist" style="color:#334155;">-</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Print & Sticker Actions Card -->
                    <div class="card-custom rounded-md mt-3 shadow-sm border">
                        <div class="card-header-custom py-2 px-3 bg-light border-bottom">
                            <div class="title small fw-bold text-dark d-flex align-items-center gap-1.5" style="font-size: 0.9rem;">
                                <i data-lucide="printer" style="width:16px;height:16px;color:#ea580c;"></i> เครื่องมือพิมพ์เอกสารและสติกเกอร์
                            </div>
                        </div>
                        <div class="card-body-custom p-3">
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex align-items-center justify-content-between bg-body-tertiary p-2 rounded-2 border">
                                    <label class="small fw-semibold text-secondary mb-0 d-flex align-items-center gap-1" style="font-size:0.85rem;">
                                        <i data-lucide="sliders" style="width:14px;height:14px;color:#ea580c;"></i> ขนาดสติกเกอร์:
                                    </label>
                                    <select id="stickerSizeSelect" class="form-select form-select-sm border-secondary-subtle fw-medium" style="width: 145px; font-size:0.83rem;" onchange="printSticker()">
                                        <option value="50x30" selected>50 x 30 mm</option>
                                        <option value="70x40">70 x 40 mm</option>
                                        <option value="80x50">80 x 50 mm</option>
                                        <option value="100x50">100 x 50 mm</option>
                                    </select>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button type="button" class="btn btn-sm btn-orange w-100 py-2 rounded-md fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-1" onclick="printOPD()">
                                            <i data-lucide="printer" style="width:15px;height:15px;"></i> พิมพ์ OPD Card
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button type="button" class="btn btn-sm btn-outline-orange w-100 py-2 rounded-md fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-1" onclick="printSticker()">
                                            <i data-lucide="tag" style="width:15px;height:15px;"></i> พิมพ์สติกเกอร์
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>

        <!-- 4. Pharmacy Note History Data Table -->
        <div class="card-custom rounded-md mt-4">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <div class="title">
                    <i data-lucide="table-2" style="color: #ea580c;"></i> ทะเบียนข้อมูล Pharmacy Note
                </div>
                <button type="button" class="btn btn-sm rounded-md btn-outline-orange" style="font-size: 0.82rem;" onclick="reloadNoteTable()">
                    <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i> รีเฟรชข้อมูล
                </button>
            </div>
            <div class="card-body-custom">
                <div class="table-responsive">
                    <table id="pharmacyNoteTable" class="table table-hover w-100" style="font-size: 0.88rem;">
                        <thead class="table-light text-dark text-nowrap align-middle">
                            <tr>
                                <th class="text-center">ลำดับ</th>
                                <th class="text-center">จัดการ</th>
                                <th>สถานะ</th>
                                <th>อัปเดตล่าสุด</th>
                                <th>HN</th>
                                <th>VN</th>
                                <th>รคส.แพทย์</th>
                                <th>ยาที่ให้หยุด</th>
                                <th>ช่วงเวลาหยุดยา</th>
                                <th>เภสัชกรผู้บันทึก</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- HOSxP Database Config & Test Modal -->
    <div class="modal fade" id="dbConfigModal" tabindex="-1" aria-labelledby="dbConfigModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
            <div class="modal-content border-0 shadow-lg rounded-md overflow-hidden">
                <div class="modal-header bg-light py-2.5 px-3 border-bottom">
                    <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2 mb-0" id="dbConfigModalLabel">
                        <i data-lucide="database" style="width:18px;height:18px;color:#ea580c;"></i>
                        ตั้งค่าและการเชื่อมต่อฐานข้อมูล HOSxP
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <!-- Status Banner -->
                    <div id="modalTestResult" class="mb-3">
                        <div class="card border-0 shadow-sm rounded-md overflow-hidden bg-light border-start border-4 <?= isMockMode() ? 'border-warning' : 'border-success' ?>">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (isMockMode()): ?>
                                            <div class="rounded-md bg-warning text-dark d-flex align-items-center justify-content-center p-2 shadow-sm" style="width:36px;height:36px;">
                                                <i data-lucide="wifi-off" style="width:18px;height:18px;"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold text-dark mb-0" style="font-size:0.92rem;">โหมดจำลอง (Offline / Mock Mode)</h6>
                                                <div class="small text-muted mt-0.5" style="font-size:0.78rem;">ไม่อยู่ในเครือข่าย HOSxP โรงพยาบาล</div>
                                            </div>
                                        <?php else: ?>
                                            <div class="rounded-md bg-success text-white d-flex align-items-center justify-content-center p-2 shadow-sm" style="width:36px;height:36px;">
                                                <i data-lucide="check-circle-2" style="width:20px;height:20px;"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold text-success-emphasis mb-0" style="font-size:0.92rem;">เชื่อมต่อฐานข้อมูล HOSxP สำเร็จ!</h6>
                                                <div class="small text-secondary mt-0.5" style="font-size:0.78rem;">
                                                    <i data-lucide="server" style="width:13px;height:13px;color:#16a34a;"></i> HIS connected: <span class="font-monospace text-dark-emphasis"><?= DB_HOST ?>:<?= DB_PORT ?></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-secondary-subtle text-secondary border font-monospace px-2.5 py-1 rounded-md shadow-xs" style="font-size:0.75rem;">
                                        MySQL <?= DB_PORT ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Config Form -->
                    <form id="formDbTest">
                        <div class="row g-2 mb-2">
                            <div class="col-8">
                                <label class="form-label small fw-semibold text-muted mb-1">Database Host IP</label>
                                <input type="text" class="form-control form-control-sm rounded-md font-monospace" id="cfg_host" value="<?= DB_HOST ?>" placeholder="10.250.100.201">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold text-muted mb-1">Port</label>
                                <input type="text" class="form-control form-control-sm rounded-md font-monospace" id="cfg_port" value="<?= DB_PORT ?>" placeholder="3306">
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">Username</label>
                                <input type="text" class="form-control form-control-sm rounded-md" id="cfg_user" value="<?= DB_USER ?>" placeholder="hxpkt">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">Password</label>
                                <input type="password" class="form-control form-control-sm rounded-md" id="cfg_pass" value="<?= DB_PASS ?>" placeholder="••••••••">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-muted mb-1">Database Name</label>
                            <input type="text" class="form-control form-control-sm rounded-md" id="cfg_dbname" value="<?= DB_NAME ?>" placeholder="hos">
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-sm btn-light border rounded-md" data-bs-dismiss="modal">ปิด</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-md px-3 shadow-xs d-flex align-items-center gap-1.5" id="btnTestConn" onclick="testDbConnection()">
                            <i data-lucide="refresh-cw" id="iconTestSpin" style="width:14px;height:14px;"></i> ทดสอบ
                        </button>
                        <button type="button" class="btn btn-sm btn-orange rounded-md px-3 shadow-sm d-flex align-items-center gap-1.5" id="btnSaveDbConfig" onclick="saveDbConnection()">
                            <i data-lucide="save" style="width:14px;height:14px;"></i> บันทึกการตั้งค่า
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Dependencies (correct order) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    
    <script src="assets/js/app.js"></script>

</body>
</html>
