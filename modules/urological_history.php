<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$patient_id = intval($_GET['patient_id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
if (!$p) die("Patient not found");
$pageTitle = 'Previous Urological History';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $trauma            = $_POST['trauma']            ?? 'No';
    $instrumentation   = $_POST['instrumentation']   ?? 'No';
    $catheterization   = $_POST['catheterization']   ?? 'No';
    $surgery           = $_POST['surgery']           ?? 'No';
    $stone_disease     = $_POST['stone_disease']     ?? 'No';
    $malignancy        = $_POST['malignancy']        ?? 'No';

    $values = [
        $patient_id,
        $trauma,          ($trauma === 'Yes')          ? $nz($_POST['trauma_details'] ?? '') : null,
        $instrumentation, ($instrumentation === 'Yes') ? $nz($_POST['instrumentation_details'] ?? '') : null,
        $catheterization, ($catheterization === 'Yes') ? $nz($_POST['catheterization_details'] ?? '') : null,
        $surgery,         ($surgery === 'Yes')         ? $nz($_POST['surgery_details'] ?? '') : null,
        $stone_disease,   ($stone_disease === 'Yes')   ? $nz($_POST['stone_details'] ?? '') : null,
        $malignancy,      ($malignancy === 'Yes')      ? $nz($_POST['malignancy_details'] ?? '') : null,
        $nz($_POST['others'] ?? ''),
        $_SESSION['user_id'],
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("INSERT INTO urological_history 
        (patient_id, trauma, trauma_details, 
         instrumentation, instrumentation_details,
         catheterization, catheterization_details,
         surgery, surgery_details, 
         stone_disease, stone_details, 
         malignancy, malignancy_details, 
         others, created_by) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: urological_history.php?patient_id=$patient_id&saved=1"); exit;
}
$records = $conn->query("SELECT * FROM urological_history WHERE patient_id=$patient_id ORDER BY id DESC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-history text-primary me-2"></i>Previous Urological History</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="../patients/view.php?id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> History saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Urological History</div>
        <div class="card-body">
            <?php
            $fields = [
                ['trauma',            'History of trauma',                    'trauma_details'],
                ['instrumentation',   'History of per-urethral instrumentation','instrumentation_details'],
                ['catheterization',   'History of per-urethral catheterization','catheterization_details'],
                ['surgery',           'History of surgery',                    'surgery_details'],
                ['stone_disease',     'History of urinary stone disease',      'stone_details'],
                ['malignancy',        'History of urological malignancy',      'malignancy_details'],
            ];
            foreach ($fields as $f):
                $name = $f[0]; $label = $f[1]; $detailName = $f[2];
            ?>
                <div class="uh-block">
                    <div class="uh-row">
                        <div class="uh-label"><i class="fas fa-notes-medical text-primary"></i> <?= htmlspecialchars($label) ?></div>
                        <div class="uh-input">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="<?= $name ?>" id="<?= $name ?>" value="Yes"
                                           onchange="document.getElementById('<?= $name ?>_extra').style.display=this.checked?'inline-flex':'none'">
                                    <label class="form-check-label" for="<?= $name ?>">Yes</label>
                                </div>
                                <div id="<?= $name ?>_extra" style="display:none">
                                    <input name="<?= $detailName ?>" class="form-control form-control-sm d-inline-block" style="width:400px" placeholder="Specify details">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="uh-block">
                <div class="uh-row">
                    <div class="uh-label"><i class="fas fa-ellipsis-h text-primary"></i> Others</div>
                    <div class="uh-input">
                        <textarea name="others" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save History</button>
        </div>
    </div>
</form>

<!-- ================= SAVED RECORDS — FULL DETAIL ================= -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2 text-primary"></i>History Records (<?= $records->num_rows ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle full-rec-table">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Trauma</th>
                    <th>Trauma Details</th>
                    <th>Instrumentation</th>
                    <th>Instrumentation Details</th>
                    <th>Catheterization</th>
                    <th>Catheterization Details</th>
                    <th>Surgery</th>
                    <th>Surgery Details</th>
                    <th>Stone Disease</th>
                    <th>Stone Details</th>
                    <th>Malignancy</th>
                    <th>Malignancy Details</th>
                    <th>Others</th>
                    <th>Date</th>
                    <th style="width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= yesBadge($r['trauma']) ?></td>
                    <td><?= htmlspecialchars($r['trauma_details'] ?: '—') ?></td>
                    <td><?= yesBadge($r['instrumentation']) ?></td>
                    <td><?= htmlspecialchars($r['instrumentation_details'] ?: '—') ?></td>
                    <td><?= yesBadge($r['catheterization']) ?></td>
                    <td><?= htmlspecialchars($r['catheterization_details'] ?: '—') ?></td>
                    <td><?= yesBadge($r['surgery']) ?></td>
                    <td><?= htmlspecialchars($r['surgery_details'] ?: '—') ?></td>
                    <td><?= yesBadge($r['stone_disease']) ?></td>
                    <td><?= htmlspecialchars($r['stone_details'] ?: '—') ?></td>
                    <td><?= yesBadge($r['malignancy']) ?></td>
                    <td><?= htmlspecialchars($r['malignancy_details'] ?: '—') ?></td>
                    <td><?= nl2br(htmlspecialchars($r['others'] ?: '—')) ?></td>
                    <td><?= date('d-m-Y', strtotime($r['created_at'])) ?></td>
                    <?= rowActions('urological_history', $r['id']) ?>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="16" class="text-center text-muted py-3">No records yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<style>
.uh-block { border-bottom: 1px solid #eef2f7; padding: 12px 0; }
.uh-block:last-child { border-bottom: none; }
.uh-row { display: grid; grid-template-columns: 300px 1fr; gap: 16px; align-items: center; }
.uh-label { font-weight: 600; color: #1e293b; font-size: 13px; line-height: 1.5; }
.uh-input { min-width: 0; }
.full-rec-table { font-size: 12px; }
.full-rec-table th { white-space: nowrap; font-size: 11px; background: #f1f5f9; }
.full-rec-table td { vertical-align: top; max-width: 180px; }
@media (max-width: 768px) {
    .uh-row { grid-template-columns: 1fr; gap: 6px; }
    .uh-input input[style*="width:400px"] { width: 100% !important; }
}
</style>

<?php
function yesBadge($v) {
    if ($v === 'Yes') return '<span class="badge bg-success">Yes</span>';
    return '<span class="text-muted">No</span>';
}
?>

<?php include __DIR__ . '/../includes/footer.php'; ?>