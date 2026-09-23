<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$patient_id = intval($_GET['patient_id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
if (!$p) die("Patient not found");
$pageTitle = 'Family History';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $values = [
        $patient_id,
        $nz($_POST['family_history'] ?? ''),
        $_SESSION['user_id'],
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("INSERT INTO family_history (patient_id, family_history, created_by) VALUES (?,?,?)");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: family_history.php?patient_id=$patient_id&saved=1"); exit;
}
$records = $conn->query("SELECT * FROM family_history WHERE patient_id=$patient_id ORDER BY id DESC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-sitemap text-primary me-2"></i>Family History</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="../patients/view.php?id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Family history saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Family History</div>
        <div class="card-body">
            <label class="form-label"><i class="fas fa-notes-medical text-primary"></i> Family History</label>
            <textarea name="family_history" class="form-control" rows="5"
                      placeholder="Any relevant family history (e.g. urological cancer, stone disease, congenital anomalies, diabetes, hypertension)"
                      required></textarea>
            <small class="text-muted">Unrestricted text — include relationship, condition, age at diagnosis if known.</small>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Family History</button>
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
                    <th style="width:50px">#</th>
                    <th>Family History (Full)</th>
                    <th style="width:150px">Created</th>
                    <th style="width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= nl2br(htmlspecialchars($r['family_history'] ?: '—')) ?></td>
                    <td><?= date('d-m-Y H:i', strtotime($r['created_at'])) ?></td>
                    <?= rowActions('family_history', $r['id']) ?>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">No records yet.</td></tr>
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