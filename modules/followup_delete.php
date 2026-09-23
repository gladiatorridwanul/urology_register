<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT patient_id, investigation_file, file_path FROM followup WHERE id=$id")->fetch_assoc();
if ($r) {
    // Clean up uploaded files
    if (!empty($r['investigation_file']) && file_exists(UPLOAD_DIR . $r['investigation_file'])) {
        @unlink(UPLOAD_DIR . $r['investigation_file']);
    }
    if (!empty($r['file_path']) && file_exists(UPLOAD_DIR . $r['file_path'])) {
        @unlink(UPLOAD_DIR . $r['file_path']);
    }

    $patient_id = $r['patient_id'];
    $conn->query("DELETE FROM followup WHERE id=$id");
    header("Location: followup.php?patient_id=$patient_id&saved=1"); exit;
}
header("Location: ../patients/index.php"); exit;