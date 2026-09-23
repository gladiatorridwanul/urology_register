<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT * FROM menstrual_history WHERE id=$id")->fetch_assoc();
if (!$r) die("Record not found");
$patient_id = $r['patient_id'];
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
$pageTitle = 'Edit Menstrual History';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $values = [
        $nz($_POST['menstrual_flow'] ?? ''),
        $nz($_POST['menstrual_cycle'] ?? ''),
        !empty($_POST['lmp']) ? $_POST['lmp'] : null,
        $nz($_POST['lmp_free'] ?? ''),
        $nz($_POST['para'] ?? ''),
        $nz($_POST['gravidity'] ?? ''),
        $id,
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("UPDATE menstrual_history SET 
        menstrual_flow=?, menstrual_cycle=?, lmp=?, lmp_free=?, para=?, gravidity=? 
        WHERE id=?");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: menstrual_history.php?patient_id=$patient_id&saved=1"); exit;
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Edit Menstrual History</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="menstrual_history.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<form method="POST">
    <div class="card">
        <div class="card-body row g-3">

            <div class="col-md-6">
                <label class="form-label">Menstrual Flow</label>
                <input name="menstrual_flow" class="form-control" value="<?= htmlspecialchars($r['menstrual_flow']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Menstrual Cycle</label>
                <input name="menstrual_cycle" class="form-control" value="<?= htmlspecialchars($r['menstrual_cycle']) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">LMP (Date)</label>
                <input type="date" name="lmp" class="form-control" value="<?= $r['lmp'] ?>">
            </div>
            <div class="col-md-8">
                <label class="form-label">LMP (Free text if uncertain)</label>
                <input name="lmp_free" class="form-control" value="<?= htmlspecialchars($r['lmp_free']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Para</label>
                <input name="para" class="form-control" value="<?= htmlspecialchars($r['para']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Gravidity</label>
                <input name="gravidity" class="form-control" value="<?= htmlspecialchars($r['gravidity']) ?>">
            </div>

        </div>
        <div class="card-footer text-end">
            <a href="menstrual_history.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary me-2">Cancel</a>
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update</button>
        </div>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>