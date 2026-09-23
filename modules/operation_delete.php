<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT patient_id, findings_file, procedure_file FROM operations WHERE id=$id")->fetch_assoc();
if ($r) {
    $patient_id = $r['patient_id'];
    if (!empty($r['findings_file']) && file_exists(UPLOAD_DIR . $r['findings_file'])) @unlink(UPLOAD_DIR . $r['findings_file']);
    if (!empty($r['procedure_file']) && file_exists(UPLOAD_DIR . $r['procedure_file'])) @unlink(UPLOAD_DIR . $r['procedure_file']);
    $conn->query("DELETE FROM operations WHERE id=$id");
    header("Location: operation.php?patient_id=$patient_id&saved=1"); exit;
}
header("Location: ../patients/index.php"); exit;
?>