<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT patient_id, file_path FROM imaging_reports WHERE id=$id")->fetch_assoc();
if ($r) {
    $patient_id = $r['patient_id'];
    if ($r['file_path'] && file_exists(UPLOAD_DIR . $r['file_path'])) {
        @unlink(UPLOAD_DIR . $r['file_path']);
    }
    $conn->query("DELETE FROM imaging_reports WHERE id=$id");
    header("Location: imaging.php?patient_id=$patient_id&saved=1"); exit;
}
header("Location: ../patients/index.php"); exit;