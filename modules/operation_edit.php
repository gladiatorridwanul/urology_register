<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT * FROM operations WHERE id=$id")->fetch_assoc();
if (!$r) die("Record not found");
$patient_id = $r['patient_id'];
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
$pageTitle = 'Edit Operation';

// Load all operation types
$opTypes = $conn->query("SELECT id, name FROM op_types WHERE status=1 ORDER BY name");

function handleUploadEdit($fieldName, $oldFile = null) {
    if (empty($_FILES[$fieldName]['name'])) return [$oldFile, null];
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','pdf'])) return [$oldFile, null];
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $fname = uniqid('op_') . '.' . $ext;
    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], UPLOAD_DIR . $fname)) {
        if ($oldFile && file_exists(UPLOAD_DIR . $oldFile)) @unlink(UPLOAD_DIR . $oldFile);
        return [$fname, $_FILES[$fieldName]['type']];
    }
    return [$oldFile, null];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    list($findings_file, $findings_file_type) = handleUploadEdit('findings_file', $r['findings_file']);
    if ($findings_file_type === null) $findings_file_type = $r['findings_file_type'];

    list($procedure_file, $procedure_file_type) = handleUploadEdit('procedure_file', $r['procedure_file']);
    if ($procedure_file_type === null) $procedure_file_type = $r['procedure_file_type'];

    $op_type_id    = intval($_POST['op_type_id'] ?? 0) ?: null;
    $op_subtype_id = intval($_POST['op_subtype_id'] ?? 0) ?: null;

    $opTypeName = $opSubtypeName = null;
    if ($op_type_id) {
        $rr = $conn->query("SELECT name FROM op_types WHERE id=$op_type_id")->fetch_assoc();
        $opTypeName = $rr['name'] ?? null;
    }
    if ($op_subtype_id) {
        $rr = $conn->query("SELECT name FROM op_subtypes WHERE id=$op_subtype_id")->fetch_assoc();
        $opSubtypeName = $rr['name'] ?? null;
    }
    if ($op_subtype_id && $op_subtype_id > 0) {
        // handled
    } elseif ($_POST['op_subtype_id'] === '__other__') {
        $opSubtypeName = 'Others';
    }

    $opSubtypeOther = $nz($_POST['op_subtype_other'] ?? '');

    $values = [
        $nz($_POST['operation_date'] ?? ''),
        $nz($_POST['op_duration'] ?? ''),
        $_POST['laterality'] ?? 'Not applicable',
        $opTypeName,
        $opSubtypeName,
        $opSubtypeOther,
        $nz($_POST['operative_findings'] ?? ''),
        $findings_file,
        $findings_file_type,
        $nz($_POST['procedure_details'] ?? ''),
        $procedure_file,
        $procedure_file_type,
        $op_type_id,
        $op_subtype_id,
        $id,
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("UPDATE operations SET 
        operation_date=?, op_duration=?, laterality=?, 
        op_type=?, op_subtype=?, op_subtype_other=?, 
        operative_findings=?, findings_file=?, findings_file_type=?, 
        procedure_details=?, procedure_file=?, procedure_file_type=?,
        op_type_id=?, op_subtype_id=?
        WHERE id=?");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: operation.php?patient_id=$patient_id&saved=1"); exit;
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2 align-items-start">
    <div>
        <h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Edit Operation</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <div class="pc-actions-view">
        <a href="operation.php?patient_id=<?= $patient_id ?>" class="btn btn-icon btn-secondary" title="Back">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>
</div>

<form method="POST" enctype="multipart/form-data">
    <div class="card">
        <div class="card-body row g-3">
            <div class="col-md-3"><label class="form-label">Operation Date</label>
                <input type="date" name="operation_date" class="form-control" value="<?= htmlspecialchars($r['operation_date']) ?>"></div>
            <div class="col-md-3"><label class="form-label">Duration (Time)</label>
                <input type="time" name="op_duration" class="form-control" value="<?= htmlspecialchars($r['op_duration']) ?>"></div>
            <div class="col-md-3"><label class="form-label">Laterality</label>
                <select name="laterality" class="form-select">
                    <?php foreach(['Right','Left','Not applicable'] as $o): ?>
                        <option <?= $r['laterality'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="col-md-3"><label class="form-label">Operation Type</label>
                <select name="op_type_id" id="op_type_id" class="form-select" required onchange="loadSubtypes(this.value, null)">
                    <option value="">— Select type —</option>
                    <?php $opTypes->data_seek(0); while ($t = $opTypes->fetch_assoc()): ?>
                        <option value="<?= $t['id'] ?>" <?= ($r['op_type_id'] ?? 0) == $t['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select></div>
            <div class="col-md-6"><label class="form-label">Operation Sub-Type</label>
                <select name="op_subtype_id" id="op_subtype_id" class="form-select" onchange="document.getElementById('op_subtype_other_wrap').style.display=this.value==='__other__'?'block':'none'">
                    <option value="">— Select Operation Type first —</option>
                </select></div>
            <div class="col-md-6" id="op_subtype_other_wrap"
                 style="display:<?= ($r['op_subtype'] === 'Others' || !empty($r['op_subtype_other'])) ? 'block' : 'none' ?>">
                <label class="form-label">Specify Other Sub-Type</label>
                <input name="op_subtype_other" class="form-control"
                       value="<?= htmlspecialchars($r['op_subtype_other'] ?? '') ?>">
            </div>
            <div class="col-md-8"><label class="form-label">Findings</label>
                <textarea name="operative_findings" class="form-control" rows="3"><?= htmlspecialchars($r['operative_findings']) ?></textarea></div>
            <div class="col-md-4"><label class="form-label">Findings — Image / PDF</label>
                <input type="file" name="findings_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                <?php if (!empty($r['findings_file'])): ?>
                    <small class="text-muted d-block mt-1">Current: <a href="<?= BASE_URL . UPLOAD_URL . $r['findings_file'] ?>" target="_blank">View</a></small>
                <?php endif; ?>
            </div>
            <div class="col-md-8"><label class="form-label">Procedure</label>
                <textarea name="procedure_details" class="form-control" rows="3"><?= htmlspecialchars($r['procedure_details']) ?></textarea></div>
            <div class="col-md-4"><label class="form-label">Procedure — Image / PDF</label>
                <input type="file" name="procedure_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                <?php if (!empty($r['procedure_file'])): ?>
                    <small class="text-muted d-block mt-1">Current: <a href="<?= BASE_URL . UPLOAD_URL . $r['procedure_file'] ?>" target="_blank">View</a></small>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-footer text-end">
            <a href="operation.php?patient_id=<?= $patient_id ?>" class="btn btn-secondary px-4 me-2">
                <i class="fas fa-times me-1"></i> Cancel
            </a>
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update Operation</button>
        </div>
    </div>
</form>

<script>
const CURRENT_TYPE_ID    = <?= json_encode($r['op_type_id'] ?? 0) ?>;
const CURRENT_SUBTYPE_ID = <?= json_encode($r['op_subtype_id'] ?? 0) ?>;
const CURRENT_SUBTYPE_NAME = <?= json_encode($r['op_subtype'] ?? '') ?>;

function loadSubtypes(typeId, preselectId) {
    const subSel = document.getElementById('op_subtype_id');
    const otherWrap = document.getElementById('op_subtype_other_wrap');
    subSel.innerHTML = '<option value="">— Loading… —</option>';
    if (!typeId) {
        subSel.innerHTML = '<option value="">— Select Operation Type first —</option>';
        otherWrap.style.display = 'none';
        return;
    }

    fetch('../settings/api.php?action=op_subtypes&type_id=' + typeId)
        .then(r => r.json())
        .then(data => {
            subSel.innerHTML = '<option value="">— Select Sub-Type —</option>';
            data.forEach(s => {
                const o = document.createElement('option');
                o.value = s.id;
                o.textContent = s.name;
                if (preselectId && preselectId == s.id) o.selected = true;
                subSel.appendChild(o);
            });
            const oth = document.createElement('option');
            oth.value = '__other__';
            oth.textContent = 'Others (specify below)';
            if (CURRENT_SUBTYPE_NAME === 'Others' && !preselectId) oth.selected = true;
            subSel.appendChild(oth);
        });
}

// On page load: rebuild subtypes if op_type_id already set
document.addEventListener('DOMContentLoaded', function() {
    if (CURRENT_TYPE_ID) {
        loadSubtypes(CURRENT_TYPE_ID, CURRENT_SUBTYPE_ID);
    }
});
</script>

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