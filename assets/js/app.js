/**
 * Pharmacy Note OPD Card System - Kamalasai Hospital
 * Main JavaScript Engine (Minimal White/Orange Theme)
 * Icons: Lucide Icons ONLY (No Emojis Policy)
 */

// PDPA Data Masking Helpers
function maskName(fullName) {
    if (!fullName || fullName === '-') return '-';
    let parts = fullName.trim().split(/\s+/);
    if (parts.length === 1) return parts[0];
    
    // Title/Pname if present in first element
    let fname = parts[0];
    let lname = parts.slice(1).join(' ');
    
    // Mask Last Name (e.g., "สมชาย ใจดี" -> "สมชาย ใจ***" หรือ "วิษณุ ศรี***")
    let maskedLname = lname.length > 2 ? lname.substring(0, 2) + '***' : lname + '***';
    return `${fname} ${maskedLname}`;
}

function maskCID(cid) {
    if (!cid || cid === '-' || cid.length < 13) return cid || '-';
    // Format: 1-4699-XXXXX-XX-X (Mask middle 5 digits)
    let clean = cid.replace(/[^0-9]/g, '');
    if (clean.length === 13) {
        return clean.substring(0, 4) + '-XXXXX-' + clean.substring(9, 11) + '-' + clean.substring(12);
    }
    return cid;
}

function maskTel(tel) {
    if (!tel || tel === '-' || tel.length < 9) return tel || '-';
    let clean = tel.replace(/[^0-9]/g, '');
    if (clean.length >= 9) {
        return clean.substring(0, 3) + '-XXX-' + clean.substring(clean.length - 4);
    }
    return tel;
}

function maskAddress(addr) {
    if (!addr || addr === '-') return '-';
    // Keep Sub-district/District, mask house number (e.g., "123/4 ม.1..." -> "*** ม.1...")
    return addr.replace(/^[\d\/\-\sA-Za-zก-ฮ]+(ม\.|ต\.|อ\.|จ\.)/u, '*** $1');
}

