<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT * FROM discharge_summary WHERE id=$id")->fetch_assoc();
if (!$r) die("Record not found");
$patient_id = $r['patient_id'];
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
$pageTitle = 'Edit Discharge Summary';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $values = [
        $nz($_POST['admission_date'] ?? ''),
        $nz($_POST['discharge_date'] ?? ''),
        $nz($_POST['diagnosis'] ?? ''),
        $nz($_POST['relevant_investigations'] ?? ''),
        $nz($_POST['operation_date'] ?? ''),
        $nz($_POST['operation_time'] ?? ''),
        $nz($_POST['operation_name'] ?? ''),
        $nz($_POST['operation_laterality'] ?? ''),
        $nz($_POST['operative_findings'] ?? ''),
        $nz($_POST['procedure_details'] ?? ''),
        $nz($_POST['medication'] ?? ''),
        $nz($_POST['advice'] ?? ''),
        $nz($_POST['followup_date'] ?? ''),
        $nz($_POST['followup_instructions'] ?? ''),
        $id,
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("UPDATE discharge_summary SET 
        admission_date=?, discharge_date=?, diagnosis=?, relevant_investigations=?, 
        operation_date=?, operation_time=?, operation_name=?, operation_laterality=?, 
        operative_findings=?, procedure_details=?, medication=?, advice=?, 
        followup_date=?, followup_instructions=? 
        WHERE id=?");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    if (!empty($_POST['discharge_date'])) {
        $dd = $conn->real_escape_string($_POST['discharge_date']);
        $conn->query("UPDATE patients SET discharge_date='$dd' WHERE id=$patient_id");
    }

    header("Location: discharge.php?patient_id=$patient_id&saved=1"); exit;
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Edit Discharge Summary</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="discharge.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<form method="POST">
    <div class="card">
        <div class="card-body">

            <h6 class="ds-section">Admission &amp; Discharge</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-4"><label class="form-label">Admission Date</label>
                    <input type="date" name="admission_date" class="form-control"
                           value="<?= htmlspecialchars($r['admission_date']) ?>"></div>
                <div class="col-md-4"><label class="form-label">Discharge Date</label>
                    <input type="date" name="discharge_date" class="form-control"
                           value="<?= htmlspecialchars($r['discharge_date']) ?>"></div>
                <div class="col-md-4"><label class="form-label">Follow-up Date</label>
                    <input type="date" name="followup_date" class="form-control"
                           value="<?= htmlspecialchars($r['followup_date']) ?>"></div>
            </div>

            <h6 class="ds-section">Diagnosis &amp; Investigations</h6>
            <div class="row g-3 mb-3">
                <div class="col-12"><label class="form-label">Diagnosis</label>
                    <textarea name="diagnosis" class="form-control" rows="2"><?= htmlspecialchars($r['diagnosis']) ?></textarea></div>
                <div class="col-12"><label class="form-label">Relevant Investigations</label>
                    <textarea name="relevant_investigations" class="form-control" rows="2"><?= htmlspecialchars($r['relevant_investigations']) ?></textarea></div>
            </div>

            <h6 class="ds-section">Operation</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-3"><label class="form-label">Operation Date</label>
                    <input type="date" name="operation_date" class="form-control"
                           value="<?= htmlspecialchars($r['operation_date']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Operation Time</label>
                    <input type="time" name="operation_time" class="form-control"
                           value="<?= htmlspecialchars($r['operation_time']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Laterality</label>
                    <select name="operation_laterality" class="form-select">
                        <?php foreach(['Not applicable','Right','Left','Bilateral'] as $o): ?>
                            <option <?= $r['operation_laterality'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="col-md-3"><label class="form-label">Operation Name</label>
                    <input name="operation_name" class="form-control"
                           value="<?= htmlspecialchars($r['operation_name']) ?>"></div>
                <div class="col-12"><label class="form-label">Operative Findings</label>
                    <textarea name="operative_findings" class="form-control" rows="2"><?= htmlspecialchars($r['operative_findings']) ?></textarea></div>
                <div class="col-12"><label class="form-label">Procedure Details</label>
                    <textarea name="procedure_details" class="form-control" rows="2"><?= htmlspecialchars($r['procedure_details']) ?></textarea></div>
            </div>

            <h6 class="ds-section">Medication &amp; Advice</h6>
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Medication</label>
                    <textarea name="medication" class="form-control" rows="3"><?= htmlspecialchars($r['medication']) ?></textarea></div>
                <div class="col-12"><label class="form-label">Advice</label>
                    <textarea name="advice" class="form-control" rows="3"><?= htmlspecialchars($r['advice']) ?></textarea></div>
                <div class="col-12"><label class="form-label">Follow-up Instructions</label>
                    <textarea name="followup_instructions" class="form-control" rows="2"><?= htmlspecialchars($r['followup_instructions']) ?></textarea></div>
            </div>

        </div>
        <div class="card-footer text-end">
            <a href="discharge_pdf.php?id=<?= $id ?>" class="btn btn-danger me-2"><i class="fas fa-file-pdf me-1"></i> Export PDF</a>
            <a href="discharge.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary me-2">Cancel</a>
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update Discharge Summary</button>
        </div>
    </div>
</form>

<style>
.ds-section {
    font-size: 13px;
    font-weight: 700;
    color: #1e40af;
    background: #eff6ff;
    border-left: 3px solid #2563eb;
    padding: 6px 12px;
    border-radius: 4px;
    margin: 8px 0;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>