<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$patient_id = intval($_GET['patient_id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
if (!$p) die("Patient not found");
$pageTitle = 'Menstrual & Obstetric History';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $values = [
        $patient_id,
        $nz($_POST['menstrual_flow'] ?? ''),
        $nz($_POST['menstrual_cycle'] ?? ''),
        !empty($_POST['lmp']) ? $_POST['lmp'] : null,
        $nz($_POST['lmp_free'] ?? ''),
        $nz($_POST['para'] ?? ''),
        $nz($_POST['gravidity'] ?? ''),
        $_SESSION['user_id'],
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("INSERT INTO menstrual_history 
        (patient_id, menstrual_flow, menstrual_cycle, lmp, lmp_free, para, gravidity, created_by) 
        VALUES (?,?,?,?,?,?,?,?)");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: menstrual_history.php?patient_id=$patient_id&saved=1"); exit;
}
$records = $conn->query("SELECT * FROM menstrual_history WHERE patient_id=$patient_id ORDER BY id DESC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-female text-primary me-2"></i>Menstrual & Obstetric History</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>) — Female Patients</small>
    </div>
    <a href="../patients/view.php?id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Menstrual history saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Menstrual / Obstetric Details</div>
        <div class="card-body row g-3">
            <div class="col-md-6"><label class="form-label"><i class="fas fa-tint text-primary"></i> Menstrual Flow</label><input name="menstrual_flow" class="form-control"></div>
            <div class="col-md-6"><label class="form-label"><i class="fas fa-sync text-primary"></i> Menstrual Cycle</label><input name="menstrual_cycle" class="form-control"></div>
            <div class="col-md-4"><label class="form-label"><i class="fas fa-calendar-alt text-primary"></i> LMP (Date)</label><input type="date" name="lmp" class="form-control"></div>
            <div class="col-md-8"><label class="form-label"><i class="fas fa-pen text-primary"></i> LMP (Free text if uncertain)</label><input name="lmp_free" class="form-control"></div>
            <div class="col-md-6"><label class="form-label"><i class="fas fa-baby text-primary"></i> Para</label><input name="para" class="form-control"></div>
            <div class="col-md-6"><label class="form-label"><i class="fas fa-baby-carriage text-primary"></i> Gravidity</label><input name="gravidity" class="form-control"></div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Details</button>
        </div>
    </div>
</form>

<!-- ================= SAVED RECORDS — FULL DETAIL ================= -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2 text-primary"></i>Saved Records (<?= $records->num_rows ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle full-rec-table">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Menstrual Flow</th>
                    <th>Menstrual Cycle</th>
                    <th>LMP (Date)</th>
                    <th>LMP (Free Text)</th>
                    <th>Para</th>
                    <th>Gravidity</th>
                    <th>Date</th>
                    <th style="width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($r['menstrual_flow'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['menstrual_cycle'] ?: '—') ?></td>
                    <td><?= $r['lmp'] ? date('d-m-Y', strtotime($r['lmp'])) : '—' ?></td>
                    <td><?= htmlspecialchars($r['lmp_free'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['para'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['gravidity'] ?: '—') ?></td>
                    <td><?= date('d-m-Y', strtotime($r['created_at'])) ?></td>
                    <?= rowActions('menstrual_history', $r['id']) ?>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="9" class="text-center text-muted py-3">No records yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<style>
.full-rec-table { font-size: 12.5px; }
.full-rec-table th { white-space: nowrap; font-size: 11.5px; background: #f1f5f9; }
.full-rec-table td { vertical-align: top; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>