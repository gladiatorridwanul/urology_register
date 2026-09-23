<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'wards') {
    $unit_id = intval($_GET['unit_id'] ?? 0);
    $rows = [];
    if ($unit_id) {
        $q = $conn->query("SELECT id, name FROM wards WHERE unit_id=$unit_id AND status=1 ORDER BY name");
        while ($r = $q->fetch_assoc()) $rows[] = $r;
    }
    echo json_encode($rows);
    exit;
}

if ($action === 'beds') {
    $ward_id = intval($_GET['ward_id'] ?? 0);
    $rows = [];
    if ($ward_id) {
        $q = $conn->query("SELECT id, name FROM beds WHERE ward_id=$ward_id AND status=1 ORDER BY name");
        while ($r = $q->fetch_assoc()) $rows[] = $r;
    }
    echo json_encode($rows);
    exit;
}

// NEW: operation subtypes by type
if ($action === 'op_subtypes') {
    $type_id = intval($_GET['type_id'] ?? 0);
    $rows = [];
    if ($type_id) {
        $q = $conn->query("SELECT id, name FROM op_subtypes WHERE type_id=$type_id AND status=1 ORDER BY name");
        while ($r = $q->fetch_assoc()) $rows[] = $r;
    }
    echo json_encode($rows);
    exit;
}

echo json_encode([]);
?>