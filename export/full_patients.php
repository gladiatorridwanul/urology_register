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

$patients = $conn->query("SELECT p.* FROM patients p $whereSQL ORDER BY p.id ASC");

// Collect patient IDs for scoped module exports (optional)
$patientIds = [];
$patientMap = [];
while ($p = $patients->fetch_assoc()) {
    $patientIds[] = (int)$p['id'];
    $patientMap[(int)$p['id']] = $p;
}
// Reset pointer by re-querying
$patients = $conn->query("SELECT p.* FROM patients p $whereSQL ORDER BY p.id ASC");

$idList = empty($patientIds) ? '0' : implode(',', $patientIds);

$titleBlock = [
    ['Urology Patient Registry — Full Patient Data'],
    ['Date Range: ' . ($from ?: 'Beginning') . ' to ' . ($to ?: 'Today')],
    ['Generated: ' . date('d M Y, H:i')],
    ['Scope: ' . count($patientIds) . ' patient(s), all modules'],
    [],
];

$sheets = [];

// ============================================================
// SHEET 1: Patients
// ============================================================
$rows = $titleBlock;
$rows[] = [
    'ID', 'URO ID', 'Name', 'Age', 'Sex', 'DOB', 'Blood', 'Guardian',
    'Occupation', 'Mobile', 'NID', 'Reg No', 'Unit', 'Ward', 'Bed',
    'Admission Mode', 'First Admission', 'Current Visit', 'Discharge Date', 'Address'
];
while ($r = $patients->fetch_assoc()) {
    $rows[] = [
        (int)$r['id'], excelSafe($r['uro_id']), excelSafe($r['name']),
        (int)$r['age'], excelSafe($r['sex']), excelVal($r['dob']),
        excelVal($r['blood_group']), excelVal($r['guardian_name']),
        excelVal($r['occupation']), excelVal($r['mobile']), excelVal($r['nid']),
        excelVal($r['hospital_reg_no']), excelVal($r['unit']),
        excelVal($r['ward']), excelVal($r['bed']),
        excelVal($r['mode_of_admission']), excelVal($r['first_admission_date']),
        excelVal($r['current_visit_date']), excelVal($r['discharge_date']),
        excelVal($r['address']),
    ];
}
$sheets['Patients'] = $rows;

// ============================================================
// HELPER — Build a module sheet
// ============================================================
function buildModuleSheet($conn, $table, $patientIds, $headers, $rowMapper) {
    $rows = [
        [ucfirst(str_replace('_', ' ', $table)) . ' — Module Data'],
        [],
    ];
    $rows[] = $headers;

    if (empty($patientIds)) return $rows;

    $ids = implode(',', array_map('intval', $patientIds));
    $sql = "SELECT t.*, p.uro_id, p.name AS patient_name
            FROM $table t
            LEFT JOIN patients p ON p.id = t.patient_id
            WHERE t.patient_id IN ($ids)
            ORDER BY t.patient_id, t.id";
    $q = $conn->query($sql);
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            $rows[] = $rowMapper($r);
        }
    }
    return $rows;
}

// ============================================================
// SHEET 2: Chief Complaints
// ============================================================
$sheets['Chief Complaints'] = buildModuleSheet(
    $conn, 'chief_complaints', $patientIds,
    ['Patient ID','URO ID','Name','LUTS','Specify','LUTS Dur','Retention','Retention Type','Ret Dur','Pain','Pain Dur','Hematuria','Hem Dur','Fever','Fever Details','Fever Dur','Others','Others Dur','Date'],
    function($r) {
        return [
            (int)$r['patient_id'], excelSafe($r['uro_id']), excelSafe($r['patient_name']),
            excelVal($r['luts']), excelVal($r['luts_specify']), excelVal($r['luts_duration']),
            $r['retention_present'] ? 'Yes' : 'No', excelVal($r['retention']), excelVal($r['retention_duration']),
            excelVal($r['pain']), excelVal($r['pain_duration']),
            excelVal($r['hematuria']), excelVal($r['hematuria_duration']),
            excelVal($r['fever']), excelVal($r['fever_details']), excelVal($r['fever_duration']),
            excelVal($r['others']), excelVal($r['others_duration']),
            excelVal($r['created_at']),
        ];
    }
);

