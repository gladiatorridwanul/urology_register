<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT patient_id FROM discharge_summary WHERE id=$id")->fetch_assoc();
if ($r) {
    $patient_id = $r['patient_id'];
    $conn->query("DELETE FROM discharge_summary WHERE id=$id");
    header("Location: discharge.php?patient_id=$patient_id&saved=1"); exit;
}
header("Location: ../patients/index.php"); exit;