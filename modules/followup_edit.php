<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT * FROM followup WHERE id=$id")->fetch_assoc();
if (!$r) die("Record not found");
$patient_id = $r['patient_id'];
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
$pageTitle = 'Edit Follow-up';

/** Upload handler — replaces existing file if new one uploaded */
function handleFollowupEditUpload($fieldName, $oldFile = null) {
    if (empty($_FILES[$fieldName]['name'])) return [$oldFile, null];
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','pdf'])) return [$oldFile, null];
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $fname = uniqid('fu_') . '.' . $ext;
    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], UPLOAD_DIR . $fname)) {
        if ($oldFile && file_exists(UPLOAD_DIR . $oldFile)) @unlink(UPLOAD_DIR . $oldFile);
        return [$fname, $_FILES[$fieldName]['type']];
    }
    return [$oldFile, null];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    list($investigation_file, $investigation_file_type) = handleFollowupEditUpload('investigation_file', $r['investigation_file']);
    if ($investigation_file_type === null) $investigation_file_type = $r['investigation_file_type'];

    $visit_type = $_POST['visit_type'] ?? 'Routine';
    $scoring    = $nz($_POST['scoring'] ?? '');
    $notes      = $nz($_POST['notes'] ?? '');

    $values = [
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
        $id,
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("UPDATE followup SET 
        followup_date=?, visit_type=?, 
        chief_complain=?, exam_finding=?, investigation_finding=?, investigation_file=?, investigation_file_type=?, 
        management_plan=?, scoring=?, notes=? 
        WHERE id=?");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: followup.php?patient_id=$patient_id&saved=1"); exit;
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2 align-items-start">
    <div>
        <h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Edit Follow-up</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <div class="pc-actions-view">
        <a href="followup.php?patient_id=<?= $patient_id ?>" class="btn btn-icon btn-secondary" title="Back">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>
</div>

<form method="POST" enctype="multipart/form-data">
    <div class="card">
        <div class="card-body row g-3">

            <!-- Date + Visit Type + Scoring -->
            <div class="col-md-4">
                <label class="form-label">Follow-up Date</label>
                <input type="date" name="followup_date" class="form-control"
                       value="<?= htmlspecialchars($r['followup_date']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Visit Type</label>
                <select name="visit_type" class="form-select">
                    <?php foreach(['Routine','Pre Operative','Post Operative','Emergency'] as $o): ?>
                        <option <?= $r['visit_type'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Scoring <small class="text-muted">(optional)</small></label>
                <input name="scoring" class="form-control" value="<?= htmlspecialchars($r['scoring']) ?>">
            </div>

            <!-- Chief Complain -->
            <div class="col-12">
                <label class="form-label"><i class="fas fa-comment-medical text-primary me-1"></i> Chief Complain</label>
                <textarea name="chief_complain" class="form-control" rows="2"><?= htmlspecialchars($r['chief_complain']) ?></textarea>
            </div>

            <!-- Examination Finding -->
            <div class="col-12">
                <label class="form-label"><i class="fas fa-stethoscope text-primary me-1"></i> Examination Finding</label>
                <textarea name="exam_finding" class="form-control" rows="2"><?= htmlspecialchars($r['exam_finding']) ?></textarea>
            </div>

            <!-- Investigation Finding + Upload -->
            <div class="col-md-8">
                <label class="form-label"><i class="fas fa-flask text-primary me-1"></i> Investigation Finding</label>
                <textarea name="investigation_finding" class="form-control" rows="3"><?= htmlspecialchars($r['investigation_finding']) ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Investigation File</label>
                <input type="file" name="investigation_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                <?php if (!empty($r['investigation_file'])): ?>
                    <small class="text-muted d-block mt-1">
                        Current:
                        <a href="<?= BASE_URL . UPLOAD_URL . htmlspecialchars($r['investigation_file']) ?>" target="_blank">
                            View file
                        </a>
                    </small>
                <?php endif; ?>
            </div>

            <!-- Management Plan -->
            <div class="col-12">
                <label class="form-label"><i class="fas fa-notes-medical text-primary me-1"></i> Management Plan</label>
                <textarea name="management_plan" class="form-control" rows="3"><?= htmlspecialchars($r['management_plan']) ?></textarea>
            </div>

            <!-- Additional Notes -->
            <div class="col-12">
                <label class="form-label"><i class="fas fa-ellipsis-h text-primary me-1"></i> Additional Notes</label>
                <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($r['notes']) ?></textarea>
            </div>

        </div>
        <div class="card-footer text-end">
            <a href="followup.php?patient_id=<?= $patient_id ?>" class="btn btn-secondary px-4 me-2">
                <i class="fas fa-times me-1"></i> Cancel
            </a>
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update Follow-up</button>
        </div>
    </div>
</form>

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
@media (max-width: 576px) { .btn-icon { width: 30px; height: 30px; font-size: 12px; } }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>