$(document).ready(function () {
    // 1. Initialize Lucide Icons (already loaded in <head>)
    lucide.createIcons();

    // 2. Initialize Visit Live Search (Select2) with minimumInputLength: 2 to prevent server overload
    $('#visitSearchSelect').select2({
        theme: 'bootstrap-5',
        placeholder: '⚡ พิมพ์ HN / ชื่อ-นามสกุล / เลขบัตรประชาชน / VN เพื่อค้นหา...',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: 'api/search_visit.php',
            dataType: 'json',
            delay: 350,
            data: function (params) {
                return { q: params.term || '' };
            },
            processResults: function (data) {
                if (data.mock) {
                    $('#dbStatusBadge').html('<span class="db-badge-warn"><i data-lucide="wifi-off" style="width:13px;height:13px;"></i> Mock/Offline Mode</span>');
                } else {
                    $('#dbStatusBadge').html('<span class="db-badge-teal"><i data-lucide="database" style="width:13px;height:13px;"></i> HIS Connected</span>');
                }
                lucide.createIcons();
                
                // Mask names in search results for PDPA Compliance
                let maskedResults = (data.results || []).map(function(item) {
                    let pNameMasked = maskName(item.patient_name);
                    return {
                        id: item.id,
                        vn: item.vn,
                        hn: item.hn,
                        text: `VN: ${item.vn} | HN: ${item.hn} | ${pNameMasked} | วันที่: ${item.vstdate}`,
                        vstdate: item.vstdate,
                        vsttime: item.vsttime,
                        patient_name: item.patient_name,
                        cid: item.cid,
                        age: item.age,
                        has_note: item.has_note,
                        note_status: item.note_status
                    };
                });

                return { results: maskedResults };
            },
            cache: true
        },
        templateResult: function(item) {
            if (!item.id) return item.text; // placeholder
            
            let $container = $('<div style="display:flex; justify-content:space-between; align-items:center; width:100%;"></div>');
            let $text = $('<span style="line-height:1.5;"></span>').text(item.text);
            
            let badgeHtml = '';
            if (item.note_status === 'cancelled') {
                badgeHtml = `<span style="display:inline-flex; align-items:center; background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; border-radius:4px; font-size:10px; padding:2px 6px; margin-left:8px; font-weight:600; white-space:nowrap;"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:3px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>ยกเลิกการคัดกรอง</span>`;
            } else if (item.has_note) {
                badgeHtml = `<span style="display:inline-flex; align-items:center; background:#dcfce7; color:#16a34a; border:1px solid #86efac; border-radius:4px; font-size:10px; padding:2px 6px; margin-left:8px; font-weight:600; white-space:nowrap;"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:3px;"><polyline points="20 6 9 17 4 12"></polyline></svg>บันทึกแล้ว</span>`;
            } else {
                badgeHtml = `<span style="display:inline-flex; align-items:center; background:#fef9c3; color:#92400e; border:1px solid #fde68a; border-radius:4px; font-size:10px; padding:2px 6px; margin-left:8px; font-weight:600; white-space:nowrap;">ยังไม่บันทึก</span>`;
            }
            
            let $badge = $(badgeHtml);
            $container.append($text).append($badge);
            
            return $container;
        },
        templateSelection: function(item) {
            if (!item.id) return item.text;
            
            let $container = $('<span style="display:flex; align-items:center;"></span>');
            $container.text(item.text);
            
            if (item.note_status === 'cancelled') {
                let $badge = $(`<span style="display:inline-flex; align-items:center; background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; border-radius:4px; font-size:10px; padding:1px 5px; margin-left:6px; font-weight:600;">✕ ยกเลิกคัดกรอง</span>`);
                $container.append($badge);
            } else if (item.has_note) {
                let $badge = $(`<span style="display:inline-flex; align-items:center; background:#dcfce7; color:#16a34a; border:1px solid #86efac; border-radius:4px; font-size:10px; padding:1px 5px; margin-left:6px; font-weight:600;">✓ บันทึกแล้ว</span>`);
                $container.append($badge);
            }
            
            return $container;
        }
    });

    // Initialize Paper Size Select2
    $('#paperSizeSelectMain').select2({
        theme: 'bootstrap-5',
        minimumResultsForSearch: Infinity
    });

    // Automatically trigger initial open focus
    $('#visitSearchSelect').on('select2:open', function () {
        document.querySelector('.select2-search__field').focus();
    });

    // 3. Initialize Drugitems Select2 Search
    $('#stop_drug_select').select2({
        theme: 'bootstrap-5',
        placeholder: 'ค้นหายาละลายลิ่มเลือด/ยาต้านเกล็ดเลือดจากตาราง drugitems...',
        allowClear: true,
        ajax: {
            url: 'api/get_options.php?type=drugs',
            dataType: 'json',
            delay: 200,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; },
            cache: true
        }
    });

    // 4. Initialize Consulting Doctor Select2 Search
    $('#consult_doctor_select').select2({
        theme: 'bootstrap-5',
        placeholder: 'เลือก รคส.แพทย์ ผู้ให้คำปรึกษา จากตาราง doctor...',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: 'api/get_options.php?type=doctors',
            dataType: 'json',
            delay: 200,
            data: function (params) { return { q: params.term || '' }; },
            processResults: function (data) { return { results: data.results || [] }; },
            cache: true
        }
    });

    // 4.6 Load Drug Shortcuts
    function loadDrugShortcuts() {
        $.ajax({
            url: 'api/get_drug_shortcuts.php',
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success' && res.data) {
                    let html = '';
                    const renderGroup = (key, colorClass) => {
                        if(res.data[key] && res.data[key].length > 0) {
                            res.data[key].forEach(d => {
                                html += `<button type="button" class="btn btn-sm ${colorClass} rounded-pill drug-shortcut-btn" style="font-size:0.75rem;" data-id="${d.id}" data-text="${d.text}" disabled><i data-lucide="zap" style="width:12px;height:12px;"></i> ${d.text}</button>`;
                            });
                        }
                    };
                    renderGroup('aspirin', 'btn-outline-danger');
                    renderGroup('warfarin', 'btn-outline-primary');
                    renderGroup('clopidogrel', 'btn-outline-warning text-dark');
                    $('#drug_shortcuts_container').html(html);
                    lucide.createIcons();
                }
            }
        });
    }
    loadDrugShortcuts();

    $(document).on('click', '.drug-shortcut-btn', function() {
        let id = $(this).data('id');
        let text = $(this).data('text');
        let newOption = new Option(text, id, true, true);
        $('#stop_drug_select').append(newOption).trigger('change');
    });

    $(document).on('click', '.remark-shortcut-btn', function() {
        let text = $(this).data('text');
        let currentText = $('#note_remark').val().trim();
        if (currentText && !currentText.includes(text)) {
            $('#note_remark').val(currentText + ' ' + text).trigger('change');
        } else {
            $('#note_remark').val(text).trigger('change');
        }
    });

    $(document).on('click', '#btnClearRemark', function() {
        $('#note_remark').val('').trigger('change');
    });

    $('#stop_drug_select').on('change', function() {
        let selectedId = $(this).val();
        $('.drug-shortcut-btn').removeClass('active text-white fw-bold shadow-sm').css('opacity', '0.7');
        if (selectedId) {
            $(`.drug-shortcut-btn[data-id="${selectedId}"]`).addClass('active text-white fw-bold shadow-sm').css('opacity', '1');
        } else {
            $('.drug-shortcut-btn').css('opacity', '1');
        }
    });

    // 4.5 Initialize Pharmacist Select2 Search
    $('#pharmacist_select').select2({
        theme: 'bootstrap-5',
        placeholder: 'เลือก เภสัชกรผู้บันทึกข้อมูล จากตาราง doctor...',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: 'api/get_options.php?type=doctors',
            dataType: 'json',
            delay: 200,
            data: function (params) { return { q: params.term || '' }; },
            processResults: function (data) { return { results: data.results || [] }; },
            cache: true
        }
    });

    // 5. Handle Visit Selection Event
    $('#visitSearchSelect').on('select2:select', function (e) {
        let data = e.params.data;
        loadVisitData(data.vn || data.id);
    });

    // Load Visit Data & Existing Pharmacy Note
    window.loadVisitData = function(vn) {
        Swal.fire({
            title: 'กำลังดึงข้อมูล Visit...',
            text: 'ค้นหาและดึงข้อมูลจากระบบสด',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: 'api/get_visit_detail.php',
            type: 'GET',
            data: { vn: vn },
            dataType: 'json',
            success: function (res) {
                Swal.close();
                if (res.status === 'success' && res.visit) {
                    let v = res.visit;

                    // Set Hidden Fields
                    $('#form_vn').val(v.vn);
                    $('#form_hn').val(v.hn);

                    // Render Demographics with PDPA Masking Protection
                    $('#display_hn').text(v.hn || '-');
                    $('#display_vn').text(v.vn || '-');
                    $('#display_name').html(`${maskName(v.patient_name)} <span class="badge-pdpa-green ms-1" title="PDPA Protected"><i data-lucide="shield-check" style="width:12px;height:12px;"></i> PDPA Protected</span>`);
                    $('#display_cid').text(maskCID(v.cid));
                    $('#display_age').text((v.age || '-') + ' ปี');
                    $('#display_sex').text(v.sex || '-');
                    $('#display_vstdate').text(v.vstdate_thai || ((v.vstdate || '-') + ' ' + (v.vsttime || '')));
                    $('#display_pttype').text(v.pttype_name || '-');
                    $('#display_addr').text(maskAddress(v.informaddr));
                    $('#display_tel').text(maskTel(v.pt_tel));
                    $('#display_bloodgrp').text(v.bloodgrp || '-');
                    $('#display_clinic').text(v.patient_clinic || 'ไม่มีข้อมูลโรคประจำตัว');
                    $('#display_allergy').text(v.drugallergy || 'ไม่มีประวัติแพ้ยา');

                    // Sync Search Dropdown Value to match current Visit
                    let currentOptionText = `VN: ${v.vn} | HN: ${v.hn} | ${maskName(v.patient_name)} | วันที่: ${v.vstdate_thai || v.vstdate}`;
                    let newSearchOption = new Option(currentOptionText, v.vn, true, true);
                    $('#visitSearchSelect').append(newSearchOption).trigger('change.select2');

                    // Generate VN Barcode using JsBarcode
                    try {
                        if (v.vn && typeof JsBarcode !== 'undefined') {
                            JsBarcode("#vnBarcodeSvg", v.vn, {
                                format: "CODE128",
                                lineColor: "#0f172a",
                                width: 1.8,
                                height: 45,
                                displayValue: true,
                                fontSize: 12,
                                fontOptions: "bold"
                            });
                        }
                    } catch(bErr) { console.warn('Barcode error:', bErr); }

                    // Load Recent Visits History Badges for this Patient (Up to 3-5 visits)
                    loadPatientVisitHistory(v.hn, v.vn);

                    // Enable Pharmacy Note Form & Print Controls
                    $('#pharmacyForm input, #pharmacyForm select, #pharmacyForm textarea, #btnSave, #btnPrintOPD, .drug-shortcut-btn, .remark-shortcut-btn, #btnClearRemark').prop('disabled', false);
                    $('#btnNowTime').css({'pointer-events': 'auto', 'opacity': '1'});
                    $('#btnClearAllForm').removeClass('d-none').addClass('d-flex').show();

                    // Reset form select values before loading note
                    let nowTime = new Date();
                    let defH = nowTime.getHours().toString().padStart(2, '0');
                    let defM = nowTime.getMinutes().toString().padStart(2, '0');
                    let liveTime = defH + ':' + defM;

                    $('#stop_drug_select').val(null).trigger('change');
                    $('#consult_doctor_select').val(null).trigger('change');
                    $('#pharmacist_select').val(null).trigger('change');
                    $('#stop_start_date').val('');
                    $('#stop_end_date').val('');
                    $('#consult_time').val(liveTime);
                    $('#note_remark').val('');

                    // Load Pharmacy Note if exists
                    if (res.pharmacy_note) {
                        let note = res.pharmacy_note;
                        $('#stop_start_date').val(note.stop_start_date || '');
                        $('#stop_end_date').val(note.stop_end_date || '');
                        // CRITICAL: Always use current live time for consult_time as requested
                        $('#consult_time').val(liveTime);
                        $('#pharmacist_name').val(note.pharmacist_name || '');
                        $('#note_remark').val(note.note_remark || '');

                        if (note.stop_drug_icode) {
                            let newOption = new Option(note.stop_drug_name, note.stop_drug_icode, true, true);
                            $('#stop_drug_select').append(newOption).trigger('change');
                        }
                        if (note.consult_doctor_code) {
                            let newDocOption = new Option(note.consult_doctor_name, note.consult_doctor_code, true, true);
                            $('#consult_doctor_select').append(newDocOption).trigger('change');
                        }
                        if (note.pharmacist_name) {
                            let newPharmOption = new Option(note.pharmacist_name, note.pharmacist_name, true, true);
                            $('#pharmacist_select').append(newPharmOption).trigger('change');
                        }
                    } else {
                        // Feature: Auto-fill from last saved record (localStorage) to speed up entry
                        let savedNote = localStorage.getItem('lastPharmacyNote');
                        if (savedNote) {
                            try {
                                let note = JSON.parse(savedNote);
                                $('#stop_start_date').val(note.stop_start_date || '');
                                $('#stop_end_date').val(note.stop_end_date || '');
                                // CRITICAL: Always use current live time for consult_time as requested
                                $('#consult_time').val(liveTime);
                                $('#note_remark').val(note.note_remark || '');

                                if (note.stop_drug_icode) {
                                    let newOption = new Option(note.stop_drug_name, note.stop_drug_icode, true, true);
                                    $('#stop_drug_select').append(newOption).trigger('change');
                                }
                                if (note.consult_doctor_code) {
                                    let newDocOption = new Option(note.consult_doctor_name, note.consult_doctor_code, true, true);
                                    $('#consult_doctor_select').append(newDocOption).trigger('change');
                                }
                                if (note.pharmacist_name) {
                                    let newPharmOption = new Option(note.pharmacist_name, note.pharmacist_name, true, true);
                                    $('#pharmacist_select').append(newPharmOption).trigger('change');
                                }
                            } catch(e) { console.warn('Failed to load last note from localStorage', e); }
                        }
                    }

                    // Sync Preview Card
                    syncPreview(v);

                    // Highlight corresponding row in pharmacy note table if exists
                    if (typeof highlightActiveTableRow === 'function') {
                        highlightActiveTableRow(v.vn);
                    }



                } else {
                    Swal.fire('ไม่พบข้อมูล', res.message || 'ไม่พบข้อมูล Visit ดังกล่าวในระบบ', 'warning');
                }
            },
            error: function (xhr, status, err) {
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลจาก API ได้<br><small class="text-muted">' + (xhr.responseText || err) + '</small>', 'error');
                console.error('API Error:', xhr.responseText);
            }
        });
    }

    // Function to load and render patient recent 3-5 visits selector with full Thai date formatting
    function loadPatientVisitHistory(hn, currentVn) {
        $.ajax({
            url: 'api/get_patient_visits.php',
            type: 'GET',
            data: { hn: hn, current_vn: currentVn },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success' && res.visits && res.visits.length > 0) {
                    let html = '<div class="d-flex align-items-center gap-2 flex-wrap">';
                    html += '<span class="fw-semibold text-dark small me-1"><i data-lucide="history" style="width:15px;height:15px;color:#f97316;"></i> ประวัติ Visit ล่าสุดของผู้รับบริการคนนี้:</span>';
                    
                    res.visits.forEach(function(item) {
                        let isCurrent = (item.vn === currentVn);
                        let btnStyle = isCurrent 
                            ? 'background-color:#f97316; color:#ffffff; font-weight:600; border:1px solid #ea580c; box-shadow: 0 2px 4px rgba(249,115,22,0.25);' 
                            : 'background-color:#ffffff; color:#475569; border:1px solid #cbd5e1;';
                        let currentTag = isCurrent ? ' (เลือกอยู่)' : '';
                        
                        html += `<button type="button" class="btn btn-sm rounded-md py-1 px-3" style="font-size:0.82rem; transition:all 0.2s ease; ${btnStyle}" onclick="loadVisitData('${item.vn}')">
                                    <i data-lucide="calendar" style="width:13px;height:13px;"></i> ${item.formatted_date}${currentTag}
                                 </button>`;
                    });
                    html += '</div>';
                    $('#visitHistoryContainer').html(html).show();
                    lucide.createIcons();
                } else {
                    $('#visitHistoryContainer').hide();
                }
            },
            error: function() {
                $('#visitHistoryContainer').hide();
            }
        });
    }

    // 6. Handle Pharmacy Form Submission
    $('#pharmacyForm').on('submit', function (e) {
        e.preventDefault();
        let vn = $('#form_vn').val();
        if (!vn) {
            Swal.fire('แจ้งเตือน', 'กรุณาค้นหาและเลือก Visit (VN) ก่อนบันทึกข้อมูล', 'warning');
            return;
        }

        // Set hidden text labels for Select2
        let drugData = $('#stop_drug_select').select2('data');
        if (drugData && drugData[0]) {
            $('#stop_drug_name_hidden').val(drugData[0].text);
        }
        let docData = $('#consult_doctor_select').select2('data');
        if (docData && docData[0]) {
            $('#consult_doctor_name_hidden').val(docData[0].text);
        }
        let pharmData = $('#pharmacist_select').select2('data');
        if (pharmData && pharmData[0]) {
            $('#pharmacist_name_hidden').val(pharmData[0].text);
        }

        Swal.fire({
            title: 'ยืนยันการบันทึก Pharmacy Note?',
            text: 'บันทึกคำสั่งหยุดยาละลายลิ่มเลือด สำหรับ VN: ' + vn,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f97316',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'บันทึกข้อมูล',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'กำลังบันทึกข้อมูล...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                $.ajax({
                    url: 'api/save_pharmacy_note.php',
                    type: 'POST',
                    data: $('#pharmacyForm').serialize(),
                    dataType: 'json',
                    success: function (res) {
                        if (res.status === 'success') {
                            // Save to LocalStorage for auto-fill next time
                            let noteData = {
                                stop_drug_icode: $('#stop_drug_select').val(),
                                stop_drug_name: $('#stop_drug_name_hidden').val(),
                                stop_start_date: $('#stop_start_date').val(),
                                stop_end_date: $('#stop_end_date').val(),
                                consult_doctor_code: $('#consult_doctor_select').val(),
                                consult_doctor_name: $('#consult_doctor_name_hidden').val(),
                                consult_time: $('#consult_time').val(),
                                pharmacist_name: $('#pharmacist_name_hidden').val(),
                                note_remark: $('#note_remark').val()
                            };
                            localStorage.setItem('lastPharmacyNote', JSON.stringify(noteData));

                            Swal.fire({
                                icon: 'success',
                                title: 'บันทึกสำเร็จ!',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                printOPD();
                            });
                            
                            // Reload Datatable
                            if(typeof reloadNoteTable === 'function') reloadNoteTable();

                        } else {
                            Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อ Server ได้', 'error');
                    }
                });
            }
        });
    });

    // Sync Live OPD Card Preview (With PDPA Masked Name)
    function syncPreview(v) {
        if (!v) return;
        $('#prev_hn').text(v.hn || '-');
        $('#prev_vn').text(v.vn || '-');
        $('#prev_name').text(maskName(v.patient_name) || '-');
        $('#prev_pttype').text(v.pttype_name || '-');
        
        let drugText = $('#stop_drug_select').select2('data')[0] ? $('#stop_drug_select').select2('data')[0].text : '-';
        let docText = $('#consult_doctor_select').select2('data')[0] ? $('#consult_doctor_select').select2('data')[0].text : '-';
        let pharmText = $('#pharmacist_select').select2('data')[0] ? $('#pharmacist_select').select2('data')[0].text : '-';
        let startDate = $('#stop_start_date').val() || '-';
        let endDate = $('#stop_end_date').val() || '-';

        $('#prev_drug').text(drugText);
        $('#prev_date_range').text(`${startDate} ถึง ${endDate}`);
        $('#prev_doctor').text(docText);
        $('#prev_pharmacist').text(pharmText);
    }

    $('#pharmacyForm input, #pharmacyForm select, #pharmacyForm textarea').on('change input', function () {
        let vn = $('#form_vn').val();
        if (vn) {
            syncPreview({ hn: $('#display_hn').text(), vn: vn, patient_name: $('#display_name').text(), pttype_name: $('#display_pttype').text() });
        }
    });

    // 7. Handle "ตอนนี้" Button for Consult Time
    $('#btnNowTime').on('click', function() {
        if ($(this).css('pointer-events') === 'none') return;
        let now = new Date();
        let h = now.getHours().toString().padStart(2, '0');
        let m = now.getMinutes().toString().padStart(2, '0');
        $('#consult_time').val(h + ':' + m).trigger('change');
    });

    // Helper to highlight active selected row in pharmacy note table
    window.highlightActiveTableRow = function(vn) {
        let currentVn = vn || $('#form_vn').val();
        if (!currentVn || !window.pharmacyNoteTable) return;
        
        $('#pharmacyNoteTable tbody tr').removeClass('table-active-selected');
        $('#pharmacyNoteTable tbody tr').each(function() {
            let rowData = window.pharmacyNoteTable.row(this).data();
            if (rowData && rowData.vn === currentVn) {
                $(this).addClass('table-active-selected');
            }
        });
    };

    // 8. Initialize Data Table for History
    window.pharmacyNoteTable = $('#pharmacyNoteTable').DataTable({
        ajax: 'api/get_pharmacy_notes.php',
        responsive: false, // Ensure full horizontal scrolling without hiding columns under '+' child rows
        scrollX: true,     // Native smooth horizontal scrollbar
        order: [[3, 'desc']], // order by date updated (now column index 3)
        createdRow: function(row, data, dataIndex) {
            $(row).addClass('text-nowrap align-middle');
            let currentVn = $('#form_vn').val();
            if (currentVn && data && data.vn === currentVn) {
                $(row).addClass('table-active-selected');
            }
        },
        columns: [
            { data: null, searchable: false, orderable: false, className: 'text-center', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
            { data: null, orderable: false, searchable: false, className: 'text-center text-nowrap', render: function(data, type, row) {
                let isCancelled = (row.status === 'cancelled');
                let cancelBtn = isCancelled
                    ? `<button type="button" class="btn btn-sm btn-outline-success rounded-md" style="padding: 2px 8px; font-size: 0.8rem;" onclick="cancelScreening('${row.vn}', 'restore')" title="คืนสถานะ"><i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i></button>`
                    : `<button type="button" class="btn btn-sm btn-outline-danger rounded-md" style="padding: 2px 8px; font-size: 0.8rem;" onclick="cancelScreening('${row.vn}', 'cancel')" title="ยกเลิกการคัดกรอง (ไม่ลบข้อมูล)"><i data-lucide="ban" style="width:14px;height:14px;"></i></button>`;

                return `<div class="d-inline-flex flex-nowrap gap-1 justify-content-center">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-md" style="padding: 2px 8px; font-size: 0.8rem;" onclick="loadVisitData('${row.vn}')" title="แก้ไข"><i data-lucide="edit" style="width:14px;height:14px;"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-orange rounded-md" style="padding: 2px 8px; font-size: 0.8rem;" onclick="printSticker('${row.vn}')" title="พิมพ์สติกเกอร์"><i data-lucide="tag" style="width:14px;height:14px;"></i></button>
                            <button type="button" class="btn btn-sm btn-orange rounded-md" style="padding: 2px 8px; font-size: 0.8rem;" onclick="printOPD('${row.vn}')" title="พิมพ์ OPD Card"><i data-lucide="printer" style="width:14px;height:14px;"></i></button>
                            ${cancelBtn}
                        </div>`;
            } },
            { data: 'status', render: function(data) {
                if (data === 'cancelled') {
                    return `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 rounded-md" style="font-size:0.78rem;"><i data-lucide="x-circle" style="width:12px;height:12px;vertical-align:-1px;"></i> ยกเลิก</span>`;
                }
                return `<span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-md" style="font-size:0.78rem;"><i data-lucide="check-circle-2" style="width:12px;height:12px;vertical-align:-1px;"></i> บันทึก</span>`;
            } },
            { data: 'updated_at', render: function(data) { return data ? data.substring(0, 16) : '-'; } },
            { data: 'hn', render: function(data) { return `<strong>${data}</strong>`; } },
            { data: 'vn' },
            { data: 'consult_doctor_name' },
            { data: 'stop_drug_name', render: function(data) { return data ? `<span class="badge bg-danger rounded-md" style="font-weight: 500;">${data}</span>` : '-'; } },
            { data: null, render: function(data, type, row) { 
                if(!row.stop_start_date && !row.stop_end_date) return '-';
                return (row.stop_start_date || '-') + ' ถึง ' + (row.stop_end_date || '-'); 
            } },
            { data: 'pharmacist_name' }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/th.json'
        },
        drawCallback: function() {
            lucide.createIcons();
            if (typeof highlightActiveTableRow === 'function') {
                highlightActiveTableRow();
            }
        }
    });

    window.reloadNoteTable = function() {
        if (window.pharmacyNoteTable) {
            window.pharmacyNoteTable.ajax.reload(null, false);
        }
    }

    // Auto-refresh table every 15 seconds for real-time standards
    setInterval(function() {
        window.reloadNoteTable();
    }, 15000);

});