// ============================================================
// SHEET 3: Urological History
// ============================================================
$sheets['Urological History'] = buildModuleSheet(
    $conn, 'urological_history', $patientIds,
    ['Patient ID','URO ID','Name','Trauma','Trauma Details','Instrumentation','Instr Details','Catheterization','Cath Details','Surgery','Surgery Details','Stone Disease','Stone Details','Malignancy','Malig Details','Others','Date'],
    function($r) {
        return [
            (int)$r['patient_id'], excelSafe($r['uro_id']), excelSafe($r['patient_name']),
            excelVal($r['trauma']), excelVal($r['trauma_details']),
            excelVal($r['instrumentation']), excelVal($r['instrumentation_details']),
            excelVal($r['catheterization']), excelVal($r['catheterization_details']),
            excelVal($r['surgery']), excelVal($r['surgery_details']),
            excelVal($r['stone_disease']), excelVal($r['stone_details']),
            excelVal($r['malignancy']), excelVal($r['malignancy_details']),
            excelVal($r['others']), excelVal($r['created_at']),
        ];
    }
);

// ============================================================
// SHEET 4: Comorbidity
// ============================================================
$sheets['Comorbidity'] = buildModuleSheet(
    $conn, 'comorbidity', $patientIds,
    ['Patient ID','URO ID','Name','HTN','DM','IHD','CKD','COPD','Neuro','Neuro Details','Others','Date'],
    function($r) {
        return [
            (int)$r['patient_id'], excelSafe($r['uro_id']), excelSafe($r['patient_name']),
            $r['htn'] ? 'Yes' : 'No', $r['dm'] ? 'Yes' : 'No',
            $r['ihd'] ? 'Yes' : 'No', $r['ckd'] ? 'Yes' : 'No',
            $r['copd'] ? 'Yes' : 'No', $r['neurological'] ? 'Yes' : 'No',
            excelVal($r['neurological_details']),
            excelVal($r['others']), excelVal($r['created_at']),
        ];
    }
);

// ============================================================
// SHEET 5: Drug History
// ============================================================
$sheets['Drug History'] = buildModuleSheet(
    $conn, 'drug_history', $patientIds,
    ['Patient ID','URO ID','Name','Drug History','Date'],
    function($r) {
        return [
            (int)$r['patient_id'], excelSafe($r['uro_id']), excelSafe($r['patient_name']),
            excelVal($r['drug_history']), excelVal($r['created_at']),
        ];
    }
);

// ============================================================
// SHEET 6: Personal History
// ============================================================
$sheets['Personal History'] = buildModuleSheet(
    $conn, 'personal_history', $patientIds,
    ['Patient ID','URO ID','Name','Smoking','Smoking Details','Alcohol','Alcohol Details','Betel','Betel Details','Others','Date'],
    function($r) {
        return [
            (int)$r['patient_id'], excelSafe($r['uro_id']), excelSafe($r['patient_name']),
            excelVal($r['smoking']), excelVal($r['smoking_details']),
            excelVal($r['alcohol']), excelVal($r['alcohol_details']),
            excelVal($r['betel']), excelVal($r['betel_details']),
            excelVal($r['others']), excelVal($r['created_at']),
        ];
    }
);

// ============================================================
// SHEET 7: Family History
// ============================================================
$sheets['Family History'] = buildModuleSheet(
    $conn, 'family_history', $patientIds,
    ['Patient ID','URO ID','Name','Family History','Date'],
    function($r) {
        return [
            (int)$r['patient_id'], excelSafe($r['uro_id']), excelSafe($r['patient_name']),
            excelVal($r['family_history']), excelVal($r['created_at']),
        ];
    }
);

// ============================================================
// SHEET 8: Menstrual History
// ============================================================
$sheets['Menstrual History'] = buildModuleSheet(
    $conn, 'menstrual_history', $patientIds,
    ['Patient ID','URO ID','Name','Flow','Cycle','LMP','LMP Free','Para','Gravidity','Date'],
    function($r) {
        return [
            (int)$r['patient_id'], excelSafe($r['uro_id']), excelSafe($r['patient_name']),
            excelVal($r['menstrual_flow']), excelVal($r['menstrual_cycle']),
            excelVal($r['lmp']), excelVal($r['lmp_free']),
            excelVal($r['para']), excelVal($r['gravidity']),
            excelVal($r['created_at']),
        ];
    }
);

