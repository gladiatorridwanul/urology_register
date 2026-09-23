<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$patient_id = intval($_GET['patient_id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
if (!$p) die("Patient not found");
$pageTitle = 'Personal History';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $smoking = $_POST['smoking'] ?? 'Never';
    $alcohol = $_POST['alcohol'] ?? 'No';
    $betel   = $_POST['betel']   ?? 'No';

    $smoking_details = ($smoking !== 'Never') ? $nz($_POST['smoking_details'] ?? '') : null;
    $alcohol_details = ($alcohol === 'Yes')    ? $nz($_POST['alcohol_details'] ?? '') : null;
    $betel_details   = ($betel   === 'Yes')    ? $nz($_POST['betel_details'] ?? '')   : null;

    $others = $nz($_POST['others'] ?? '');

    $values = [
        $patient_id, $smoking, $smoking_details,
        $alcohol, $alcohol_details,
        $betel, $betel_details,
        $others, $_SESSION['user_id'],
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("INSERT INTO personal_history 
        (patient_id, smoking, smoking_details, alcohol, alcohol_details, betel, betel_details, others, created_by) 
        VALUES (?,?,?,?,?,?,?,?,?)");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: personal_history.php?patient_id=$patient_id&saved=1");
    exit;
}

$records = $conn->query("SELECT * FROM personal_history WHERE patient_id=$patient_id ORDER BY id DESC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-user-tag text-primary me-2"></i>Personal History</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="../patients/view.php?id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Personal history saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Personal History</div>
        <div class="card-body">
            <div class="ph-block">
                <div class="ph-row">
                    <div class="ph-label"><i class="fas fa-smoking text-primary"></i> Smoking<small class="text-muted d-block">Dropdown + optional details</small></div>
                    <div class="ph-input">
                        <select name="smoking" class="form-select form-select-sm" onchange="document.getElementById('smoking_details_wrap').style.display=this.value!=='Never'?'block':'none'">
                            <option>Never</option><option>Current</option><option>Former</option>
                        </select>
                        <div id="smoking_details_wrap" class="mt-2" style="display:none">
                            <input name="smoking_details" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
            </div>
            <div class="ph-block">
                <div class="ph-row">
                    <div class="ph-label"><i class="fas fa-wine-glass text-primary"></i> Alcohol<small class="text-muted d-block">Dropdown + optional details</small></div>
                    <div class="ph-input">
                        <select name="alcohol" class="form-select form-select-sm" onchange="document.getElementById('alcohol_details_wrap').style.display=this.value==='Yes'?'block':'none'">
                            <option>No</option><option>Yes</option>
                        </select>
                        <div id="alcohol_details_wrap" class="mt-2" style="display:none">
                            <input name="alcohol_details" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
            </div>
            <div class="ph-block">
                <div class="ph-row">
                    <div class="ph-label"><i class="fas fa-leaf text-primary"></i> Betel Nut<small class="text-muted d-block">Dropdown + optional details</small></div>
                    <div class="ph-input">
                        <select name="betel" class="form-select form-select-sm" onchange="document.getElementById('betel_details_wrap').style.display=this.value==='Yes'?'block':'none'">
                            <option>No</option><option>Yes</option>
                        </select>
                        <div id="betel_details_wrap" class="mt-2" style="display:none">
                            <input name="betel_details" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
            </div>
            <div class="ph-block">
                <div class="ph-row">
                    <div class="ph-label"><i class="fas fa-ellipsis-h text-primary"></i> Others</div>
                    <div class="ph-input">
                        <textarea name="others" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Personal History</button>
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
                    <th>Smoking</th>
                    <th>Smoking Details</th>
                    <th>Alcohol</th>
                    <th>Alcohol Details</th>
                    <th>Betel Nut</th>
                    <th>Betel Details</th>
                    <th>Others</th>
                    <th>Date</th>
                    <th style="width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($r['smoking'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['smoking_details'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['alcohol'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['alcohol_details'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['betel'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['betel_details'] ?: '—') ?></td>
                    <td><?= nl2br(htmlspecialchars($r['others'] ?: '—')) ?></td>
                    <td><?= date('d-m-Y', strtotime($r['created_at'])) ?></td>
                    <?= rowActions('personal_history', $r['id']) ?>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="10" class="text-center text-muted py-3">No records yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<style>
.ph-block { border-bottom: 1px solid #eef2f7; padding: 12px 0; }
.ph-block:last-child { border-bottom: none; }
.ph-row { display: grid; grid-template-columns: 220px 1fr; gap: 16px; align-items: start; }
.ph-label { font-weight: 600; color: #1e293b; font-size: 13px; line-height: 1.5; }
.ph-input { min-width: 0; }
.ph-input .form-select { max-width: 200px; }
.full-rec-table { font-size: 12.5px; }
.full-rec-table th { white-space: nowrap; font-size: 11.5px; background: #f1f5f9; }
.full-rec-table td { vertical-align: top; max-width: 200px; }
@media (max-width: 768px) {
    .ph-row { grid-template-columns: 1fr; gap: 6px; }
    .ph-input .form-select { max-width: 100%; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>