// Helper Function: Print OPD Card
function printOPD(vn) {
    let targetVn = vn || $('#form_vn').val();
    let selectedSize = $('#paperSizeSelectMain').val() || 'a5-landscape';
    if (!targetVn) {
        Swal.fire('แจ้งเตือน', 'กรุณาเลือก Visit (VN) ก่อนสั่งพิมพ์', 'warning');
        return;
    }

    // When printing from table row (vn passed), just open saved data from DB
    let isFormPrint = (!vn || vn === $('#form_vn').val());

    // Read live Select2 values directly (not relying on possibly stale hidden fields)
    let drugName = '';
    let drugData = $('#stop_drug_select').select2('data');
    if (drugData && drugData[0]) drugName = drugData[0].text;

    let doctorName = '';
    let docData = $('#consult_doctor_select').select2('data');
    if (docData && docData[0]) doctorName = docData[0].text;

    let pharmacistName = '';
    let pharmData = $('#pharmacist_select').select2('data');
    if (pharmData && pharmData[0]) pharmacistName = pharmData[0].text;

    // Create a dynamic form to submit to print_opd.php for live printing
    let form = $('<form>', { action: 'print_opd.php', target: '_blank', method: 'POST' });
    form.append($('<input>', { type: 'hidden', name: 'vn', value: targetVn }));
    form.append($('<input>', { type: 'hidden', name: 'size', value: selectedSize }));

    if (isFormPrint) {
        // Live form print: pass all current form data
        form.append($('<input>', { type: 'hidden', name: 'live_print', value: '1' }));
        form.append($('<input>', { type: 'hidden', name: 'stop_drug_name', value: drugName }));
        form.append($('<input>', { type: 'hidden', name: 'stop_start_date', value: $('#stop_start_date').val() }));
        form.append($('<input>', { type: 'hidden', name: 'stop_end_date', value: $('#stop_end_date').val() }));
        form.append($('<input>', { type: 'hidden', name: 'consult_doctor_name', value: doctorName }));
        form.append($('<input>', { type: 'hidden', name: 'consult_time', value: $('#consult_time').val() }));
        form.append($('<input>', { type: 'hidden', name: 'pharmacist_name', value: pharmacistName }));
        form.append($('<input>', { type: 'hidden', name: 'note_remark', value: $('#note_remark').val() }));
    }
    // else: no live_print flag → print_opd.php will load from DB (for table row print)

    $('body').append(form);
    form.submit();
    form.remove();
}

