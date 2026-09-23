<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$patient_id = intval($_GET['patient_id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
if (!$p) die("Patient not found");
$pageTitle = 'Follow-up';

function handleFollowupUpload($fieldName) {
    if (empty($_FILES[$fieldName]['name'])) return [null, null];
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','pdf'])) return [null, null];
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $fname = uniqid('fu_') . '.' . $ext;
    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], UPLOAD_DIR . $fname)) {
        return [$fname, $_FILES[$fieldName]['type']];
    }
    return [null, null];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    list($investigation_file, $investigation_file_type) = handleFollowupUpload('investigation_file');

    $visit_type = $_POST['visit_type'] ?? 'Routine';
    $scoring    = $nz($_POST['scoring'] ?? '');
    $notes      = $nz($_POST['notes'] ?? '');

    $values = [
        $patient_id,
        $nz($_POST['followup_date'] ?? ''),
        $visit_type,
        $nz($_POST['chief_complain'] ?? ''),
        $nz($_POST['exam_finding'] ?? ''),
        $nz($_POST['investigation_finding'] ?? ''),
        $investigation_file,
        $investigation_file_type,
        $nz($_POST['management_plan'] ?? ''),
        $scoring,
        $notes,
        $_SESSION['user_id'],
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("INSERT INTO followup 
        (patient_id, followup_date, visit_type, 
         chief_complain, exam_finding, investigation_finding, investigation_file, investigation_file_type, 
         management_plan, scoring, notes, created_by) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: followup.php?patient_id=$patient_id&saved=1"); exit;
}
$records = $conn->query("SELECT * FROM followup WHERE patient_id=$patient_id ORDER BY id DESC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2 align-items-start">
    <div>
        <h5 class="mb-0"><i class="fas fa-calendar-check text-primary me-2"></i>Follow-up</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <div class="pc-actions-view">
        <a href="../patients/view.php?id=<?= $patient_id ?>" class="btn btn-icon btn-secondary" title="Back to Patient">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Follow-up saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Follow-up</div>
        <div class="card-body row g-3">
            <div class="col-md-4"><label class="form-label">Follow-up Date <span class="text-danger">*</span></label>
                <input type="date" name="followup_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
            <div class="col-md-4"><label class="form-label">Visit Type</label>
                <select name="visit_type" class="form-select"><?php foreach(['Routine','Pre Operative','Post Operative','Emergency'] as $t): ?><option><?= $t ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Scoring <small class="text-muted">(optional)</small></label>
                <input name="scoring" class="form-control"></div>

            <div class="col-12"><label class="form-label"><i class="fas fa-comment-medical text-primary me-1"></i> Chief Complain</label>
                <textarea name="chief_complain" class="form-control" rows="2"></textarea></div>

            <div class="col-12"><label class="form-label"><i class="fas fa-stethoscope text-primary me-1"></i> Examination Finding</label>
                <textarea name="exam_finding" class="form-control" rows="2"></textarea></div>

            <div class="col-md-8"><label class="form-label"><i class="fas fa-flask text-primary me-1"></i> Investigation Finding</label>
                <textarea name="investigation_finding" class="form-control" rows="3"></textarea></div>
            <div class="col-md-4"><label class="form-label">Investigation File</label>
                <input type="file" name="investigation_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                <small class="text-muted">JPG / JPEG / PNG / PDF</small></div>

            <div class="col-12"><label class="form-label"><i class="fas fa-notes-medical text-primary me-1"></i> Management Plan</label>
                <textarea name="management_plan" class="form-control" rows="3"></textarea></div>

            <div class="col-12"><label class="form-label"><i class="fas fa-ellipsis-h text-primary me-1"></i> Additional Notes <small class="text-muted">(optional)</small></label>
                <textarea name="notes" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Follow-up</button>
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
                    <th>Date</th>
                    <th>Visit Type</th>
                    <th>Scoring</th>
                    <th>Chief Complain</th>
                    <th>Examination Finding</th>
                    <th>Investigation Finding</th>
                    <th>Investigation File</th>
                    <th>Management Plan</th>
                    <th>Additional Notes</th>
                    <th>Created</th>
                    <th style="width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($r['followup_date'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['visit_type'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['scoring'] ?: '—') ?></td>
                    <td><?= nl2br(htmlspecialchars($r['chief_complain'] ?: '—')) ?></td>
                    <td><?= nl2br(htmlspecialchars($r['exam_finding'] ?: '—')) ?></td>
                    <td><?= nl2br(htmlspecialchars($r['investigation_finding'] ?: '—')) ?></td>
                    <td class="text-center">
                        <?php if (!empty($r['investigation_file'])): ?>
                            <a href="<?= BASE_URL . UPLOAD_URL . htmlspecialchars($r['investigation_file']) ?>" target="_blank" class="thumb-link" title="View investigation file">
                                <?php if (strpos((string)$r['investigation_file_type'], 'image') !== false): ?>
                                    <img src="<?= BASE_URL . UPLOAD_URL . htmlspecialchars($r['investigation_file']) ?>" class="thumb-img" style="width:32px;height:32px">
                                <?php else: ?>
                                    <span class="badge bg-danger" style="font-size:9px">PDF</span>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= nl2br(htmlspecialchars($r['management_plan'] ?: '—')) ?></td>
                    <td><?= nl2br(htmlspecialchars($r['notes'] ?: '—')) ?></td>
                    <td><?= date('d-m-Y H:i', strtotime($r['created_at'])) ?></td>
                    <?= rowActions('followup', $r['id']) ?>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="12" class="text-center text-muted py-3">No records yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<style>
.pc-actions-view { display: flex; gap: 8px; flex-wrap: wrap; }
.btn-icon {
    width: 34px; height: 34px; padding: 0;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 7px; font-size: 13px; color: #fff; border: none;
    transition: transform .15s, box-shadow .15s, filter .15s;
    text-decoration: none;
}
.btn-icon:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,.18);
    color: #fff; filter: brightness(1.05);
}
.thumb-link { display: inline-flex; align-items: center; justify-content: center; border-radius: 4px; overflow: hidden; border: 1px solid #e2e8f0; }
.thumb-img { object-fit: cover; display: block; border-radius: 3px; }
.full-rec-table { font-size: 12px; }
.full-rec-table th { white-space: nowrap; font-size: 11px; background: #f1f5f9; }
.full-rec-table td { vertical-align: top; max-width: 200px; }
@media (max-width: 576px) { .btn-icon { width: 30px; height: 30px; font-size: 12px; } }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>