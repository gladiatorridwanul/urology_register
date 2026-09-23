<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT patient_id FROM chief_complaints WHERE id=$id")->fetch_assoc();
if ($r) {
    $patient_id = $r['patient_id'];
    $conn->query("DELETE FROM chief_complaints WHERE id=$id");
    header("Location: chief_complaints.php?patient_id=$patient_id"); exit;
}
header("Location: ../patients/index.php"); exit;