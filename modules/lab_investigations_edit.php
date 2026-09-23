<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT * FROM lab_investigations WHERE id=$id")->fetch_assoc();
if (!$r) die("Record not found");
$patient_id = $r['patient_id'];
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
$pageTitle = 'Edit Lab Investigation';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $values = [
        $nz($_POST['hemoglobin'] ?? ''),
        $nz($_POST['wbc'] ?? ''),
        $nz($_POST['platelet'] ?? ''),
        $nz($_POST['esr'] ?? ''),
        $nz($_POST['creatinine'] ?? ''),
        $nz($_POST['electrolytes'] ?? ''),
        $nz($_POST['psa'] ?? ''),
        $nz($_POST['urine_rbc'] ?? ''),
        $nz($_POST['urine_pus'] ?? ''),
        $nz($_POST['rbs'] ?? ''),
        $_POST['urine_cs_growth'] ?? 'Pending',
        $nz($_POST['urine_cs_sensitivity'] ?? ''),
        $nz($_POST['others'] ?? ''),
        $id,
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("UPDATE lab_investigations SET 
        hemoglobin=?, wbc=?, platelet=?, esr=?, creatinine=?, electrolytes=?, psa=?, 
        urine_rbc=?, urine_pus=?, rbs=?, urine_cs_growth=?, urine_cs_sensitivity=?, others=? 
        WHERE id=?");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: lab_investigations.php?patient_id=$patient_id&saved=1"); exit;
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Edit Lab Investigation</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="lab_investigations.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<form method="POST">
    <div class="card">
        <div class="card-body row g-3">

            <div class="col-12"><h6 class="lab-section"><i class="fas fa-tint text-danger me-1"></i> Haematology</h6></div>

            <div class="col-md-3">
                <label class="form-label">Hemoglobin</label>
                <div class="input-group input-group-sm">
                    <input name="hemoglobin" class="form-control" value="<?= htmlspecialchars($r['hemoglobin']) ?>">
                    <span class="input-group-text">g/dL</span>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Total WBC Count</label>
                <div class="input-group input-group-sm">
                    <input name="wbc" class="form-control" value="<?= htmlspecialchars($r['wbc']) ?>">
                    <span class="input-group-text">/µL</span>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Platelet Count</label>
                <div class="input-group input-group-sm">
                    <input name="platelet" class="form-control" value="<?= htmlspecialchars($r['platelet']) ?>">
                    <span class="input-group-text">/µL</span>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">ESR</label>
                <div class="input-group input-group-sm">
                    <input name="esr" class="form-control" value="<?= htmlspecialchars($r['esr']) ?>">
                    <span class="input-group-text">mm/hr</span>
                </div>
            </div>

            <div class="col-12"><h6 class="lab-section mt-2"><i class="fas fa-vial text-primary me-1"></i> Biochemistry</h6></div>

            <div class="col-md-3">
                <label class="form-label">Serum Creatinine</label>
                <div class="input-group input-group-sm">
                    <input name="creatinine" class="form-control" value="<?= htmlspecialchars($r['creatinine']) ?>">
                    <span class="input-group-text">mg/dL</span>
                </div>
            </div>
            <div class="col-md-9">
                <label class="form-label">Serum Electrolyte</label>
                <input name="electrolytes" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($r['electrolytes']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Serum PSA</label>
                <div class="input-group input-group-sm">
                    <input name="psa" class="form-control" value="<?= htmlspecialchars($r['psa']) ?>">
                    <span class="input-group-text">ng/mL</span>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">RBS</label>
                <div class="input-group input-group-sm">
                    <input name="rbs" class="form-control" value="<?= htmlspecialchars($r['rbs']) ?>">
                    <span class="input-group-text">mg/dL</span>
                </div>
            </div>

            <div class="col-12"><h6 class="lab-section mt-2"><i class="fas fa-tint text-warning me-1"></i> Urine Analysis</h6></div>

            <div class="col-md-3">
                <label class="form-label">Urine R/E — RBC</label>
                <input name="urine_rbc" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($r['urine_rbc']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Urine R/E — Pus Cell</label>
                <input name="urine_pus" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($r['urine_pus']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Urine C/S — Growth</label>
                <select name="urine_cs_growth" class="form-select form-select-sm">
                    <?php foreach(['Pending','Growth','No growth'] as $o): ?>
                        <option <?= $r['urine_cs_growth'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Urine C/S — Sensitivity</label>
                <input name="urine_cs_sensitivity" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($r['urine_cs_sensitivity']) ?>">
            </div>

            <div class="col-12 mt-2">
                <label class="form-label"><i class="fas fa-ellipsis-h text-primary"></i> Others</label>
                <textarea name="others" class="form-control" rows="2"><?= htmlspecialchars($r['others']) ?></textarea>
            </div>

        </div>
        <div class="card-footer text-end">
            <a href="lab_investigations.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary me-2">Cancel</a>
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update</button>
        </div>
    </div>
</form>

<style>
.lab-section {
    font-size: 13px;
    font-weight: 700;
    color: #1e40af;
    background: #eff6ff;
    border-left: 3px solid #2563eb;
    padding: 6px 12px;
    border-radius: 4px;
    margin: 4px 0;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>