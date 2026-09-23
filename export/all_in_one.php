<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/excel_helper.php';

// ---------- Date range ----------
$from = !empty($_GET['from']) ? $_GET['from'] : null;
$to   = !empty($_GET['to'])   ? $_GET['to']   : null;

$where = [];
if ($from) $where[] = "DATE(p.created_at) >= '" . $conn->real_escape_string($from) . "'";
if ($to)   $where[] = "DATE(p.created_at) <= '" . $conn->real_escape_string($to) . "'";
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ============================================================
// MASTER QUERY — one row per patient, LEFT JOIN every module's LATEST record
// Uses subqueries to grab the latest row of each module per patient.
// ============================================================
$sql = "
    SELECT
        p.*,

        cc.luts, cc.luts_specify, cc.luts_duration, cc.retention_present, cc.retention,
        cc.retention_duration, cc.pain, cc.pain_duration,
        cc.hematuria, cc.hematuria_duration,
        cc.fever, cc.fever_present, cc.fever_details, cc.fever_duration,
        cc.others AS cc_others, cc.others_duration AS cc_others_duration,

        uh.trauma, uh.trauma_details,
        uh.instrumentation, uh.instrumentation_details,
        uh.catheterization, uh.catheterization_details,
        uh.surgery, uh.surgery_details,
        uh.stone_disease, uh.stone_details,
        uh.malignancy, uh.malignancy_details,
        uh.others AS uh_others,

        cmb.htn, cmb.dm, cmb.ihd, cmb.ckd, cmb.copd, cmb.neurological,
        cmb.neurological_details, cmb.others AS cmb_others,

        dh.drug_history,

        ph.smoking, ph.smoking_details,
        ph.alcohol, ph.alcohol_details,
        ph.betel, ph.betel_details, ph.others AS ph_others,

        fh.family_history,

        mh.menstrual_flow, mh.menstrual_cycle, mh.lmp, mh.lmp_free,
        mh.para, mh.gravidity,

        ge.anaemia, ge.anaemia_details, ge.jaundice, ge.jaundice_details,
        ge.edema, ge.edema_details, ge.pulse, ge.bp, ge.temperature,
        ge.lymph_node, ge.others AS ge_others,

        gu.renal_angle, gu.kidney, gu.kidney_details,
        gu.bladder_fullness, gu.suprapubic_tenderness, gu.hernial_orifice,
        gu.meatus, gu.meatus_details,
        gu.testis, gu.testis_details, gu.scrotum, gu.scrotum_details,
        gu.penis, gu.penis_details, gu.dre_prostate, gu.anal_tone,
        gu.bulbocavernosus, gu.others AS gu_others,

        ose.other_system,

        dx.renal_stone, dx.ureteric_stone, dx.ureteric_location, dx.bladder_stone,
        dx.bph, dx.carcinoma_prostate, dx.renal_mass, dx.utuc, dx.bladder_mass,
        dx.stricture_urethra, dx.stricture_details, dx.pelvic_fracture,
        dx.hypospadias, dx.hypospadias_details, dx.puj_obstruction,
        dx.testicular_tumor, dx.carcinoma_penis, dx.varicocele,
        dx.others AS dx_others,

        lab.hemoglobin, lab.wbc, lab.platelet, lab.esr, lab.creatinine,
        lab.electrolytes, lab.psa, lab.urine_rbc, lab.urine_pus, lab.rbs,
        lab.urine_cs_growth, lab.urine_cs_sensitivity, lab.others AS lab_others,

        op.operation_date, op.operation_time, op.op_duration, op.laterality,
        op.approach, op.operation_name, op.op_type, op.op_subtype, op.op_subtype_other,
        op.operative_findings, op.procedure_details,
        op.findings_file, op.procedure_file,

        ds.admission_date AS ds_admission, ds.discharge_date AS ds_discharge,
        ds.diagnosis AS ds_diagnosis, ds.relevant_investigations AS ds_investigations,
        ds.operation_date AS ds_op_date, ds.operation_name AS ds_op_name,
        ds.medication AS ds_medication, ds.advice AS ds_advice,
        ds.followup_date AS ds_fu_date, ds.followup_instructions AS ds_fu_ins,

        fu.followup_date AS fu_date, fu.visit_type AS fu_visit,
        fu.chief_complain AS fu_chief, fu.exam_finding AS fu_exam,
        fu.investigation_finding AS fu_inv, fu.investigation_file AS fu_file,
        fu.management_plan AS fu_plan, fu.scoring AS fu_scoring, fu.notes AS fu_notes,

        (SELECT COUNT(*) FROM imaging_reports WHERE patient_id = p.id) AS img_count

    FROM patients p

    -- Latest Chief Complaints
    LEFT JOIN chief_complaints cc ON cc.id = (
        SELECT id FROM chief_complaints WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )
    -- Latest Urological History
    LEFT JOIN urological_history uh ON uh.id = (
        SELECT id FROM urological_history WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )
    -- Latest Comorbidity
    LEFT JOIN comorbidity cmb ON cmb.id = (
        SELECT id FROM comorbidity WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )
    -- Latest Drug History
    LEFT JOIN drug_history dh ON dh.id = (
        SELECT id FROM drug_history WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )
    -- Latest Personal History
    LEFT JOIN personal_history ph ON ph.id = (
        SELECT id FROM personal_history WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )
    -- Latest Family History
    LEFT JOIN family_history fh ON fh.id = (
        SELECT id FROM family_history WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )
    -- Latest Menstrual History
    LEFT JOIN menstrual_history mh ON mh.id = (
        SELECT id FROM menstrual_history WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )
    -- Latest General Exam
    LEFT JOIN general_exam ge ON ge.id = (
        SELECT id FROM general_exam WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )
    -- Latest GU Exam
    LEFT JOIN genitourinary_exam gu ON gu.id = (
        SELECT id FROM genitourinary_exam WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )
    -- Latest Other System Exam
    LEFT JOIN other_system_exam ose ON ose.id = (
        SELECT id FROM other_system_exam WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )
    -- Latest Diagnosis
    LEFT JOIN diagnosis dx ON dx.id = (
        SELECT id FROM diagnosis WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )
    -- Latest Lab
    LEFT JOIN lab_investigations lab ON lab.id = (
        SELECT id FROM lab_investigations WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )
    -- Latest Operation
    LEFT JOIN operations op ON op.id = (
        SELECT id FROM operations WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )
    -- Latest Discharge Summary
    LEFT JOIN discharge_summary ds ON ds.id = (
        SELECT id FROM discharge_summary WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )
    -- Latest Follow-up
    LEFT JOIN followup fu ON fu.id = (
        SELECT id FROM followup WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )

    $whereSQL
    ORDER BY p.id ASC