// Helper Function: Print Sticker (TSC TE210)
function printSticker(vn) {
    let targetVn = vn || $('#form_vn').val();
    let selectedSize = $('#stickerSizeSelect').val() || '50x30';
    if (!targetVn) {
        Swal.fire('แจ้งเตือน', 'กรุณาเลือก Visit (VN) ก่อนสั่งพิมพ์สติกเกอร์', 'warning');
        return;
    }

    let isFormPrint = (!vn || vn === $('#form_vn').val());

    let drugName = '';
    let drugData = $('#stop_drug_select').select2('data');
    if (drugData && drugData[0]) drugName = drugData[0].text;

    let doctorName = '';
    let docData = $('#consult_doctor_select').select2('data');
    if (docData && docData[0]) doctorName = docData[0].text;

    let pharmacistName = '';
    let pharmData = $('#pharmacist_select').select2('data');
    if (pharmData && pharmData[0]) pharmacistName = pharmData[0].text;

    let form = $('<form>', { action: 'print_sticker.php', target: '_blank', method: 'POST' });
    form.append($('<input>', { type: 'hidden', name: 'vn', value: targetVn }));
    form.append($('<input>', { type: 'hidden', name: 'size', value: selectedSize }));

    if (isFormPrint) {
        form.append($('<input>', { type: 'hidden', name: 'live_print', value: '1' }));
        form.append($('<input>', { type: 'hidden', name: 'stop_drug_name', value: drugName }));
        form.append($('<input>', { type: 'hidden', name: 'stop_start_date', value: $('#stop_start_date').val() }));
        form.append($('<input>', { type: 'hidden', name: 'stop_end_date', value: $('#stop_end_date').val() }));
        form.append($('<input>', { type: 'hidden', name: 'consult_doctor_name', value: doctorName }));
        form.append($('<input>', { type: 'hidden', name: 'consult_time', value: $('#consult_time').val() }));
        form.append($('<input>', { type: 'hidden', name: 'pharmacist_name', value: pharmacistName }));
        form.append($('<input>', { type: 'hidden', name: 'note_remark', value: $('#note_remark').val() }));
    }

    $('body').append(form);
    form.submit();
    form.remove();
}

