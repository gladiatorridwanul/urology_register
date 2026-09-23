<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);

// ---- Fetch patient ----
$p = $conn->query("SELECT * FROM patients WHERE id=$id")->fetch_assoc();
if (!$p) die("Patient not found");

// ---- Fetch EVERYTHING for the patient ----
$dx      = $conn->query("SELECT * FROM diagnosis WHERE patient_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$op      = $conn->query("SELECT * FROM operations WHERE patient_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$ds      = $conn->query("SELECT * FROM discharge_summary WHERE patient_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$cc      = $conn->query("SELECT * FROM chief_complaints WHERE patient_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$uh      = $conn->query("SELECT * FROM urological_history WHERE patient_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$cm      = $conn->query("SELECT * FROM comorbidity WHERE patient_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$dh      = $conn->query("SELECT * FROM drug_history WHERE patient_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$ph      = $conn->query("SELECT * FROM personal_history WHERE patient_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$fh      = $conn->query("SELECT * FROM family_history WHERE patient_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$mh      = $conn->query("SELECT * FROM menstrual_history WHERE patient_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$ge      = $conn->query("SELECT * FROM general_exam WHERE patient_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$gue     = $conn->query("SELECT * FROM genitourinary_exam WHERE patient_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$ose     = $conn->query("SELECT * FROM other_system_exam WHERE patient_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$lab     = $conn->query("SELECT * FROM lab_investigations WHERE patient_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$fu      = $conn->query("SELECT * FROM followup WHERE patient_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();

// ---- All imaging reports ----
$imgRows = [];
$imgQ = $conn->query("SELECT * FROM imaging_reports WHERE patient_id=$id ORDER BY id DESC");
while ($row = $imgQ->fetch_assoc()) $imgRows[] = $row;

/** Null-safe display */
function val($v, $dash = '—') {
    return (!empty($v) || $v === '0') ? htmlspecialchars((string)$v) : $dash;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Full Patient Report — <?= htmlspecialchars($p['name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { padding: 24px; font-size: 13px; color: #1e293b; }
    .hdr { text-align: center; border-bottom: 3px double #2563eb; padding-bottom: 10px; margin-bottom: 18px; }
    .hdr h3 { margin: 0; color: #2563eb; font-size: 22px; }
    .hdr p  { margin: 2px 0; font-size: 12px; color: #64748b; }
    .badge-id { display:inline-block; background:#2563eb; color:#fff; padding: 2px 10px; border-radius: 10px; font-size: 11px; }

    .section { margin-bottom: 14px; page-break-inside: avoid; }
    .section-title {
        background: #eef2ff;
        color: #1e40af;
        padding: 5px 10px;
        font-size: 12.5px;
        font-weight: 700;
        border-left: 3px solid #2563eb;
        margin: 0 0 6px;
        border-radius: 3px;
    }
    table.info { width: 100%; border-collapse: collapse; }
    table.info td { padding: 4px 8px; font-size: 12px; vertical-align: top; border-bottom: 1px solid #f1f5f9; }
    table.info td:first-child { font-weight: 600; color: #475569; width: 180px; }

    table.grid { width: 100%; border-collapse: collapse; font-size: 12px; }
    table.grid thead th { background: #f1f5f9; color: #475569; padding: 5px 8px; border: 1px solid #e2e8f0; text-align: left; }
    table.grid tbody td { padding: 5px 8px; border: 1px solid #e2e8f0; vertical-align: top; }

    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px; }
    .two-col .cell { padding: 3px 0; border-bottom: 1px dashed #e2e8f0; }
    .cell .lbl { color: #64748b; font-weight: 600; margin-right: 4px; }
    .cell .val { color: #1e293b; }

    .pill {
        display: inline-block;
        padding: 2px 8px;
        background: #dcfce7;
        color: #166534;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
        margin-right: 4px;
        margin-bottom: 3px;
    }
    .pill-pending { background:#e2e8f0; color:#475569; }
    .pill-yes     { background:#dcfce7; color:#166534; }
    .pill-no      { background:#f1f5f9; color:#64748b; }

    .signature { margin-top: 40px; text-align: right; }
    .signature .line { display: inline-block; border-top: 1px solid #1e293b; padding-top: 4px; width: 200px; font-size: 11px; }
    .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }

    .thumb-img { max-width: 90px; max-height: 90px; border: 1px solid #e2e8f0; border-radius: 4px; }
    .thumb-pdf { display:inline-block; padding: 4px 8px; background:#fef2f2; color:#dc2626; border-radius: 4px; font-size: 11px; font-weight: 600; }

    @media print {
        body { padding: 12px; }
        .no-print { display: none !important; }
        .section { page-break-inside: avoid; }
    }
    @media (max-width: 700px) {
        .two-col { grid-template-columns: 1fr; }
    }
</style>
</head>
<body>

<!-- ============ HEADER ============ -->
<div class="hdr">
    <h3>Department of Urology</h3>
    <p>Urology Patient Registry &amp; Clinical Database</p>
    <p><strong>Complete Patient Record</strong></p>
</div>

<!-- ============ PATIENT PROFILE ============ -->
<div class="section">
    <div class="section-title">Patient Profile</div>
    <table class="info">
        <tr><td>Patient Name:</td><td><?= val($p['name']) ?></td>
            <td>URO ID:</td><td><span class="badge-id"><?= val($p['uro_id']) ?></span></td></tr>
        <tr><td>Age / Sex:</td><td><?= val($p['age']) ?> / <?= val($p['sex']) ?></td>
            <td>Date of Birth:</td><td><?= $p['dob'] ? date('d M Y', strtotime($p['dob'])) : '—' ?></td></tr>
        <tr><td>Mobile No.:</td><td><?= val($p['mobile']) ?></td>
            <td>Blood Group:</td><td><?= val($p['blood_group']) ?></td></tr>
        <tr><td>Guardian:</td><td><?= val($p['guardian_name']) ?></td>
            <td>Occupation:</td><td><?= val($p['occupation']) ?></td></tr>
        <tr><td>NID:</td><td><?= val($p['nid']) ?></td>
            <td>Hospital Reg No:</td><td><?= val($p['hospital_reg_no']) ?></td></tr>
        <tr><td>Address:</td><td colspan="3"><?= val($p['address']) ?></td></tr>
        <tr><td>First Admission:</td><td><?= $p['first_admission_date'] ? date('d M Y', strtotime($p['first_admission_date'])) : '—' ?></td>
            <td>Current Visit:</td><td><?= $p['current_visit_date'] ? date('d M Y', strtotime($p['current_visit_date'])) : '—' ?></td></tr>
        <tr><td>Mode of Admission:</td><td><?= val($p['mode_of_admission']) ?></td>
            <td>Unit / Ward / Bed:</td><td><?= val($p['unit']) ?> / <?= val($p['ward']) ?> / <?= val($p['bed']) ?></td></tr>
        <tr><td>Discharge Date:</td><td colspan="3"><?= $p['discharge_date'] ? date('d M Y', strtotime($p['discharge_date'])) : '— (still admitted)' ?></td></tr>
    </table>
</div>

<!-- ============ 1. CHIEF COMPLAINTS ============ -->
<?php if ($cc): ?>
<div class="section">
    <div class="section-title">1. Presenting / Chief Complaints</div>
    <div class="two-col">
        <?php if (!empty($cc['luts'])): ?>
            <div class="cell"><span class="lbl">LUTS:</span> <span class="val"><?= val($cc['luts']) ?><?= $cc['luts_specify'] ? ' — ' . val($cc['luts_specify']) : '' ?><?= $cc['luts_duration'] ? ' (' . val($cc['luts_duration']) . ')' : '' ?></span></div>
        <?php endif; ?>
        <?php if ($cc['retention_present']): ?>
            <div class="cell"><span class="lbl">Retention:</span> <span class="val"><?= val($cc['retention']) ?><?= $cc['retention_duration'] ? ' — ' . val($cc['retention_duration']) : '' ?></span></div>
        <?php endif; ?>
        <?php if (!empty($cc['pain'])): ?>
            <div class="cell"><span class="lbl">Pain:</span> <span class="val"><?= val($cc['pain']) ?><?= $cc['pain_duration'] ? ' (' . val($cc['pain_duration']) . ')' : '' ?></span></div>
        <?php endif; ?>
        <?php if (!empty($cc['hematuria'])): ?>
            <div class="cell"><span class="lbl">Hematuria:</span> <span class="val"><?= val($cc['hematuria']) ?><?= $cc['hematuria_duration'] ? ' (' . val($cc['hematuria_duration']) . ')' : '' ?></span></div>
        <?php endif; ?>
        <?php if ($cc['fever_present']): ?>
            <div class="cell"><span class="lbl">Fever:</span> <span class="val">Yes — <?= val($cc['fever_details']) ?><?= $cc['fever_duration'] ? ' (' . val($cc['fever_duration']) . ')' : '' ?></span></div>
        <?php else: ?>
            <div class="cell"><span class="lbl">Fever:</span> <span class="val">No</span></div>
        <?php endif; ?>
        <?php if (!empty($cc['others'])): ?>
            <div class="cell"><span class="lbl">Others:</span> <span class="val"><?= val($cc['others']) ?><?= $cc['others_duration'] ? ' (' . val($cc['others_duration']) . ')' : '' ?></span></div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============ 2. UROLOGICAL HISTORY ============ -->
<?php if ($uh): ?>
<div class="section">
    <div class="section-title">2. History of Past Illness / Previous Urological History</div>
    <table class="grid">
        <tbody>
            <tr><td style="width:280px"><strong>History of trauma</strong></td><td><?= val($uh['trauma']) ?> <?= $uh['trauma_details'] ? ' — ' . val($uh['trauma_details']) : '' ?></td></tr>
            <tr><td><strong>Per-urethral instrumentation</strong></td><td><?= val($uh['instrumentation']) ?> <?= $uh['instrumentation_details'] ? ' — ' . val($uh['instrumentation_details']) : '' ?></td></tr>
            <?php if (isset($uh['catheterization'])): ?>
                <tr><td><strong>Per-urethral catheterization</strong></td><td><?= val($uh['catheterization']) ?> <?= !empty($uh['catheterization_details']) ? ' — ' . val($uh['catheterization_details']) : '' ?></td></tr>
            <?php endif; ?>
            <tr><td><strong>History of surgery</strong></td><td><?= val($uh['surgery']) ?> <?= $uh['surgery_details'] ? ' — ' . val($uh['surgery_details']) : '' ?></td></tr>
            <tr><td><strong>Urinary stone disease</strong></td><td><?= val($uh['stone_disease']) ?> <?= $uh['stone_details'] ? ' — ' . val($uh['stone_details']) : '' ?></td></tr>
            <tr><td><strong>Urological malignancy</strong></td><td><?= val($uh['malignancy']) ?> <?= $uh['malignancy_details'] ? ' — ' . val($uh['malignancy_details']) : '' ?></td></tr>
            <?php if (!empty($uh['others'])): ?>
                <tr><td><strong>Others</strong></td><td><?= val($uh['others']) ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ============ 3. COMORBIDITY ============ -->
<?php if ($cm): ?>
<div class="section">
    <div class="section-title">3. Comorbidity</div>
    <div style="margin-bottom:6px">
        <?php
        $comorbPills = [];
        if ($cm['htn'])          $comorbPills[] = 'HTN';
        if ($cm['dm'])           $comorbPills[] = 'DM';
        if ($cm['ihd'])          $comorbPills[] = 'IHD';
        if ($cm['ckd'])          $comorbPills[] = 'CKD';
        if ($cm['copd'])         $comorbPills[] = 'COPD';
        if ($cm['neurological']) $comorbPills[] = 'Neurological';
        ?>
        <?php if (empty($comorbPills)): ?>
            <span class="text-muted">No comorbidity recorded.</span>
        <?php else: foreach ($comorbPills as $cp): ?>
            <span class="pill"><?= $cp ?></span>
        <?php endforeach; endif; ?>
    </div>
    <?php if (!empty($cm['neurological_details'])): ?>
        <div><strong>Neurological details:</strong> <?= val($cm['neurological_details']) ?></div>
    <?php endif; ?>
    <?php if (!empty($cm['others'])): ?>
        <div><strong>Others:</strong> <?= val($cm['others']) ?></div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ============ 4. DRUG HISTORY ============ -->
<?php if ($dh && !empty($dh['drug_history'])): ?>
<div class="section">
    <div class="section-title">4. Drug History</div>
    <div><?= nl2br(val($dh['drug_history'])) ?></div>
</div>
<?php endif; ?>

<!-- ============ 5. PERSONAL HISTORY ============ -->
<?php if ($ph): ?>
<div class="section">
    <div class="section-title">5. Personal History</div>
    <div class="two-col">
        <div class="cell"><span class="lbl">Smoking:</span> <span class="val"><?= val($ph['smoking']) ?><?= $ph['smoking_details'] ? ' — ' . val($ph['smoking_details']) : '' ?></span></div>
        <div class="cell"><span class="lbl">Alcohol:</span> <span class="val"><?= val($ph['alcohol']) ?><?= $ph['alcohol_details'] ? ' — ' . val($ph['alcohol_details']) : '' ?></span></div>
        <div class="cell"><span class="lbl">Betel nut:</span> <span class="val"><?= val($ph['betel']) ?><?= $ph['betel_details'] ? ' — ' . val($ph['betel_details']) : '' ?></span></div>
        <?php if (!empty($ph['others'])): ?>
            <div class="cell"><span class="lbl">Others:</span> <span class="val"><?= val($ph['others']) ?></span></div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============ 6. FAMILY HISTORY ============ -->
<?php if ($fh && !empty($fh['family_history'])): ?>
<div class="section">
    <div class="section-title">6. Family History</div>
    <div><?= nl2br(val($fh['family_history'])) ?></div>
</div>
<?php endif; ?>

<!-- ============ 7. MENSTRUAL HISTORY ============ -->
<?php if ($mh && strtolower($p['sex']) === 'female'): ?>
<div class="section">
    <div class="section-title">7. Menstrual &amp; Obstetric History</div>
    <div class="two-col">
        <div class="cell"><span class="lbl">Menstrual Flow:</span> <span class="val"><?= val($mh['menstrual_flow']) ?></span></div>
        <div class="cell"><span class="lbl">Menstrual Cycle:</span> <span class="val"><?= val($mh['menstrual_cycle']) ?></span></div>
        <div class="cell"><span class="lbl">LMP:</span> <span class="val"><?= $mh['lmp'] ? date('d M Y', strtotime($mh['lmp'])) : val($mh['lmp_free']) ?></span></div>
        <div class="cell"><span class="lbl">Para:</span> <span class="val"><?= val($mh['para']) ?></span></div>
        <div class="cell"><span class="lbl">Gravidity:</span> <span class="val"><?= val($mh['gravidity']) ?></span></div>
    </div>
</div>
<?php endif; ?>

<!-- ============ 8. GENERAL EXAMINATION ============ -->
<?php if ($ge): ?>
<div class="section">
    <div class="section-title">8. General Examination</div>
    <table class="grid">
        <tbody>
            <tr>
                <td style="width:180px"><strong>Anaemia</strong></td>
                <td><?= val($ge['anaemia']) ?><?= $ge['anaemia_details'] ? ' — ' . val($ge['anaemia_details']) : '' ?></td>
            </tr>
            <tr>
                <td><strong>Jaundice</strong></td>
                <td><?= val($ge['jaundice']) ?><?= $ge['jaundice_details'] ? ' — ' . val($ge['jaundice_details']) : '' ?></td>
            </tr>
            <tr>
                <td><strong>Edema</strong></td>
                <td><?= val($ge['edema']) ?><?= $ge['edema_details'] ? ' — ' . val($ge['edema_details']) : '' ?></td>
            </tr>
            <tr>
                <td><strong>Pulse / BP / Temp</strong></td>
                <td><?= val($ge['pulse']) ?> / <?= val($ge['bp']) ?> / <?= val($ge['temperature']) ?></td>
            </tr>
            <tr>
                <td><strong>Lymph node status</strong></td>
                <td><?= val($ge['lymph_node']) ?></td>
            </tr>
            <?php if (!empty($ge['others'])): ?>
                <tr><td><strong>Others</strong></td><td><?= val($ge['others']) ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ============ 9. GENITOURINARY EXAMINATION ============ -->
<?php if ($gue): ?>
<div class="section">
    <div class="section-title">9. Genitourinary Examination</div>
    <table class="grid">
        <tbody>
            <tr><td style="width:220px"><strong>Renal angle</strong></td><td><?= val($gue['renal_angle']) ?></td></tr>
            <tr><td><strong>Kidney</strong></td><td><?= val($gue['kidney']) ?><?= $gue['kidney_details'] ? ' — ' . val($gue['kidney_details']) : '' ?></td></tr>
            <tr><td><strong>Suprapubic — bladder fullness</strong></td><td><?= val($gue['bladder_fullness']) ?></td></tr>
            <tr><td><strong>Suprapubic — tenderness</strong></td><td><?= val($gue['suprapubic_tenderness']) ?></td></tr>
            <tr><td><strong>Hernial orifice</strong></td><td><?= val($gue['hernial_orifice']) ?></td></tr>
            <tr><td><strong>External urethral meatus</strong></td><td><?= val($gue['meatus']) ?><?= $gue['meatus_details'] ? ' — ' . val($gue['meatus_details']) : '' ?></td></tr>
            <tr><td><strong>Testis</strong></td><td><?= val($gue['testis']) ?><?= $gue['testis_details'] ? ' — ' . val($gue['testis_details']) : '' ?></td></tr>
            <tr><td><strong>Scrotum</strong></td><td><?= val($gue['scrotum']) ?><?= $gue['scrotum_details'] ? ' — ' . val($gue['scrotum_details']) : '' ?></td></tr>
            <tr><td><strong>Penis</strong></td><td><?= val($gue['penis']) ?><?= $gue['penis_details'] ? ' — ' . val($gue['penis_details']) : '' ?></td></tr>
            <tr><td><strong>DRE — prostate</strong></td><td><?= val($gue['dre_prostate']) ?></td></tr>
            <tr><td><strong>Anal tone</strong></td><td><?= val($gue['anal_tone']) ?></td></tr>
            <tr><td><strong>Bulbocavernosus reflex</strong></td><td><?= val($gue['bulbocavernosus']) ?></td></tr>
            <?php if (!empty($gue['others'])): ?>
                <tr><td><strong>Others</strong></td><td><?= val($gue['others']) ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ============ 10. OTHER SYSTEM EXAMINATION ============ -->
<?php if ($ose && !empty($ose['other_system'])): ?>
<div class="section">
    <div class="section-title">10. Other System Examination</div>
    <div><?= nl2br(val($ose['other_system'])) ?></div>
</div>
<?php endif; ?>

<!-- ============ 11. DIAGNOSIS ============ -->
<?php if ($dx): ?>
<div class="section">
    <div class="section-title">11. Diagnosis</div>
    <div>
        <?php
        $dxPills = [];
        if ($dx['renal_stone']      !== 'None') $dxPills[] = 'Renal Stone (' . $dx['renal_stone'] . ')';
        if ($dx['ureteric_stone']   !== 'None') $dxPills[] = 'Ureteric Stone — ' . $dx['ureteric_stone'] . ($dx['ureteric_location'] ? ' (' . $dx['ureteric_location'] . ')' : '');
        if ($dx['bladder_stone'])               $dxPills[] = 'Urinary Bladder Stone';
        if ($dx['bph'])                         $dxPills[] = 'BPH';
        if ($dx['carcinoma_prostate'])          $dxPills[] = 'Carcinoma Prostate';
        if ($dx['renal_mass']       !== 'None') $dxPills[] = 'Renal Mass (' . $dx['renal_mass'] . ')';
        if ($dx['utuc']             !== 'None') $dxPills[] = 'UTUC (' . $dx['utuc'] . ')';
        if ($dx['bladder_mass'])                $dxPills[] = 'Bladder Mass';
        if ($dx['stricture_urethra'])           $dxPills[] = 'Stricture Urethra' . ($dx['stricture_details'] ? ' — ' . $dx['stricture_details'] : '');
        if ($dx['pelvic_fracture'])             $dxPills[] = 'Pelvic Fracture Urethral Injury';
        if ($dx['hypospadias'])                 $dxPills[] = 'Hypospadias' . ($dx['hypospadias_details'] ? ' — ' . $dx['hypospadias_details'] : '');
        if ($dx['puj_obstruction']  !== 'None') $dxPills[] = 'PUJ Obstruction (' . $dx['puj_obstruction'] . ')';
        if ($dx['testicular_tumor'] !== 'None') $dxPills[] = 'Testicular Tumor (' . $dx['testicular_tumor'] . ')';
        if ($dx['carcinoma_penis'])             $dxPills[] = 'Carcinoma Penis';
        if ($dx['varicocele']       !== 'None') $dxPills[] = 'Varicocele (' . $dx['varicocele'] . ')';
        if (!empty($dx['others']))              $dxPills[] = $dx['others'];
        ?>
        <?php if (empty($dxPills)): ?>
            <span class="text-muted">No diagnosis recorded.</span>
        <?php else: foreach ($dxPills as $dp): ?>
            <span class="pill"><?= htmlspecialchars($dp) ?></span>
        <?php endforeach; endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============ 12. LAB INVESTIGATIONS ============ -->
<?php if ($lab): ?>
<div class="section">
    <div class="section-title">12. Laboratory Investigations</div>
    <table class="grid">
        <thead>
            <tr><th>Test</th><th>Result</th><th>Test</th><th>Result</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Hemoglobin</td><td><?= val($lab['hemoglobin']) ?></td>
                <td>Total WBC Count</td><td><?= val($lab['wbc']) ?></td>
            </tr>
            <tr>
                <td>Platelet Count</td><td><?= val($lab['platelet']) ?></td>
                <td>ESR</td><td><?= val($lab['esr']) ?></td>
            </tr>
            <tr>
                <td>Serum Creatinine</td><td><?= val($lab['creatinine']) ?></td>
                <td>Serum PSA</td><td><?= val($lab['psa']) ?></td>
            </tr>
            <tr>
                <td>Serum Electrolyte</td><td colspan="3"><?= val($lab['electrolytes']) ?></td>
            </tr>
            <tr>
                <td>Urine R/E — RBC</td><td><?= val($lab['urine_rbc']) ?></td>
                <td>Urine R/E — Pus Cell</td><td><?= val($lab['urine_pus']) ?></td>
            </tr>
            <tr>
                <td>RBS</td><td><?= val($lab['rbs']) ?></td>
                <td>Urine C/S — Growth</td><td><?= val($lab['urine_cs_growth']) ?></td>
            </tr>
            <?php if (!empty($lab['urine_cs_sensitivity'])): ?>
            <tr>
                <td>Urine C/S — Sensitivity</td><td colspan="3"><?= val($lab['urine_cs_sensitivity']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($lab['others'])): ?>
            <tr>
                <td>Others</td><td colspan="3"><?= nl2br(val($lab['others'])) ?></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ============ 13. IMAGING / REPORTS ============ -->
<?php if (!empty($imgRows)): ?>
<div class="section">
    <div class="section-title">13. Imaging / Reports / Documents</div>
    <?php foreach ($imgRows as $ir): ?>
        <div style="border:1px solid #e2e8f0; border-radius:6px; padding:8px 10px; margin-bottom:6px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap">
                <div style="flex:1; min-width:240px">
                    <strong><?= val($ir['investigation_name'], 'Investigation') ?></strong>
                    <span class="text-muted" style="font-size:11px">
                        — <?= $ir['investigation_date'] ? date('d M Y', strtotime($ir['investigation_date'])) : '' ?>
                    </span>
                    <?php if (!empty($ir['report_text'])): ?>
                        <div style="margin-top:4px; font-size:12px; color:#334155; white-space:pre-line"><?= val($ir['report_text']) ?></div>
                    <?php endif; ?>
                </div>
                <div style="text-align:center">
                    <?php
                    $fp = $ir['file_path'] ?? '';
                    $ext = $fp ? strtolower(pathinfo($fp, PATHINFO_EXTENSION)) : '';
                    $url = BASE_URL . UPLOAD_URL . $fp;
                    ?>
                    <?php if (in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
                        <img src="<?= htmlspecialchars($url) ?>" alt="report" class="thumb-img">
                    <?php elseif ($ext === 'pdf'): ?>
                        <span class="thumb-pdf">PDF<br><a href="<?= htmlspecialchars($url) ?>" target="_blank">Open</a></span>
                    <?php else: ?>
                        <span class="text-muted" style="font-size:11px">No file</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ============ 14. OPERATION ============ -->
<?php if ($op): ?>
<div class="section">
    <div class="section-title">14. Operation / Procedure</div>
    <table class="info">
        <tr><td>Operation Name:</td><td><?= val($op['operation_name']) ?></td></tr>
        <tr><td>Date / Time:</td><td><?= val($op['operation_date']) ?> <?= val($op['operation_time']) ?></td></tr>
        <tr><td>Laterality:</td><td><?= val($op['laterality']) ?></td></tr>
        <tr><td>Approach:</td><td><?= val($op['approach']) ?></td></tr>
        <tr><td>Operative Findings:</td><td><?= nl2br(val($op['operative_findings'])) ?></td></tr>
        <tr><td>Procedure Details:</td><td><?= nl2br(val($op['procedure_details'])) ?></td></tr>
    </table>
</div>
<?php endif; ?>

<!-- ============ 15. DISCHARGE SUMMARY ============ -->
<?php if ($ds): ?>
<div class="section">
    <div class="section-title">15. Discharge Summary</div>
    <table class="info">
        <tr><td>Admission Date:</td><td><?= val($ds['admission_date']) ?></td>
            <td>Discharge Date:</td><td><?= val($ds['discharge_date']) ?></td></tr>
    </table>
    <?php if (!empty($ds['diagnosis'])): ?>
        <div style="margin-top:6px"><strong>Diagnosis:</strong> <?= nl2br(val($ds['diagnosis'])) ?></div>
    <?php endif; ?>
    <?php if (!empty($ds['relevant_investigations'])): ?>
        <div><strong>Relevant Investigations:</strong> <?= nl2br(val($ds['relevant_investigations'])) ?></div>
    <?php endif; ?>
    <?php if (!empty($ds['medication'])): ?>
        <div><strong>Medication on Discharge:</strong> <?= nl2br(val($ds['medication'])) ?></div>
    <?php endif; ?>
    <?php if (!empty($ds['advice'])): ?>
        <div><strong>Advice:</strong> <?= nl2br(val($ds['advice'])) ?></div>
    <?php endif; ?>
    <?php if (!empty($ds['followup_date']) || !empty($ds['followup_instructions'])): ?>
        <div><strong>Follow-up:</strong>
            <?= $ds['followup_date'] ? date('d M Y', strtotime($ds['followup_date'])) : '' ?>
            <?= $ds['followup_instructions'] ? ' — ' . nl2br(val($ds['followup_instructions'])) : '' ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ============ 16. FOLLOW-UP ============ -->
<?php if ($fu): ?>
<div class="section">
    <div class="section-title">16. Follow-up</div>
    <table class="info">
        <tr><td>Visit Type:</td><td><?= val($fu['visit_type']) ?></td>
            <td>Date:</td><td><?= val($fu['followup_date']) ?></td></tr>
        <tr><td>Scoring:</td><td><?= val($fu['scoring']) ?></td>
            <td>File:</td><td>
                <?php if (!empty($fu['file_path'])): ?>
                    <a href="<?= BASE_URL . UPLOAD_URL . $fu['file_path'] ?>" target="_blank">View attachment</a>
                <?php else: ?>—<?php endif; ?>
            </td></tr>
        <tr><td>Notes:</td><td colspan="3"><?= nl2br(val($fu['notes'])) ?></td></tr>
    </table>
</div>
<?php endif; ?>

<!-- ============ SIGNATURE ============ -->
<div class="signature">
    <div class="line">Doctor's Signature</div>
</div>

<div class="footer">
    Generated on <?= date('d M Y, H:i') ?> — Urology Patient Registry
</div>

<!-- ============ PRINT BUTTON ============ -->
<div class="text-center mt-4 no-print">
    <button onclick="window.print()" class="btn btn-primary px-4">
        <i class="fas fa-print me-1"></i> Print
    </button>
    <a href="../patients/view.php?id=<?= $id ?>" class="btn btn-secondary px-4 ms-2">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

</body>
</html>