";

$result = $conn->query($sql);

// ============================================================
// BUILD THE WIDE SHEET
// ============================================================
$data = [];

// Title
$data[] = ['Urology Patient Registry — Complete Data (All Modules, One Row per Patient)'];
$data[] = ['Date Range: ' . ($from ?: 'Beginning') . ' to ' . ($to ?: 'Today')];
$data[] = ['Generated: ' . date('d M Y, H:i')];
$data[] = ['Note: Each row = one patient with latest entry from every module.'];
$data[] = [];

// SECTION 1 headers (Patient Profile)
$sectionRow1 = [];
$headerRow1  = [];
$sectionRow1[] = 'PATIENT PROFILE';
$headerRow1[]  = 'ID';
$headerRow1[]  = 'URO ID';
$headerRow1[]  = 'Name';
$headerRow1[]  = 'Age';
$headerRow1[]  = 'Sex';
$headerRow1[]  = 'DOB';
$headerRow1[]  = 'Blood';
$headerRow1[]  = 'Guardian';
$headerRow1[]  = 'Occupation';
$headerRow1[]  = 'Mobile';
$headerRow1[]  = 'NID';
$headerRow1[]  = 'Reg No';
$headerRow1[]  = 'Unit';
$headerRow1[]  = 'Ward';
$headerRow1[]  = 'Bed';
$headerRow1[]  = 'Mode';
$headerRow1[]  = 'First Admission';
$headerRow1[]  = 'Current Visit';
$headerRow1[]  = 'Discharge Date';
$headerRow1[]  = 'Address';