// Function to open HOSxP Database Config & Test Modal
function openDbConfigModal() {
    let dbModal = new bootstrap.Modal(document.getElementById('dbConfigModal'));
    dbModal.show();
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// Function to test HOSxP Database Connection via API
function testDbConnection() {
    let host = $('#cfg_host').val().trim();
    let port = $('#cfg_port').val().trim();
    let user = $('#cfg_user').val().trim();
    let pass = $('#cfg_pass').val();
    let dbname = $('#cfg_dbname').val().trim();

    let btn = $('#btnTestConn');
    let spinIcon = $('#iconTestSpin');
    let resContainer = $('#modalTestResult');

    btn.prop('disabled', true);
    spinIcon.addClass('spin-anim');

    $.ajax({
        url: 'api/test_db.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            host: host,
            port: port,
            user: user,
            pass: pass,
            dbname: dbname
        }),
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false);
            spinIcon.removeClass('spin-anim');

            if (res.success) {
                resContainer.html(`
                    <div class="card border-0 shadow-sm rounded-md overflow-hidden bg-success-subtle border-start border-4 border-success">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-md bg-success text-white d-flex align-items-center justify-content-center p-2 shadow-sm" style="width:36px;height:36px;">
                                        <i data-lucide="check-circle-2" style="width:20px;height:20px;"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-success-emphasis mb-0" style="font-size:0.92rem;">${res.message}</h6>
                                        <div class="small text-secondary mt-0.5" style="font-size:0.78rem;">
                                            <i data-lucide="server" style="width:13px;height:13px;color:#16a34a;"></i> HIS connected: <span class="font-monospace text-dark-emphasis">${res.host}:${res.port}</span>
                                        </div>
                                    </div>
                                </div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace px-2.5 py-1 rounded-md shadow-xs" style="font-size:0.75rem; white-space:nowrap;">
                                    <i data-lucide="zap" style="width:12px;height:12px;" class="me-0.5"></i> ${res.response_time}
                                </span>
                            </div>
                        </div>
                    </div>
                `);
            } else {
                resContainer.html(`
                    <div class="card border-0 shadow-sm rounded-md overflow-hidden bg-danger-subtle border-start border-4 border-danger">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-md bg-danger text-white d-flex align-items-center justify-content-center p-2 shadow-sm" style="width:36px;height:36px;">
                                        <i data-lucide="alert-circle" style="width:20px;height:20px;"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-danger-emphasis mb-0" style="font-size:0.9rem;">การเชื่อมต่อล้มเหลว</h6>
                                        <div class="small text-danger mt-1" style="font-size:0.78rem;line-height:1.35;">${res.message}</div>
                                        <div class="small text-muted mt-1 font-monospace" style="font-size:0.75rem;">Host: ${res.host}:${res.port}</div>
                                    </div>
                                </div>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace px-2.5 py-1 rounded-md" style="font-size:0.75rem;">
                                    ${res.response_time}
                                </span>
                            </div>
                        </div>
                    </div>
                `);
            }
            if (typeof lucide !== 'undefined') lucide.createIcons();
        },
        error: function(xhr, status, err) {
            btn.prop('disabled', false);
            spinIcon.removeClass('spin-anim');
            resContainer.html(`
                <div class="card border-0 shadow-sm rounded-md bg-danger-subtle border-start border-4 border-danger p-3">
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="x-circle" class="text-danger" style="width:20px;height:20px;"></i>
                        <div>
                            <strong class="d-block small text-danger" style="font-size:0.85rem;">API Connection Error</strong>
                            <span class="small text-muted" style="font-size:0.78rem;">${xhr.responseText || err}</span>
                        </div>
                    </div>
                </div>
            `);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    });
}

