<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$patient_id = intval($_GET['patient_id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
if (!$p) die("Patient not found");
$pageTitle = 'Operation Procedure';

// Load operation types
$opTypes = $conn->query("SELECT id, name FROM op_types WHERE status=1 ORDER BY name");

function handleUpload($fieldName) {
    if (empty($_FILES[$fieldName]['name'])) return [null, null];
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','pdf'])) return [null, null];
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $fname = uniqid('op_') . '.' . $ext;
    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], UPLOAD_DIR . $fname)) {
        return [$fname, $_FILES[$fieldName]['type']];
    }
    return [null, null];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    list($findings_file, $findings_file_type)   = handleUpload('findings_file');
    list($procedure_file, $procedure_file_type) = handleUpload('procedure_file');

    // NEW: accept type/subtype by ID; store names for legacy columns
    $op_type_id    = intval($_POST['op_type_id'] ?? 0) ?: null;
    $op_subtype_id = intval($_POST['op_subtype_id'] ?? 0) ?: null;

    $opTypeName = $opSubtypeName = null;
    if ($op_type_id) {
        $r = $conn->query("SELECT name FROM op_types WHERE id=$op_type_id")->fetch_assoc();
        $opTypeName = $r['name'] ?? null;
    }
    if ($op_subtype_id) {
        $r = $conn->query("SELECT name FROM op_subtypes WHERE id=$op_subtype_id")->fetch_assoc();
        $opSubtypeName = $r['name'] ?? null;
    }

    // Manual fallback
    $opSubtypeOther = $nz($_POST['op_subtype_other'] ?? '');

    $values = [
        $patient_id,
        $nz($_POST['operation_date'] ?? ''),
        $nz($_POST['op_duration'] ?? ''),
        $_POST['laterality'] ?? 'Not applicable',
        $opTypeName,          // legacy text column
        $opSubtypeName,       // legacy text column
        $opSubtypeOther,
        $nz($_POST['operative_findings'] ?? ''),
        $findings_file,
        $findings_file_type,
        $nz($_POST['procedure_details'] ?? ''),
        $procedure_file,
        $procedure_file_type,
        $_SESSION['user_id'],
        $op_type_id,          // new ID column
        $op_subtype_id,       // new ID column
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("INSERT INTO operations 
        (patient_id, operation_date, op_duration, laterality, 
         op_type, op_subtype, op_subtype_other, 
         operative_findings, findings_file, findings_file_type, 
         procedure_details, procedure_file, procedure_file_type, 
         created_by, op_type_id, op_subtype_id) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: operation.php?patient_id=$patient_id&saved=1"); exit;
}
$records = $conn->query("SELECT * FROM operations WHERE patient_id=$patient_id ORDER BY id DESC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2 align-items-start">
    <div>
        <h5 class="mb-0"><i class="fas fa-procedures text-primary me-2"></i>Operation / Procedure</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <div class="pc-actions-view">
        <a href="../patients/view.php?id=<?= $patient_id ?>" class="btn btn-icon btn-secondary" title="Back">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Operation saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Operation</div>
        <div class="card-body row g-3">
            <div class="col-md-3"><label class="form-label">Operation Date</label><input type="date" name="operation_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-3"><label class="form-label">Duration (Time)</label><input type="time" name="op_duration" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Laterality</label>
                <select name="laterality" class="form-select"><option>Right</option><option>Left</option><option>Not applicable</option></select></div>
            <div class="col-md-3"><label class="form-label">Operation Type</label>
                <select name="op_type_id" id="op_type_id" class="form-select" required onchange="loadSubtypes(this.value)">
                    <option value="">— Select type —</option>
                    <?php $opTypes->data_seek(0); while ($t = $opTypes->fetch_assoc()): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                    <?php endwhile; ?>
                </select></div>
            <div class="col-md-6"><label class="form-label">Operation Sub-Type</label>
                <select name="op_subtype_id" id="op_subtype_id" class="form-select" onchange="document.getElementById('op_subtype_other_wrap').style.display=this.value==='__other__'?'block':'none'">
                    <option value="">— Select Operation Type first —</option>
                </select></div>
            <div class="col-md-6" id="op_subtype_other_wrap" style="display:none">
                <label class="form-label">Specify Other Sub-Type</label>
                <input name="op_subtype_other" id="op_subtype_other" class="form-control" placeholder="Type custom sub-type">
            </div>
            <div class="col-md-8"><label class="form-label">Findings</label>
                <textarea name="operative_findings" class="form-control" rows="3"></textarea></div>
            <div class="col-md-4"><label class="form-label">Findings — Image / PDF</label>
                <input type="file" name="findings_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf"></div>
            <div class="col-md-8"><label class="form-label">Procedure</label>
                <textarea name="procedure_details" class="form-control" rows="3"></textarea></div>
            <div class="col-md-4"><label class="form-label">Procedure — Image / PDF</label>
                <input type="file" name="procedure_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf"></div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Operation</button>
        </div>
    </div>
</form>

<!-- ================= SAVED RECORDS ================= -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2 text-primary"></i>Operations Performed (<?= $records->num_rows ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle full-rec-table">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Date</th>
                    <th>Duration</th>
                    <th>Laterality</th>
                    <th>Operation Type</th>
                    <th>Operation Sub-Type</th>
                    <th>Other Sub-Type</th>
                    <th>Findings (Full)</th>
                    <th>Findings File</th>
                    <th>Procedure (Full)</th>
                    <th>Procedure File</th>
                    <th>Created</th>
                    <th style="width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= $r['operation_date'] ?: '—' ?></td>
                    <td><?= $r['op_duration'] ? substr($r['op_duration'], 0, 5) : '—' ?></td>
                    <td><?= htmlspecialchars($r['laterality'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['op_type'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['op_subtype'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['op_subtype_other'] ?: '—') ?></td>
                    <td><?= nl2br(htmlspecialchars($r['operative_findings'] ?: '—')) ?></td>
                    <td>
                        <?php if (!empty($r['findings_file'])): ?>
                            <a href="<?= BASE_URL . UPLOAD_URL . $r['findings_file'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="View file">
                                <?php if (strpos((string)$r['findings_file_type'], 'image') !== false): ?>
                                    <img src="<?= BASE_URL . UPLOAD_URL . $r['findings_file'] ?>" style="width:32px;height:32px;border-radius:3px">
                                <?php else: ?>
                                    <i class="fas fa-file-pdf text-danger"></i> PDF
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= nl2br(htmlspecialchars($r['procedure_details'] ?: '—')) ?></td>
                    <td>
                        <?php if (!empty($r['procedure_file'])): ?>
                            <a href="<?= BASE_URL . UPLOAD_URL . $r['procedure_file'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="View file">
                                <?php if (strpos((string)$r['procedure_file_type'], 'image') !== false): ?>
                                    <img src="<?= BASE_URL . UPLOAD_URL . $r['procedure_file'] ?>" style="width:32px;height:32px;border-radius:3px">
                                <?php else: ?>
                                    <i class="fas fa-file-pdf text-danger"></i> PDF
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d-m-Y H:i', strtotime($r['created_at'])) ?></td>
                    <?= rowActions('operation', $r['id']) ?>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="13" class="text-center text-muted py-3">No records yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script>
function loadSubtypes(typeId) {
    const subSel = document.getElementById('op_subtype_id');
    const otherWrap = document.getElementById('op_subtype_other_wrap');
    subSel.innerHTML = '<option value="">— Loading… —</option>';
    otherWrap.style.display = 'none';

    if (!typeId) {
        subSel.innerHTML = '<option value="">— Select Operation Type first —</option>';
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
                subSel.appendChild(o);
            });
            // "Others" option for free-text
            const oth = document.createElement('option');
            oth.value = '__other__';
            oth.textContent = 'Others (specify below)';
            subSel.appendChild(oth);
        });
}
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
.full-rec-table { font-size: 11.5px; }
.full-rec-table th { white-space: nowrap; font-size: 10.5px; background: #f1f5f9; }
.full-rec-table td { vertical-align: top; max-width: 180px; }
@media (max-width: 576px) { .btn-icon { width: 30px; height: 30px; font-size: 12px; } }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>