// SECTION 2: Chief Complaints
for ($i = 1; $i < 20; $i++) $sectionRow1[] = '';
$sectionRow1[] = 'CHIEF COMPLAINTS';
for ($i = 1; $i < 18; $i++) $sectionRow1[] = '';
$headerRow1[] = 'LUTS';
$headerRow1[] = 'LUTS Specify';
$headerRow1[] = 'LUTS Duration';
$headerRow1[] = 'Retention Present';
$headerRow1[] = 'Retention Type';
$headerRow1[] = 'Retention Duration';
$headerRow1[] = 'Pain';
$headerRow1[] = 'Pain Duration';
$headerRow1[] = 'Hematuria';
$headerRow1[] = 'Hematuria Duration';
$headerRow1[] = 'Fever';
$headerRow1[] = 'Fever Present';
$headerRow1[] = 'Fever Details';
$headerRow1[] = 'Fever Duration';
$headerRow1[] = 'CC Others';
$headerRow1[] = 'CC Others Duration';

// SECTION 3: Urological History
$sectionRow1[] = 'UROLOGICAL HISTORY';
for ($i = 1; $i < 16; $i++) $sectionRow1[] = '';
$headerRow1[] = 'Trauma';
$headerRow1[] = 'Trauma Details';
$headerRow1[] = 'Instrumentation';
$headerRow1[] = 'Instrumentation Details';
$headerRow1[] = 'Catheterization';
$headerRow1[] = 'Catheterization Details';
$headerRow1[] = 'Surgery';
$headerRow1[] = 'Surgery Details';
$headerRow1[] = 'Stone Disease';
$headerRow1[] = 'Stone Details';
$headerRow1[] = 'Malignancy';
$headerRow1[] = 'Malignancy Details';
$headerRow1[] = 'UH Others';

// SECTION 4: Comorbidity
$sectionRow1[] = 'COMORBIDITY';
for ($i = 1; $i < 10; $i++) $sectionRow1[] = '';
$headerRow1[] = 'HTN';
$headerRow1[] = 'DM';
$headerRow1[] = 'IHD';
$headerRow1[] = 'CKD';
$headerRow1[] = 'COPD';
$headerRow1[] = 'Neurological';
$headerRow1[] = 'Neuro Details';
$headerRow1[] = 'Cmb Others';

// SECTION 5: Drug History
$sectionRow1[] = 'DRUG HISTORY';
$headerRow1[]  = 'Drug History';

// SECTION 6: Personal History
$sectionRow1[] = 'PERSONAL HISTORY';
for ($i = 1; $i < 9; $i++) $sectionRow1[] = '';
$headerRow1[] = 'Smoking';
$headerRow1[] = 'Smoking Details';
$headerRow1[] = 'Alcohol';
$headerRow1[] = 'Alcohol Details';
$headerRow1[] = 'Betel';
$headerRow1[] = 'Betel Details';
$headerRow1[] = 'PH Others';

// SECTION 7: Family History
$sectionRow1[] = 'FAMILY HISTORY';
$headerRow1[]  = 'Family History';

// SECTION 8: Menstrual History
$sectionRow1[] = 'MENSTRUAL HISTORY';
for ($i = 1; $i < 8; $i++) $sectionRow1[] = '';
$headerRow1[] = 'Flow';
$headerRow1[] = 'Cycle';
$headerRow1[] = 'LMP';
$headerRow1[] = 'LMP Free';
$headerRow1[] = 'Para';
$headerRow1[] = 'Gravidity';

