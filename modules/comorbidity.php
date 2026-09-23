<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$patient_id = intval($_GET['patient_id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
if (!$p) die("Patient not found");
$pageTitle = 'Comorbidity';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;
    $htn   = isset($_POST['htn'])   ? 1 : 0;
    $dm    = isset($_POST['dm'])    ? 1 : 0;
    $ihd   = isset($_POST['ihd'])   ? 1 : 0;
    $ckd   = isset($_POST['ckd'])   ? 1 : 0;
    $copd  = isset($_POST['copd'])  ? 1 : 0;
    $neuro = isset($_POST['neurological']) ? 1 : 0;
    $neuro_details = $neuro ? $nz($_POST['neurological_details'] ?? '') : null;
    $others        = $nz($_POST['others'] ?? '');

    $values = [$patient_id, $htn, $dm, $ihd, $ckd, $copd, $neuro, $neuro_details, $others, $_SESSION['user_id']];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("INSERT INTO comorbidity 
        (patient_id, htn, dm, ihd, ckd, copd, neurological, neurological_details, others, created_by) 
        VALUES (?,?,?,?,?,?,?,?,?,?)");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: comorbidity.php?patient_id=$patient_id&saved=1"); exit;
}
$records = $conn->query("SELECT * FROM comorbidity WHERE patient_id=$patient_id ORDER BY id DESC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-heartbeat text-primary me-2"></i>Comorbidity</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="../patients/view.php?id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Comorbidity saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Comorbidities</div>
        <div class="card-body">
            <div class="cm-grid">
                <div class="cm-item"><input class="form-check-input" type="checkbox" name="htn" id="htn"><label class="form-check-label" for="htn">Hypertension (HTN)</label></div>
                <div class="cm-item"><input class="form-check-input" type="checkbox" name="dm" id="dm"><label class="form-check-label" for="dm">Diabetes Mellitus (DM)</label></div>
                <div class="cm-item"><input class="form-check-input" type="checkbox" name="ihd" id="ihd"><label class="form-check-label" for="ihd">Ischemic Heart Disease (IHD)</label></div>
                <div class="cm-item"><input class="form-check-input" type="checkbox" name="ckd" id="ckd"><label class="form-check-label" for="ckd">Chronic Kidney Disease (CKD)</label></div>
                <div class="cm-item"><input class="form-check-input" type="checkbox" name="copd" id="copd"><label class="form-check-label" for="copd">COPD</label></div>
                <div class="cm-item"><input class="form-check-input" type="checkbox" name="neurological" id="neurological" onchange="document.getElementById('neuro_extra').style.display=this.checked?'flex':'none'"><label class="form-check-label" for="neurological">Neurological Disease</label></div>
            </div>
            <div id="neuro_extra" class="cm-extra" style="display:none">
                <label class="form-label small mb-0"><i class="fas fa-brain text-primary"></i> Specify Neurological Disease</label>
                <input name="neurological_details" class="form-control form-control-sm">
            </div>
            <div class="mt-3">
                <label class="form-label"><i class="fas fa-ellipsis-h text-primary"></i> Others</label>
                <textarea name="others" class="form-control" rows="2"></textarea>
            </div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Comorbidities</button>
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
                    <th>HTN</th>
                    <th>DM</th>
                    <th>IHD</th>
                    <th>CKD</th>
                    <th>COPD</th>
                    <th>Neurological</th>
                    <th>Neurological Details</th>
                    <th>Others</th>
                    <th>Date</th>
                    <th style="width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= $r['htn']  ? '<span class="badge bg-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td><?= $r['dm']   ? '<span class="badge bg-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td><?= $r['ihd']  ? '<span class="badge bg-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td><?= $r['ckd']  ? '<span class="badge bg-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td><?= $r['copd'] ? '<span class="badge bg-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td><?= $r['neurological'] ? '<span class="badge bg-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td><?= htmlspecialchars($r['neurological_details'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['others'] ?: '—') ?></td>
                    <td><?= date('d-m-Y', strtotime($r['created_at'])) ?></td>
                    <?= rowActions('comorbidity', $r['id']) ?>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="11" class="text-center text-muted py-3">No records yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<style>
.cm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 8px 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; }
.cm-item { display: flex; align-items: center; gap: 8px; }
.cm-item .form-check-input { margin: 0; }
.cm-item .form-check-label { font-size: 13px; font-weight: 500; cursor: pointer; }
.cm-extra { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 12px; padding: 10px 14px; background: #eff6ff; border-left: 3px solid #2563eb; border-radius: 6px; }
.full-rec-table { font-size: 12px; }
.full-rec-table th { white-space: nowrap; font-size: 11px; background: #f1f5f9; }
.full-rec-table td { vertical-align: top; max-width: 250px; }
@media (max-width: 576px) { .cm-grid { grid-template-columns: 1fr; padding: 10px; } .cm-extra { flex-direction: column; align-items: stretch; } }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>