// ============================================================
// SHEET 9: General Exam
// ============================================================
$sheets['General Exam'] = buildModuleSheet(
    $conn, 'general_exam', $patientIds,
    ['Patient ID','URO ID','Name','Anaemia','Anaemia Details','Jaundice','Jaundice Details','Edema','Edema Details','Pulse','BP','Temp','Lymph Node','Others','Date'],
    function($r) {
        return [
            (int)$r['patient_id'], excelSafe($r['uro_id']), excelSafe($r['patient_name']),
            excelVal($r['anaemia']), excelVal($r['anaemia_details']),
            excelVal($r['jaundice']), excelVal($r['jaundice_details']),
            excelVal($r['edema']), excelVal($r['edema_details']),
            excelVal($r['pulse']), excelVal($r['bp']), excelVal($r['temperature']),
            excelVal($r['lymph_node']), excelVal($r['others']),
            excelVal($r['created_at']),
        ];
    }
);

// ============================================================
// SHEET 10: GU Exam
// ============================================================
$sheets['GU Exam'] = buildModuleSheet(
    $conn, 'genitourinary_exam', $patientIds,
    ['Patient ID','URO ID','Name','Renal Angle','Kidney','Kidney Details','Bladder Full','Suprapubic Tenderness','Hernial Orifice','Meatus','Meatus Details','Testis','Testis Details','Scrotum','Scrotum Details','Penis','Penis Details','DRE Prostate','Anal Tone','Bulbocavernosus','Others','Date'],
    function($r) {
        return [
            (int)$r['patient_id'], excelSafe($r['uro_id']), excelSafe($r['patient_name']),
            excelVal($r['renal_angle']), excelVal($r['kidney']), excelVal($r['kidney_details']),
            excelVal($r['bladder_fullness']), excelVal($r['suprapubic_tenderness']),
            excelVal($r['hernial_orifice']), excelVal($r['meatus']), excelVal($r['meatus_details']),
            excelVal($r['testis']), excelVal($r['testis_details']),
            excelVal($r['scrotum']), excelVal($r['scrotum_details']),
            excelVal($r['penis']), excelVal($r['penis_details']),
            excelVal($r['dre_prostate']), excelVal($r['anal_tone']), excelVal($r['bulbocavernosus']),
            excelVal($r['others']), excelVal($r['created_at']),
        ];
    }
);

// ============================================================
// SHEET 11: Other System Exam
// ============================================================
$sheets['Other System Exam'] = buildModuleSheet(
    $conn, 'other_system_exam', $patientIds,
    ['Patient ID','URO ID','Name','Other System','Date'],
    function($r) {
        return [
            (int)$r['patient_id'], excelSafe($r['uro_id']), excelSafe($r['patient_name']),
            excelVal($r['other_system']), excelVal($r['created_at']),
        ];
    }
);

// ============================================================
// SHEET 12: Diagnosis
// ============================================================
$sheets['Diagnosis'] = buildModuleSheet(
    $conn, 'diagnosis', $patientIds,
    ['Patient ID','URO ID','Name','Renal Stone','Ureteric Stone','Ureteric Location','Bladder Stone','BPH','Ca Prostate','Renal Mass','UTUC','Bladder Mass','Stricture Urethra','Stricture Details','Pelvic Fracture','Hypospadias','Hypospadias Details','PUJ Obstruction','Testicular Tumor','Ca Penis','Varicocele','Others','Date'],
    function($r) {
        return [
            (int)$r['patient_id'], excelSafe($r['uro_id']), excelSafe($r['patient_name']),
            excelVal($r['renal_stone']), excelVal($r['ureteric_stone']), excelVal($r['ureteric_location']),
            $r['bladder_stone'] ? 'Yes' : 'No',
            $r['bph'] ? 'Yes' : 'No',
            $r['carcinoma_prostate'] ? 'Yes' : 'No',
            excelVal($r['renal_mass']), excelVal($r['utuc']),
            $r['bladder_mass'] ? 'Yes' : 'No',
            $r['stricture_urethra'] ? 'Yes' : 'No', excelVal($r['stricture_details']),
            $r['pelvic_fracture'] ? 'Yes' : 'No',
            $r['hypospadias'] ? 'Yes' : 'No', excelVal($r['hypospadias_details']),
            excelVal($r['puj_obstruction']), excelVal($r['testicular_tumor']),
            $r['carcinoma_penis'] ? 'Yes' : 'No',
            excelVal($r['varicocele']), excelVal($r['others']),
            excelVal($r['created_at']),
        ];
    }
);