// SECTION 9: General Exam
$sectionRow1[] = 'GENERAL EXAM';
for ($i = 1; $i < 14; $i++) $sectionRow1[] = '';
$headerRow1[] = 'Anaemia';
$headerRow1[] = 'Anaemia Details';
$headerRow1[] = 'Jaundice';
$headerRow1[] = 'Jaundice Details';
$headerRow1[] = 'Edema';
$headerRow1[] = 'Edema Details';
$headerRow1[] = 'Pulse';
$headerRow1[] = 'BP';
$headerRow1[] = 'Temperature';
$headerRow1[] = 'Lymph Node';
$headerRow1[] = 'GE Others';

// SECTION 10: GU Exam
$sectionRow1[] = 'GU EXAM';
for ($i = 1; $i < 21; $i++) $sectionRow1[] = '';
$headerRow1[] = 'Renal Angle';
$headerRow1[] = 'Kidney';
$headerRow1[] = 'Kidney Details';
$headerRow1[] = 'Bladder Fullness';
$headerRow1[] = 'Suprapubic Tenderness';
$headerRow1[] = 'Hernial Orifice';
$headerRow1[] = 'Meatus';
$headerRow1[] = 'Meatus Details';
$headerRow1[] = 'Testis';
$headerRow1[] = 'Testis Details';
$headerRow1[] = 'Scrotum';
$headerRow1[] = 'Scrotum Details';
$headerRow1[] = 'Penis';
$headerRow1[] = 'Penis Details';
$headerRow1[] = 'DRE Prostate';
$headerRow1[] = 'Anal Tone';
$headerRow1[] = 'Bulbocavernosus';
$headerRow1[] = 'GU Others';

// SECTION 11: Other System Exam
$sectionRow1[] = 'OTHER SYSTEM EXAM';
$headerRow1[]  = 'Other System';

// SECTION 12: Diagnosis
$sectionRow1[] = 'DIAGNOSIS';
for ($i = 1; $i < 20; $i++) $sectionRow1[] = '';
$headerRow1[] = 'Renal Stone';
$headerRow1[] = 'Ureteric Stone';
$headerRow1[] = 'Ureteric Location';
$headerRow1[] = 'Bladder Stone';
$headerRow1[] = 'BPH';
$headerRow1[] = 'Ca Prostate';
$headerRow1[] = 'Renal Mass';
$headerRow1[] = 'UTUC';
$headerRow1[] = 'Bladder Mass';
$headerRow1[] = 'Stricture Urethra';
$headerRow1[] = 'Stricture Details';
$headerRow1[] = 'Pelvic Fracture';
$headerRow1[] = 'Hypospadias';
$headerRow1[] = 'Hypospadias Details';
$headerRow1[] = 'PUJ Obstruction';
$headerRow1[] = 'Testicular Tumor';
$headerRow1[] = 'Ca Penis';
$headerRow1[] = 'Varicocele';
$headerRow1[] = 'DX Others';

// SECTION 13: Lab
$sectionRow1[] = 'LAB INVESTIGATIONS';
for ($i = 1; $i < 14; $i++) $sectionRow1[] = '';
$headerRow1[] = 'Hb';
$headerRow1[] = 'WBC';
$headerRow1[] = 'Platelet';
$headerRow1[] = 'ESR';
$headerRow1[] = 'Creatinine';
$headerRow1[] = 'Electrolytes';
$headerRow1[] = 'PSA';
$headerRow1[] = 'Urine RBC';
$headerRow1[] = 'Urine Pus';
$headerRow1[] = 'RBS';
$headerRow1[] = 'Urine C/S Growth';
$headerRow1[] = 'Urine C/S Sensitivity';
$headerRow1[] = 'Lab Others';

