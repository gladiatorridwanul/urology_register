<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$patient_id = intval($_GET['patient_id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
if (!$p) die("Patient not found");
$pageTitle = 'Chief Complaints';

$LUTS_OPTIONS = [
    'Hesitancy','Straining','Weak stream','Intermittency','Incomplete emptying',
    'Frequency','Urgency','Nocturia','Urge incontinence','Dysuria',
    'Terminal dribble','Sense of incomplete emptying','Burning micturition'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lutsArr = $_POST['luts'] ?? [];
    $luts = is_array($lutsArr) ? implode(', ', array_filter($lutsArr)) : '';
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $retention_present = ($_POST['retention_present'] ?? 'No') === 'Yes' ? 1 : 0;
    $retention         = $retention_present ? ($_POST['retention'] ?? 'Acute') : 'None';
    $retention_duration = $retention_present ? $nz($_POST['retention_duration'] ?? '') : null;

    $fever_present = ($_POST['fever_present'] ?? 'No') === 'Yes' ? 1 : 0;
    $fever         = $fever_present ? 'Yes' : 'No';
    $fever_details = $fever_present ? $nz($_POST['fever_details'] ?? '') : null;
    $fever_duration = $fever_present ? $nz($_POST['fever_duration'] ?? '') : null;

    $values = [
        $patient_id, $luts, $nz($_POST['luts_specify'] ?? ''), $nz($_POST['luts_duration'] ?? ''),
        $retention, $retention_present, $retention_duration,
        $nz($_POST['pain'] ?? ''), $nz($_POST['pain_duration'] ?? ''),
        $nz($_POST['hematuria'] ?? ''), $nz($_POST['hematuria_duration'] ?? ''),
        $fever, $fever_present, $fever_details, $fever_duration,
        $nz($_POST['others'] ?? ''), $nz($_POST['others_duration'] ?? ''),
        $_SESSION['user_id'],
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');

    $stmt = $conn->prepare("INSERT INTO chief_complaints 
        (patient_id, luts, luts_specify, luts_duration,
         retention, retention_present, retention_duration,
         pain, pain_duration, hematuria, hematuria_duration,
         fever, fever_present, fever_details, fever_duration,
         others, others_duration, created_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: chief_complaints.php?patient_id=$patient_id&saved=1"); exit;
}
$records = $conn->query("SELECT * FROM chief_complaints WHERE patient_id=$patient_id ORDER BY id DESC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-comment-medical text-primary me-2"></i>Presenting / Chief Complaints</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="../patients/view.php?id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Complaint saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Complaint</div>
        <div class="card-body">
            <div class="cc-block">
                <div class="cc-row">
                    <div class="cc-label"><i class="fas fa-stream text-primary"></i> Lower Urinary Tract Symptoms (LUTS)<small class="text-muted d-block">Multi-select</small></div>
                    <div class="cc-input">
                        <div class="luts-grid">
                            <?php foreach ($LUTS_OPTIONS as $opt): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="luts[]" id="luts_<?= md5($opt) ?>" value="<?= htmlspecialchars($opt) ?>">
                                    <label class="form-check-label" for="luts_<?= md5($opt) ?>"><?= htmlspecialchars($opt) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-8"><label class="form-label small">Specify (additional LUTS)</label><input name="luts_specify" class="form-control form-control-sm"></div>
                            <div class="col-md-4"><label class="form-label small">Duration</label><input name="luts_duration" class="form-control form-control-sm"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="cc-block">
                <div class="cc-row">
                    <div class="cc-label"><i class="fas fa-tint text-primary"></i> Retention of Urine</div>
                    <div class="cc-input">
                        <div class="yn-group">
                            <div class="form-check"><input class="form-check-input" type="radio" name="retention_present" id="retention_no" value="No" checked onchange="document.getElementById('retention_extra').style.display='none'"><label class="form-check-label" for="retention_no">No</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="retention_present" id="retention_yes" value="Yes" onchange="document.getElementById('retention_extra').style.display='flex'"><label class="form-check-label" for="retention_yes">Yes</label></div>
                        </div>
                        <div id="retention_extra" class="yn-extra" style="display:none">
                            <label class="form-label small mb-0">Type</label>
                            <select name="retention" class="form-select form-select-sm" style="width:auto;min-width:160px"><option>Acute</option><option>Chronic</option></select>
                            <input name="retention_duration" class="form-control form-control-sm" style="width:180px" placeholder="Duration">
                        </div>
                    </div>
                </div>
            </div>
            <div class="cc-block">
                <div class="cc-row">
                    <div class="cc-label"><i class="fas fa-bolt text-primary"></i> Pain</div>
                    <div class="cc-input"><div class="row g-2"><div class="col-md-8"><input name="pain" class="form-control form-control-sm"></div><div class="col-md-4"><input name="pain_duration" class="form-control form-control-sm" placeholder="Duration"></div></div></div>
                </div>
            </div>
            <div class="cc-block">
                <div class="cc-row">
                    <div class="cc-label"><i class="fas fa-tint text-danger"></i> Hematuria</div>
                    <div class="cc-input"><div class="row g-2"><div class="col-md-8"><input name="hematuria" class="form-control form-control-sm"></div><div class="col-md-4"><input name="hematuria_duration" class="form-control form-control-sm" placeholder="Duration"></div></div></div>
                </div>
            </div>
            <div class="cc-block">
                <div class="cc-row">
                    <div class="cc-label"><i class="fas fa-thermometer-half text-primary"></i> Fever</div>
                    <div class="cc-input">
                        <div class="yn-group">
                            <div class="form-check"><input class="form-check-input" type="radio" name="fever_present" id="fever_no" value="No" checked onchange="document.getElementById('fever_extra').style.display='none'"><label class="form-check-label" for="fever_no">No</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="fever_present" id="fever_yes" value="Yes" onchange="document.getElementById('fever_extra').style.display='flex'"><label class="form-check-label" for="fever_yes">Yes</label></div>
                        </div>
                        <div id="fever_extra" class="yn-extra" style="display:none">
                            <label class="form-label small mb-0">Specify</label>
                            <input name="fever_details" class="form-control form-control-sm" style="min-width:260px">
                            <input name="fever_duration" class="form-control form-control-sm" style="width:180px" placeholder="Duration">
                        </div>
                    </div>
                </div>
            </div>
            <div class="cc-block">
                <div class="cc-row">
                    <div class="cc-label"><i class="fas fa-ellipsis-h text-primary"></i> Others</div>
                    <div class="cc-input"><div class="row g-2"><div class="col-md-8"><input name="others" class="form-control form-control-sm"></div><div class="col-md-4"><input name="others_duration" class="form-control form-control-sm" placeholder="Duration"></div></div></div>
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Complaint</button>
        </div>
    </div>
</form>

<!-- ================= SAVED RECORDS — FULL DETAIL ================= -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-history me-2 text-primary"></i>Saved Complaints (<?= $records->num_rows ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle full-rec-table">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>LUTS</th>
                    <th>Specify / Duration</th>
                    <th>Retention</th>
                    <th>Retention Duration</th>
                    <th>Pain</th>
                    <th>Pain Duration</th>
                    <th>Hematuria</th>
                    <th>Hematuria Duration</th>
                    <th>Fever</th>
                    <th>Fever Details</th>
                    <th>Fever Duration</th>
                    <th>Others</th>
                    <th>Others Duration</th>
                    <th>Date</th>
                    <th style="width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($r['luts'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['luts_specify'] ?: '—') ?><?php if ($r['luts_duration']): ?> <small class="text-muted">(<?= htmlspecialchars($r['luts_duration']) ?>)</small><?php endif; ?></td>
                    <td><?= $r['retention_present'] ? '<span class="badge bg-warning text-dark">Yes — ' . htmlspecialchars($r['retention']) . '</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td><?= htmlspecialchars($r['retention_duration'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['pain'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['pain_duration'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['hematuria'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['hematuria_duration'] ?: '—') ?></td>
                    <td><?= $r['fever_present'] ? '<span class="badge bg-danger">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td><?= htmlspecialchars($r['fever_details'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['fever_duration'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['others'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['others_duration'] ?: '—') ?></td>
                    <td><?= date('d-m-Y', strtotime($r['created_at'])) ?></td>
                    <?= rowActions('chief_complaints', $r['id']) ?>
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
.cc-block { border-bottom: 1px solid #eef2f7; padding: 12px 0; }
.cc-block:last-child { border-bottom: none; }
.cc-row { display: grid; grid-template-columns: 240px 1fr; gap: 16px; align-items: start; }
.cc-label { font-weight: 600; color: #1e293b; font-size: 13px; line-height: 1.5; }
.cc-input { min-width: 0; }
.luts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 4px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; }
.luts-grid .form-check-label { font-size: 12.5px; }
.yn-group { display: flex; align-items: center; gap: 24px; padding: 4px 0; }
.yn-group .form-check { margin: 0; }
.yn-group .form-check-label { font-size: 13px; font-weight: 500; }
.yn-extra { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 8px; padding: 10px 12px; background: #f1f5f9; border-left: 3px solid #2563eb; border-radius: 6px; }
.full-rec-table { font-size: 12px; }
.full-rec-table th { white-space: nowrap; font-size: 11px; background: #f1f5f9; }
.full-rec-table td { vertical-align: top; max-width: 200px; }
@media (max-width: 768px) {
    .cc-row { grid-template-columns: 1fr; gap: 6px; }
    .cc-label { font-size: 12.5px; }
    .luts-grid { grid-template-columns: 1fr 1fr; }
    .yn-extra { flex-direction: column; align-items: stretch; }
    .yn-extra select, .yn-extra input { width: 100% !important; min-width: 0 !important; }
}
@media (max-width: 480px) { .luts-grid { grid-template-columns: 1fr; } }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>