// ============================================================
// SHEET 13: Lab Investigations
// ============================================================
$sheets['Lab Investigations'] = buildModuleSheet(
    $conn, 'lab_investigations', $patientIds,
    ['Patient ID','URO ID','Name','Hb','WBC','Platelet','ESR','Creatinine','Electrolytes','PSA','Urine RBC','Urine Pus','RBS','Urine C/S Growth','Urine C/S Sensitivity','Others','Date'],
    function($r) {
        return [
            (int)$r['patient_id'], excelSafe($r['uro_id']), excelSafe($r['patient_name']),
            excelVal($r['hemoglobin']), excelVal($r['wbc']), excelVal($r['platelet']), excelVal($r['esr']),
            excelVal($r['creatinine']), excelVal($r['electrolytes']), excelVal($r['psa']),
            excelVal($r['urine_rbc']), excelVal($r['urine_pus']), excelVal($r['rbs']),
            excelVal($r['urine_cs_growth']), excelVal($r['urine_cs_sensitivity']),
            excelVal($r['others']), excelVal($r['created_at']),
        ];
    }
);

// ============================================================
// SHEET 14: Imaging Reports
// ============================================================
$sheets['Imaging Reports'] = buildModuleSheet(
    $conn, 'imaging_reports', $patientIds,
    ['Patient ID','URO ID','Name','Investigation','Date','Report Text','File','File Type','Uploaded'],
    function($r) {
        return [
            (int)$r['patient_id'], excelSafe($r['uro_id']), excelSafe($r['patient_name']),
            excelVal($r['investigation_name']), excelVal($r['investigation_date']),
            excelVal($r['report_text']),
            excelVal($r['file_path']), excelVal($r['file_type']),
            excelVal($r['created_at']),
        ];
    }
);

// ============================================================
// SHEET 15: Operations
// ============================================================
$sheets['Operations'] = buildModuleSheet(
    $conn, 'operations', $patientIds,
    ['Patient ID','URO ID','Name','Date','Time','Duration','Laterality','Approach','Operation Type','Sub-Type','Specify','Findings','Procedure','Findings File','Procedure File','Created'],
    function($r) {
        return [
            (int)$r['patient_id'], excelSafe($r['uro_id']), excelSafe($r['patient_name']),
            excelVal($r['operation_date']), excelVal($r['operation_time']),
            excelVal($r['op_duration']),
            excelVal($r['laterality']), excelVal($r['approach']),
            excelVal($r['op_type']), excelVal($r['op_subtype']), excelVal($r['op_subtype_other']),
            excelVal($r['operative_findings']), excelVal($r['procedure_details']),
            excelVal($r['findings_file']), excelVal($r['procedure_file']),
            excelVal($r['created_at']),
        ];
    }
);

// ============================================================
// SHEET 16: Discharge Summary
// ============================================================
$sheets['Discharge Summary'] = buildModuleSheet(
    $conn, 'discharge_summary', $patientIds,
    ['Patient ID','URO ID','Name','Admission','Discharge','Diagnosis','Investigations','Op Date','Op Time','Op Name','Laterality','Findings','Procedure','Medication','Advice','Follow-up Date','Follow-up Instructions','Date'],
    function($r) {
        return [
            (int)$r['patient_id'], excelSafe($r['uro_id']), excelSafe($r['patient_name']),
            excelVal($r['admission_date']), excelVal($r['discharge_date']),
            excelVal($r['diagnosis']), excelVal($r['relevant_investigations']),
            excelVal($r['operation_date']), excelVal($r['operation_time']),
            excelVal($r['operation_name']), excelVal($r['operation_laterality']),
            excelVal($r['operative_findings']), excelVal($r['procedure_details']),
            excelVal($r['medication']), excelVal($r['advice']),
            excelVal($r['followup_date']), excelVal($r['followup_instructions']),
            excelVal($r['created_at']),
        ];
    }
);

// ============================================================
// SHEET 17: Follow-up
// ============================================================
$sheets['Follow-up'] = buildModuleSheet(
    $conn, 'followup', $patientIds,
    ['Patient ID','URO ID','Name','Date','Visit Type','Chief Complain','Exam Finding','Investigation','Inv File','Management Plan','Scoring','Notes','Created'],
    function($r) {
        return [
            (int)$r['patient_id'], excelSafe($r['uro_id']), excelSafe($r['patient_name']),
            excelVal($r['followup_date']), excelVal($r['visit_type']),
            excelVal($r['chief_complain']), excelVal($r['exam_finding']),
            excelVal($r['investigation_finding']), excelVal($r['investigation_file']),
            excelVal($r['management_plan']), excelVal($r['scoring']), excelVal($r['notes']),
            excelVal($r['created_at']),
        ];
    }
);

// ---------- Download ----------
$filename = 'Full_Patients_' . date('Y-m-d_H-i');
if ($from || $to) {
    $filename .= '_' . ($from ?: 'start') . '_to_' . ($to ?: 'now');
}

downloadXlsx($sheets, $filename);
?>