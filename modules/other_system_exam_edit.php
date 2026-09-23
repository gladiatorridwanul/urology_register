<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT * FROM other_system_exam WHERE id=$id")->fetch_assoc();
if (!$r) die("Record not found");
$patient_id = $r['patient_id'];
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
$pageTitle = 'Edit Other System Exam';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $values = [
        $nz($_POST['other_system'] ?? ''),
        $id,
    ];
    $types = 'si';

    $stmt = $conn->prepare("UPDATE other_system_exam SET other_system=? WHERE id=?");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: other_system_exam.php?patient_id=$patient_id&saved=1"); exit;
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Edit Other System Exam</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="other_system_exam.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<form method="POST">
    <div class="card">
        <div class="card-body">
            <label class="form-label"><i class="fas fa-notes-medical text-primary"></i> Other System Examination</label>
            <textarea name="other_system" class="form-control" rows="6" required><?= htmlspecialchars($r['other_system']) ?></textarea>
        </div>
        <div class="card-footer text-end">
            <a href="other_system_exam.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary me-2">Cancel</a>
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update</button>
        </div>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>