// Function to save HOSxP Database Connection config via API
function saveDbConnection() {
    let host = $('#cfg_host').val().trim();
    let port = $('#cfg_port').val().trim();
    let user = $('#cfg_user').val().trim();
    let pass = $('#cfg_pass').val();
    let dbname = $('#cfg_dbname').val().trim();

    if (!host || !port || !user || !dbname) {
        Swal.fire('ข้อมูลไม่ครบถ้วน', 'กรุณากรอกข้อมูลการเชื่อมต่อให้ครบถ้วนก่อนบันทึก', 'warning');
        return;
    }

    Swal.fire({
        title: 'บันทึกการตั้งค่าฐานข้อมูล?',
        text: `ระบบจะบันทึกและใช้การเชื่อมต่อ HOSxP ที่ ${host}:${port} (${dbname})`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f97316',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'บันทึกและใช่งาน',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.showLoading();
            $.ajax({
                url: 'api/save_db_config.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    host: host,
                    port: port,
                    user: user,
                    pass: pass,
                    dbname: dbname
                }),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            title: 'บันทึกสำเร็จ!',
                            text: res.message,
                            icon: 'success',
                            confirmButtonColor: '#f97316'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('บันทึกไม่สำเร็จ', res.message, 'error');
                    }
                },
                error: function(xhr, status, err) {
                    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถบันทึกการตั้งค่าได้: ' + (xhr.responseText || err), 'error');
                }
            });
        }
    });
}

