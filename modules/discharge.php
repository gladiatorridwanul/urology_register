<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$patient_id = intval($_GET['patient_id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
if (!$p) die("Patient not found");
$pageTitle = 'Discharge Summary';

// ---- AUTO-FILL DATA ----
$dxRow = $conn->query("SELECT * FROM diagnosis WHERE patient_id=$patient_id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$dxParts = [];
if ($dxRow) {
    if ($dxRow['renal_stone']      !== 'None') $dxParts[] = 'Renal Stone (' . $dxRow['renal_stone'] . ')';
    if ($dxRow['ureteric_stone']   !== 'None') $dxParts[] = 'Ureteric Stone (' . $dxRow['ureteric_stone'] . ')';
    if ($dxRow['bladder_stone'])                $dxParts[] = 'Bladder Stone';
    if ($dxRow['bph'])                          $dxParts[] = 'BPH';
    if ($dxRow['carcinoma_prostate'])           $dxParts[] = 'Carcinoma Prostate';
    if ($dxRow['renal_mass']       !== 'None') $dxParts[] = 'Renal Mass (' . $dxRow['renal_mass'] . ')';
    if ($dxRow['utuc']             !== 'None') $dxParts[] = 'UTUC (' . $dxRow['utuc'] . ')';
    if ($dxRow['bladder_mass'])                 $dxParts[] = 'Bladder Mass';
    if ($dxRow['stricture_urethra'])            $dxParts[] = 'Stricture Urethra';
    if ($dxRow['puj_obstruction']  !== 'None') $dxParts[] = 'PUJ Obstruction';
    if ($dxRow['testicular_tumor'] !== 'None') $dxParts[] = 'Testicular Tumor';
    if ($dxRow['varicocele']       !== 'None') $dxParts[] = 'Varicocele';
    if (!empty($dxRow['others']))               $dxParts[] = $dxRow['others'];
}
$dxAutoFill = implode(', ', $dxParts);

$opRow = $conn->query("SELECT * FROM operations WHERE patient_id=$patient_id ORDER BY id DESC LIMIT 1")->fetch_assoc();

$labRow = $conn->query("SELECT * FROM lab_investigations WHERE patient_id=$patient_id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$labParts = [];
if ($labRow) {
    if ($labRow['hemoglobin'])  $labParts[] = 'Hb: ' . $labRow['hemoglobin'] . ' g/dL';
    if ($labRow['creatinine'])  $labParts[] = 'Creatinine: ' . $labRow['creatinine'] . ' mg/dL';
    if ($labRow['psa'])         $labParts[] = 'PSA: ' . $labRow['psa'] . ' ng/mL';
    if ($labRow['urine_cs_growth'] === 'Growth' && $labRow['urine_cs_sensitivity'])
        $labParts[] = 'Urine C/S: Growth — ' . $labRow['urine_cs_sensitivity'];
}
$labAutoFill = implode(' | ', $labParts);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $values = [
        $patient_id,
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
        $_SESSION['user_id'],
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("INSERT INTO discharge_summary 
        (patient_id, admission_date, discharge_date, diagnosis, relevant_investigations, 
         operation_date, operation_time, operation_name, operation_laterality, 
         operative_findings, procedure_details, medication, advice, 
         followup_date, followup_instructions, created_by) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    if (!empty($_POST['discharge_date'])) {
        $dd = $conn->real_escape_string($_POST['discharge_date']);
        $conn->query("UPDATE patients SET discharge_date='$dd' WHERE id=$patient_id");
    }

    header("Location: discharge.php?patient_id=$patient_id&saved=1"); exit;
}
$records = $conn->query("SELECT * FROM discharge_summary WHERE patient_id=$patient_id ORDER BY id DESC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-file-medical-alt text-primary me-2"></i>Discharge Summary</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>) — Auto-filled fields are editable</small>
    </div>
    <a href="../patients/view.php?id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Discharge summary saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-plus-circle me-2 text-primary"></i>Create Discharge Summary</span>
            <div class="d-flex gap-2">
                <?php if ($records->num_rows > 0):
                    $records->data_seek(0); $first = $records->fetch_assoc(); $records->data_seek(0); ?>
                    <a href="discharge_pdf.php?id=<?= $first['id'] ?>" class="btn btn-sm btn-danger"><i class="fas fa-file-pdf me-1"></i> Export Latest PDF</a>
                    <a href="discharge_pdf_all.php?patient_id=<?= $patient_id ?>" class="btn btn-sm btn-outline-danger"><i class="fas fa-file-pdf me-1"></i> Export All PDFs</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">

            <h6 class="ds-section">Patient Identification (auto-filled)</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-6"><label class="form-label small">Name</label><input class="form-control" value="<?= htmlspecialchars($p['name']) ?>" disabled></div>
                <div class="col-md-3"><label class="form-label small">URO ID</label><input class="form-control" value="<?= htmlspecialchars($p['uro_id']) ?>" disabled></div>
                <div class="col-md-3"><label class="form-label small">Age / Sex</label><input class="form-control" value="<?= $p['age'] . ' / ' . $p['sex'] ?>" disabled></div>
                <div class="col-md-6"><label class="form-label small">Address</label><input class="form-control" value="<?= htmlspecialchars($p['address']) ?>" disabled></div>
                <div class="col-md-3"><label class="form-label small">Mobile</label><input class="form-control" value="<?= htmlspecialchars($p['mobile']) ?>" disabled></div>
                <div class="col-md-3"><label class="form-label small">Unit</label><input class="form-control" value="<?= htmlspecialchars($p['unit']) ?>" disabled></div>
            </div>

            <h6 class="ds-section">Admission &amp; Discharge</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-4"><label class="form-label">Date of Admission</label><input type="date" name="admission_date" class="form-control" value="<?= htmlspecialchars($p['first_admission_date']) ?>"></div>
                <div class="col-md-4"><label class="form-label">Date of Discharge</label><input type="date" name="discharge_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                <div class="col-md-4"><label class="form-label">Follow-up Date</label><input type="date" name="followup_date" class="form-control"></div>
            </div>

            <h6 class="ds-section">Diagnosis</h6>
            <div class="mb-3"><label class="form-label small text-muted">Auto-filled from latest diagnosis record — edit if needed</label>
                <textarea name="diagnosis" class="form-control" rows="2"><?= htmlspecialchars($dxAutoFill) ?></textarea></div>

            <h6 class="ds-section">Relevant Investigations</h6>
            <div class="mb-3"><label class="form-label small text-muted">Auto-filled from latest lab investigations — edit if needed</label>
                <textarea name="relevant_investigations" class="form-control" rows="2"><?= htmlspecialchars($labAutoFill) ?></textarea></div>

            <h6 class="ds-section">Operation</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-3"><label class="form-label">Operation Date</label><input type="date" name="operation_date" class="form-control" value="<?= htmlspecialchars($opRow['operation_date'] ?? '') ?>"></div>
                <div class="col-md-3"><label class="form-label">Operation Time</label><input type="time" name="operation_time" class="form-control" value="<?= htmlspecialchars($opRow['operation_time'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Operation Name</label><input name="operation_name" class="form-control" value="<?= htmlspecialchars($opRow['operation_name'] ?? '') ?>"></div>
                <div class="col-md-3"><label class="form-label">Laterality</label>
                    <select name="operation_laterality" class="form-select">
                        <?php foreach(['Not applicable','Right','Left','Bilateral'] as $o): ?>
                            <option <?= ($opRow['laterality'] ?? 'Not applicable') === $o ? 'selected' : '' ?>><?= $o ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="col-12"><label class="form-label">Operative Findings</label><textarea name="operative_findings" class="form-control" rows="2"><?= htmlspecialchars($opRow['operative_findings'] ?? '') ?></textarea></div>
                <div class="col-12"><label class="form-label">Procedure Details</label><textarea name="procedure_details" class="form-control" rows="2"><?= htmlspecialchars($opRow['procedure_details'] ?? '') ?></textarea></div>
            </div>

            <h6 class="ds-section">Medication &amp; Advice</h6>
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Medication on Discharge</label><textarea name="medication" class="form-control" rows="3"></textarea></div>
                <div class="col-12"><label class="form-label">Advice</label><textarea name="advice" class="form-control" rows="3"></textarea></div>
                <div class="col-12"><label class="form-label">Follow-up Instructions</label><textarea name="followup_instructions" class="form-control" rows="2"></textarea></div>
            </div>

        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Discharge Summary</button>
        </div>
    </div>
</form>

<!-- ================= SAVED RECORDS — FULL DETAIL ================= -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2 text-primary"></i>Discharge Summaries (<?= $records->num_rows ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle full-rec-table">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Admission</th>
                    <th>Discharge</th>
                    <th>Diagnosis</th>
                    <th>Investigations</th>
                    <th>Operation Date</th>
                    <th>Operation Time</th>
                    <th>Operation Name</th>
                    <th>Laterality</th>
                    <th>Findings</th>
                    <th>Procedure</th>
                    <th>Medication</th>
                    <th>Advice</th>
                    <th>Follow-up</th>
                    <th>Follow-up Notes</th>
                    <th>Created</th>
                    <th style="width:200px">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= $r['admission_date'] ?: '—' ?></td>
                    <td><?= $r['discharge_date'] ?: '—' ?></td>
                    <td><?= nl2br(htmlspecialchars($r['diagnosis'] ?: '—')) ?></td>
                    <td><?= nl2br(htmlspecialchars($r['relevant_investigations'] ?: '—')) ?></td>
                    <td><?= $r['operation_date'] ?: '—' ?></td>
                    <td><?= $r['operation_time'] ?: '—' ?></td>
                    <td><?= htmlspecialchars($r['operation_name'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['operation_laterality'] ?: '—') ?></td>
                    <td><?= nl2br(htmlspecialchars($r['operative_findings'] ?: '—')) ?></td>
                    <td><?= nl2br(htmlspecialchars($r['procedure_details'] ?: '—')) ?></td>
                    <td><?= nl2br(htmlspecialchars($r['medication'] ?: '—')) ?></td>
                    <td><?= nl2br(htmlspecialchars($r['advice'] ?: '—')) ?></td>
                    <td><?= $r['followup_date'] ?: '—' ?></td>
                    <td><?= nl2br(htmlspecialchars($r['followup_instructions'] ?: '—')) ?></td>
                    <td><?= date('d-m-Y', strtotime($r['created_at'])) ?></td>
                    <td class="text-nowrap">
                        <a href="discharge_print.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Print"><i class="fas fa-print"></i></a>
                        <a href="discharge_pdf.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" title="PDF"><i class="fas fa-file-pdf"></i></a>
                        <a href="discharge_edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>
                        <a href="discharge_delete.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete" title="Delete"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="17" class="text-center text-muted py-3">No records yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<style>
.ds-section { font-size: 13px; font-weight: 700; color: #1e40af; background: #eff6ff; border-left: 3px solid #2563eb; padding: 6px 12px; border-radius: 4px; margin: 8px 0; }
.full-rec-table { font-size: 11.5px; }
.full-rec-table th { white-space: nowrap; font-size: 10.5px; background: #f1f5f9; }
.full-rec-table td { vertical-align: top; max-width: 220px; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>