// SECTION 14: Operation
$sectionRow1[] = 'OPERATION';
for ($i = 1; $i < 16; $i++) $sectionRow1[] = '';
$headerRow1[] = 'Op Date';
$headerRow1[] = 'Op Time';
$headerRow1[] = 'Duration';
$headerRow1[] = 'Laterality';
$headerRow1[] = 'Approach';
$headerRow1[] = 'Operation Name';
$headerRow1[] = 'Op Type';
$headerRow1[] = 'Sub-Type';
$headerRow1[] = 'Sub-Type Other';
$headerRow1[] = 'Findings';
$headerRow1[] = 'Procedure';
$headerRow1[] = 'Findings File';
$headerRow1[] = 'Procedure File';

// SECTION 15: Discharge Summary
$sectionRow1[] = 'DISCHARGE SUMMARY';
for ($i = 1; $i < 12; $i++) $sectionRow1[] = '';
$headerRow1[] = 'DS Admission';
$headerRow1[] = 'DS Discharge';
$headerRow1[] = 'DS Diagnosis';
$headerRow1[] = 'DS Investigations';
$headerRow1[] = 'DS Op Date';
$headerRow1[] = 'DS Op Name';
$headerRow1[] = 'DS Medication';
$headerRow1[] = 'DS Advice';
$headerRow1[] = 'DS FU Date';
$headerRow1[] = 'DS FU Instructions';

// SECTION 16: Follow-up
$sectionRow1[] = 'FOLLOW-UP';
for ($i = 1; $i < 10; $i++) $sectionRow1[] = '';
$headerRow1[] = 'FU Date';
$headerRow1[] = 'FU Visit Type';
$headerRow1[] = 'FU Chief Complaint';
$headerRow1[] = 'FU Exam Finding';
$headerRow1[] = 'FU Investigation';
$headerRow1[] = 'FU File';
$headerRow1[] = 'FU Management Plan';
$headerRow1[] = 'FU Scoring';
$headerRow1[] = 'FU Notes';

// SECTION 17: Imaging
$sectionRow1[] = 'IMAGING';
$headerRow1[]  = 'Imaging Reports Count';

$data[] = $sectionRow1;   // Section label row
$data[] = $headerRow1;    // Column header row