// Function to copy HN to clipboard safely
function copyHnToClipboard() {
    let hn = $('#display_hn').text().trim();
    if (!hn || hn === '-') {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: 'ยังไม่มีข้อมูล HN ให้คัดกรอง',
            showConfirmButton: false,
            timer: 1800
        });
        return;
    }

    navigator.clipboard.writeText(hn).then(function() {
        // Change icon temporarily to check-mark
        let btn = $('#btnCopyHn');
        btn.html('<i data-lucide="check" style="width:13px;height:13px;color:#4ade80;"></i>');
        if (typeof lucide !== 'undefined') lucide.createIcons();

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `คัดลอก HN: ${hn} แล้ว`,
            showConfirmButton: false,
            timer: 1500
        });

        setTimeout(function() {
            btn.html('<i data-lucide="copy" style="width:13px;height:13px;"></i>');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }, 1500);
    }).catch(function(err) {
        // Fallback for older browsers
        let tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(hn).select();
        document.execCommand('copy');
        tempInput.remove();

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `คัดลอก HN: ${hn} แล้ว`,
            showConfirmButton: false,
            timer: 1500
        });
    });
}

// Function to cancel or restore screening status without deleting from DB
function cancelScreening(vn, action) {
    let isRestore = (action === 'restore');
    let titleText = isRestore ? 'ยืนยันคืนสถานะการคัดกรอง?' : 'ยืนยันยกเลิกการคัดกรอง?';
    let bodyText = isRestore 
        ? `คืนสถานะการคัดกรองสำหรับ VN: ${vn}` 
        : `รายการของ VN: ${vn} จะถูกเปลี่ยนเป็น "ยกเลิกการคัดกรอง" โดยไม่ลบข้อมูลออกจากตาราง oprint_pharmacy_note`;
    let confirmBtnText = isRestore ? 'คืนสถานะ' : 'ยกเลิกการคัดกรอง';
    let confirmColor = isRestore ? '#16a34a' : '#ef4444';

    Swal.fire({
        title: titleText,
        text: bodyText,
        icon: isRestore ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#64748b',
        confirmButtonText: confirmBtnText,
        cancelButtonText: 'ปิดหน้าต่าง'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'กำลังดำเนินการ...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            $.ajax({
                url: 'api/cancel_pharmacy_note.php',
                type: 'POST',
                data: { vn: vn, action: action },
                dataType: 'json',
                success: function (res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'ดำเนินการเรียบร้อย!',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        
                        // Reload Datatable
                        if (typeof reloadNoteTable === 'function') reloadNoteTable();
                    } else {
                        Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                    }
                },
            });
        }
    });
}

