<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/excel_helper.php';

// ---------- Date range ----------
$from = !empty($_GET['from']) ? $_GET['from'] : null;
$to   = !empty($_GET['to'])   ? $_GET['to']   : null;

$where = [];
if ($from) $where[] = "DATE(created_at) >= '" . $conn->real_escape_string($from) . "'";
if ($to)   $where[] = "DATE(created_at) <= '" . $conn->real_escape_string($to) . "'";
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$rows = $conn->query("SELECT * FROM patients $whereSQL ORDER BY id ASC");

// ---------- Build sheet ----------
$data = [];

// Title block
$data[] = ['Urology Patient Registry — Basic Patient Data'];
$data[] = ['Date Range: ' . ($from ?: 'Beginning') . ' to ' . ($to ?: 'Today')];
$data[] = ['Generated: ' . date('d M Y, H:i')];
$data[] = [];

// Headers
$data[] = [
    '#', 'URO ID', 'Name', 'Age', 'Sex', 'DOB', 'Blood',
    'Guardian', 'Occupation', 'Mobile', 'NID', 'Hospital Reg No',
    'Unit', 'Ward', 'Bed', 'Mode of Admission',
    'First Admission', 'Current Visit', 'Discharge Date',
    'Address', 'Created'
];

// Data rows
$i = 1;
while ($r = $rows->fetch_assoc()) {
    $data[] = [
        $i++,
        excelSafe($r['uro_id']),
        excelSafe($r['name']),
        (int)$r['age'],
        excelSafe($r['sex']),
        excelVal($r['dob']),
        excelVal($r['blood_group']),
        excelVal($r['guardian_name']),
        excelVal($r['occupation']),
        excelVal($r['mobile']),
        excelVal($r['nid']),
        excelVal($r['hospital_reg_no']),
        excelVal($r['unit']),
        excelVal($r['ward']),
        excelVal($r['bed']),
        excelVal($r['mode_of_admission']),
        excelVal($r['first_admission_date']),
        excelVal($r['current_visit_date']),
        excelVal($r['discharge_date']),
        excelVal($r['address']),
        excelVal($r['created_at']),
    ];
}

// ---------- Download ----------
$filename = 'Basic_Patients_' . date('Y-m-d_H-i');
if ($from || $to) {
    $filename .= '_' . ($from ?: 'start') . '_to_' . ($to ?: 'now');
}

$sheets = ['Patients' => $data];
downloadXlsx($sheets, $filename);
?>