// ---------- DATA ROWS ----------
while ($r = $result->fetch_assoc()) {
    $row = [];

    // Patient
    $row[] = (int)$r['id'];
    $row[] = excelSafe($r['uro_id']);
    $row[] = excelSafe($r['name']);
    $row[] = (int)$r['age'];
    $row[] = excelSafe($r['sex']);
    $row[] = excelVal($r['dob']);
    $row[] = excelVal($r['blood_group']);
    $row[] = excelVal($r['guardian_name']);
    $row[] = excelVal($r['occupation']);
    $row[] = excelVal($r['mobile']);
    $row[] = excelVal($r['nid']);
    $row[] = excelVal($r['hospital_reg_no']);
    $row[] = excelVal($r['unit']);
    $row[] = excelVal($r['ward']);
    $row[] = excelVal($r['bed']);
    $row[] = excelVal($r['mode_of_admission']);
    $row[] = excelVal($r['first_admission_date']);
    $row[] = excelVal($r['current_visit_date']);
    $row[] = excelVal($r['discharge_date']);
    $row[] = excelVal($r['address']);

    // Chief Complaints
    $row[] = excelVal($r['luts']);
    $row[] = excelVal($r['luts_specify']);
    $row[] = excelVal($r['luts_duration']);
    $row[] = $r['retention_present'] ? 'Yes' : 'No';
    $row[] = excelVal($r['retention']);
    $row[] = excelVal($r['retention_duration']);
    $row[] = excelVal($r['pain']);
    $row[] = excelVal($r['pain_duration']);
    $row[] = excelVal($r['hematuria']);
    $row[] = excelVal($r['hematuria_duration']);
    $row[] = excelVal($r['fever']);
    $row[] = $r['fever_present'] ? 'Yes' : 'No';
    $row[] = excelVal($r['fever_details']);
    $row[] = excelVal($r['fever_duration']);
    $row[] = excelVal($r['cc_others']);
    $row[] = excelVal($r['cc_others_duration']);

    // Urological History
    $row[] = excelVal($r['trauma']);
    $row[] = excelVal($r['trauma_details']);
    $row[] = excelVal($r['instrumentation']);
    $row[] = excelVal($r['instrumentation_details']);
    $row[] = excelVal($r['catheterization']);
    $row[] = excelVal($r['catheterization_details']);
    $row[] = excelVal($r['surgery']);
    $row[] = excelVal($r['surgery_details']);
    $row[] = excelVal($r['stone_disease']);
    $row[] = excelVal($r['stone_details']);
    $row[] = excelVal($r['malignancy']);
    $row[] = excelVal($r['malignancy_details']);
    $row[] = excelVal($r['uh_others']);

    // Comorbidity
    $row[] = $r['htn'] ? 'Yes' : 'No';
    $row[] = $r['dm'] ? 'Yes' : 'No';
    $row[] = $r['ihd'] ? 'Yes' : 'No';
    $row[] = $r['ckd'] ? 'Yes' : 'No';
    $row[] = $r['copd'] ? 'Yes' : 'No';
    $row[] = $r['neurological'] ? 'Yes' : 'No';
    $row[] = excelVal($r['neurological_details']);
    $row[] = excelVal($r['cmb_others']);

    // Drug History
    $row[] = excelVal($r['drug_history']);

    // Personal History
    $row[] = excelVal($r['smoking']);
    $row[] = excelVal($r['smoking_details']);
    $row[] = excelVal($r['alcohol']);
    $row[] = excelVal($r['alcohol_details']);
    $row[] = excelVal($r['betel']);
    $row[] = excelVal($r['betel_details']);
    $row[] = excelVal($r['ph_others']);

    // Family History
    $row[] = excelVal($r['family_history']);

    // Menstrual
    $row[] = excelVal($r['menstrual_flow']);
    $row[] = excelVal($r['menstrual_cycle']);
    $row[] = excelVal($r['lmp']);
    $row[] = excelVal($r['lmp_free']);
    $row[] = excelVal($r['para']);
    $row[] = excelVal($r['gravidity']);

    // General Exam
    $row[] = excelVal($r['anaemia']);
    $row[] = excelVal($r['anaemia_details']);
    $row[] = excelVal($r['jaundice']);
    $row[] = excelVal($r['jaundice_details']);
    $row[] = excelVal($r['edema']);
    $row[] = excelVal($r['edema_details']);
    $row[] = excelVal($r['pulse']);
    $row[] = excelVal($r['bp']);
    $row[] = excelVal($r['temperature']);
    $row[] = excelVal($r['lymph_node']);
    $row[] = excelVal($r['ge_others']);

    // GU Exam
    $row[] = excelVal($r['renal_angle']);
    $row[] = excelVal($r['kidney']);
    $row[] = excelVal($r['kidney_details']);
    $row[] = excelVal($r['bladder_fullness']);
    $row[] = excelVal($r['suprapubic_tenderness']);
    $row[] = excelVal($r['hernial_orifice']);
    $row[] = excelVal($r['meatus']);
    $row[] = excelVal($r['meatus_details']);
    $row[] = excelVal($r['testis']);
    $row[] = excelVal($r['testis_details']);
    $row[] = excelVal($r['scrotum']);
    $row[] = excelVal($r['scrotum_details']);
    $row[] = excelVal($r['penis']);
    $row[] = excelVal($r['penis_details']);
    $row[] = excelVal($r['dre_prostate']);
    $row[] = excelVal($r['anal_tone']);
    $row[] = excelVal($r['bulbocavernosus']);
    $row[] = excelVal($r['gu_others']);

    // Other System
    $row[] = excelVal($r['other_system']);

    // Diagnosis
    $row[] = excelVal($r['renal_stone']);
    $row[] = excelVal($r['ureteric_stone']);
    $row[] = excelVal($r['ureteric_location']);
    $row[] = $r['bladder_stone'] ? 'Yes' : 'No';
    $row[] = $r['bph'] ? 'Yes' : 'No';
    $row[] = $r['carcinoma_prostate'] ? 'Yes' : 'No';
    $row[] = excelVal($r['renal_mass']);
    $row[] = excelVal($r['utuc']);
    $row[] = $r['bladder_mass'] ? 'Yes' : 'No';
    $row[] = $r['stricture_urethra'] ? 'Yes' : 'No';
    $row[] = excelVal($r['stricture_details']);
    $row[] = $r['pelvic_fracture'] ? 'Yes' : 'No';
    $row[] = $r['hypospadias'] ? 'Yes' : 'No';
    $row[] = excelVal($r['hypospadias_details']);
    $row[] = excelVal($r['puj_obstruction']);
    $row[] = excelVal($r['testicular_tumor']);
    $row[] = $r['carcinoma_penis'] ? 'Yes' : 'No';
    $row[] = excelVal($r['varicocele']);
    $row[] = excelVal($r['dx_others']);

    // Lab
    $row[] = excelVal($r['hemoglobin']);
    $row[] = excelVal($r['wbc']);
    $row[] = excelVal($r['platelet']);
    $row[] = excelVal($r['esr']);
    $row[] = excelVal($r['creatinine']);
    $row[] = excelVal($r['electrolytes']);
    $row[] = excelVal($r['psa']);
    $row[] = excelVal($r['urine_rbc']);
    $row[] = excelVal($r['urine_pus']);
    $row[] = excelVal($r['rbs']);
    $row[] = excelVal($r['urine_cs_growth']);
    $row[] = excelVal($r['urine_cs_sensitivity']);
    $row[] = excelVal($r['lab_others']);

    // Operation
    $row[] = excelVal($r['operation_date']);
    $row[] = excelVal($r['operation_time']);
    $row[] = excelVal($r['op_duration']);
    $row[] = excelVal($r['laterality']);
    $row[] = excelVal($r['approach']);
    $row[] = excelVal($r['operation_name']);
    $row[] = excelVal($r['op_type']);
    $row[] = excelVal($r['op_subtype']);
    $row[] = excelVal($r['op_subtype_other']);
    $row[] = excelVal($r['operative_findings']);
    $row[] = excelVal($r['procedure_details']);
    $row[] = excelVal($r['findings_file']);
    $row[] = excelVal($r['procedure_file']);

    // Discharge Summary
    $row[] = excelVal($r['ds_admission']);
    $row[] = excelVal($r['ds_discharge']);
    $row[] = excelVal($r['ds_diagnosis']);
    $row[] = excelVal($r['ds_investigations']);
    $row[] = excelVal($r['ds_op_date']);
    $row[] = excelVal($r['ds_op_name']);
    $row[] = excelVal($r['ds_medication']);
    $row[] = excelVal($r['ds_advice']);
    $row[] = excelVal($r['ds_fu_date']);
    $row[] = excelVal($r['ds_fu_ins']);

    // Follow-up
    $row[] = excelVal($r['fu_date']);
    $row[] = excelVal($r['fu_visit']);
    $row[] = excelVal($r['fu_chief']);
    $row[] = excelVal($r['fu_exam']);
    $row[] = excelVal($r['fu_inv']);
    $row[] = excelVal($r['fu_file']);
    $row[] = excelVal($r['fu_plan']);
    $row[] = excelVal($r['fu_scoring']);
    $row[] = excelVal($r['fu_notes']);

    // Imaging count
    $row[] = (int)$r['img_count'];

    $data[] = $row;
}

// ---------- Download ----------
$filename = 'All_in_One_' . date('Y-m-d_H-i');
if ($from || $to) {
    $filename .= '_' . ($from ?: 'start') . '_to_' . ($to ?: 'now');
}

$sheets = ['All-in-One Data' => $data];
downloadXlsx($sheets, $filename);
?>