// Function to clear all form inputs, demographics, and visit search selection
function clearAllFormAndSearch() {
    // Clear Select2 search dropdown
    $('#visitSearchSelect').val(null).trigger('change');

    // Clear Hidden Fields
    $('#form_vn').val('');
    $('#form_hn').val('');
    $('#stop_drug_name_hidden').val('');
    $('#consult_doctor_name_hidden').val('');
    $('#pharmacist_name_hidden').val('');

    // Reset Demographics Display
    $('#display_hn').text('-');
    $('#display_vn').text('-');
    $('#display_name').text('-');
    $('#display_cid').text('-');
    $('#display_age').text('-');
    $('#display_sex').text('-');
    $('#display_vstdate').text('-');
    $('#display_pttype').text('-');
    $('#display_addr').text('-');
    $('#display_tel').text('-');
    $('#display_bloodgrp').text('-');
    $('#display_clinic').text('ไม่มีข้อมูลโรคประจำตัว');
    $('#display_allergy').text('ไม่มีประวัติแพ้ยา');
    $('#visitHistoryContainer').hide().empty();

    // Reset Form Controls
    $('#stop_drug_select').val(null).trigger('change');
    $('#consult_doctor_select').val(null).trigger('change');
    $('#pharmacist_select').val(null).trigger('change');
    $('#stop_start_date').val('');
    $('#stop_end_date').val('');
    $('#consult_time').val('');
    $('#note_remark').val('');

    // Disable Form & Print Controls until next visit search
    $('#pharmacyForm input, #pharmacyForm select, #pharmacyForm textarea, #btnSave, #btnPrintOPD, .drug-shortcut-btn, .remark-shortcut-btn').prop('disabled', true);
    $('#btnNowTime').css({'pointer-events': 'none', 'opacity': '0.6'});
    $('#btnClearAllForm').addClass('d-none').removeClass('d-flex').hide();

    // Reset Preview OPD Card
    $('#prev_hn').text('-');
    $('#prev_vn').text('-');
    $('#prev_name').text('-');
    $('#prev_pttype').text('-');
    $('#prev_drug').text('-');
    $('#prev_date_range').text('-');
    $('#prev_doctor').text('-');
    $('#prev_pharmacist').text('-');

    // Remove active highlight row in DataTables
    if (typeof highlightActiveTableRow === 'function') {
        $('#pharmacyNoteTable tbody tr').removeClass('table-active-selected');
    }

    // Refresh Lucide Icons
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

