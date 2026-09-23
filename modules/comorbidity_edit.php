<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT * FROM comorbidity WHERE id=$id")->fetch_assoc();
if (!$r) die("Record not found");
$patient_id = $r['patient_id'];
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
$pageTitle = 'Edit Comorbidity';

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

    $values = [
        $htn,              // 1  i
        $dm,               // 2  i
        $ihd,              // 3  i
        $ckd,              // 4  i
        $copd,             // 5  i
        $neuro,            // 6  i
        $neuro_details,    // 7  s
        $others,           // 8  s
        $id,               // 9  i (WHERE)
    ];

    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $sql = "UPDATE comorbidity SET 
        htn=?, dm=?, ihd=?, ckd=?, copd=?, neurological=?, 
        neurological_details=?, others=? 
        WHERE id=?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) die("Prepare failed: " . $conn->error);

    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: comorbidity.php?patient_id=$patient_id&saved=1");
    exit;
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Edit Comorbidity</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="comorbidity.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<form method="POST">
    <div class="card">
        <div class="card-body">

            <div class="cm-grid">
                <div class="cm-item">
                    <input class="form-check-input" type="checkbox" name="htn" id="htn" <?= $r['htn'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="htn">Hypertension (HTN)</label>
                </div>
                <div class="cm-item">
                    <input class="form-check-input" type="checkbox" name="dm" id="dm" <?= $r['dm'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="dm">Diabetes Mellitus (DM)</label>
                </div>
                <div class="cm-item">
                    <input class="form-check-input" type="checkbox" name="ihd" id="ihd" <?= $r['ihd'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ihd">Ischemic Heart Disease (IHD)</label>
                </div>
                <div class="cm-item">
                    <input class="form-check-input" type="checkbox" name="ckd" id="ckd" <?= $r['ckd'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ckd">Chronic Kidney Disease (CKD)</label>
                </div>
                <div class="cm-item">
                    <input class="form-check-input" type="checkbox" name="copd" id="copd" <?= $r['copd'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="copd">COPD</label>
                </div>
                <div class="cm-item">
                    <input class="form-check-input" type="checkbox" name="neurological" id="neurological"
                           <?= $r['neurological'] ? 'checked' : '' ?>
                           onchange="document.getElementById('neuro_extra').style.display=this.checked?'flex':'none'">
                    <label class="form-check-label" for="neurological">Neurological Disease</label>
                </div>
            </div>

            <div id="neuro_extra" class="cm-extra"
                 style="display:<?= $r['neurological'] ? 'flex' : 'none' ?>">
                <label class="form-label small mb-0"><i class="fas fa-brain text-primary"></i> Specify Neurological Disease</label>
                <input name="neurological_details" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($r['neurological_details']) ?>"
                       placeholder="e.g. Parkinson's disease, CVA, neuropathy">
            </div>

            <div class="mt-3">
                <label class="form-label"><i class="fas fa-ellipsis-h text-primary"></i> Others</label>
                <textarea name="others" class="form-control" rows="2"><?= htmlspecialchars($r['others']) ?></textarea>
            </div>

        </div>
        <div class="card-footer text-end">
            <a href="comorbidity.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary me-2">Cancel</a>
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update</button>
        </div>
    </div>
</form>

<style>
.cm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 8px 20px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 14px 16px;
}
.cm-item { display: flex; align-items: center; gap: 8px; }
.cm-item .form-check-input { margin: 0; }
.cm-item .form-check-label { font-size: 13px; font-weight: 500; cursor: pointer; }

.cm-extra {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 12px;
    padding: 10px 14px;
    background: #eff6ff;
    border-left: 3px solid #2563eb;
    border-radius: 6px;
}
@media (max-width: 576px) {
    .cm-grid { grid-template-columns: 1fr; padding: 10px; }
    .cm-extra { flex-direction: column; align-items: stretch; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>