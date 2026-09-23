<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$patient_id = intval($_GET['patient_id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
if (!$p) die("Patient not found");
$pageTitle = 'Laboratory Investigations';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $values = [
        $patient_id,
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
        $_SESSION['user_id'],
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("INSERT INTO lab_investigations 
        (patient_id, hemoglobin, wbc, platelet, esr, creatinine, electrolytes, psa, 
         urine_rbc, urine_pus, rbs, urine_cs_growth, urine_cs_sensitivity, others, created_by) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: lab_investigations.php?patient_id=$patient_id&saved=1"); exit;
}
$records = $conn->query("SELECT * FROM lab_investigations WHERE patient_id=$patient_id ORDER BY id DESC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-flask text-primary me-2"></i>Laboratory Investigations</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="../patients/view.php?id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Lab investigations saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Lab Values</div>
        <div class="card-body row g-3">
            <div class="col-12"><h6 class="lab-section"><i class="fas fa-tint text-danger me-1"></i> Haematology</h6></div>
            <div class="col-md-3"><label class="form-label">Hemoglobin</label><div class="input-group input-group-sm"><input name="hemoglobin" class="form-control" placeholder="e.g. 12.5"><span class="input-group-text">g/dL</span></div></div>
            <div class="col-md-3"><label class="form-label">Total WBC Count</label><div class="input-group input-group-sm"><input name="wbc" class="form-control"><span class="input-group-text">/µL</span></div></div>
            <div class="col-md-3"><label class="form-label">Platelet Count</label><div class="input-group input-group-sm"><input name="platelet" class="form-control"><span class="input-group-text">/µL</span></div></div>
            <div class="col-md-3"><label class="form-label">ESR</label><div class="input-group input-group-sm"><input name="esr" class="form-control"><span class="input-group-text">mm/hr</span></div></div>

            <div class="col-12"><h6 class="lab-section mt-2"><i class="fas fa-vial text-primary me-1"></i> Biochemistry</h6></div>
            <div class="col-md-3"><label class="form-label">Serum Creatinine</label><div class="input-group input-group-sm"><input name="creatinine" class="form-control"><span class="input-group-text">mg/dL</span></div></div>
            <div class="col-md-9"><label class="form-label">Serum Electrolyte</label><input name="electrolytes" class="form-control form-control-sm" placeholder="Na / K / Cl"></div>
            <div class="col-md-3"><label class="form-label">Serum PSA</label><div class="input-group input-group-sm"><input name="psa" class="form-control"><span class="input-group-text">ng/mL</span></div></div>
            <div class="col-md-3"><label class="form-label">RBS</label><div class="input-group input-group-sm"><input name="rbs" class="form-control"><span class="input-group-text">mg/dL</span></div></div>

            <div class="col-12"><h6 class="lab-section mt-2"><i class="fas fa-tint text-warning me-1"></i> Urine Analysis</h6></div>
            <div class="col-md-3"><label class="form-label">Urine R/E — RBC</label><input name="urine_rbc" class="form-control form-control-sm" placeholder="e.g. 5–10 /HPF"></div>
            <div class="col-md-3"><label class="form-label">Urine R/E — Pus Cell</label><input name="urine_pus" class="form-control form-control-sm" placeholder="e.g. 10–15 /HPF"></div>
            <div class="col-md-3"><label class="form-label">Urine C/S — Growth</label><select name="urine_cs_growth" class="form-select form-select-sm"><option>Pending</option><option>Growth</option><option>No growth</option></select></div>
            <div class="col-md-3"><label class="form-label">Urine C/S — Sensitivity</label><input name="urine_cs_sensitivity" class="form-control form-control-sm"></div>

            <div class="col-12 mt-2">
                <label class="form-label"><i class="fas fa-ellipsis-h text-primary"></i> Others</label>
                <textarea name="others" class="form-control" rows="2"></textarea>
            </div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Lab Values</button>
        </div>
    </div>
</form>

<!-- ================= SAVED RECORDS — FULL DETAIL ================= -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2 text-primary"></i>Lab Records (<?= $records->num_rows ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle full-rec-table">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Date</th>
                    <th>Hb</th>
                    <th>WBC</th>
                    <th>Platelet</th>
                    <th>ESR</th>
                    <th>Creatinine</th>
                    <th>Electrolytes</th>
                    <th>PSA</th>
                    <th>Urine RBC</th>
                    <th>Urine Pus</th>
                    <th>RBS</th>
                    <th>Urine C/S Growth</th>
                    <th>Urine C/S Sensitivity</th>
                    <th>Others</th>
                    <th style="width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= date('d-m-Y', strtotime($r['created_at'])) ?></td>
                    <td><?= htmlspecialchars($r['hemoglobin'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['wbc'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['platelet'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['esr'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['creatinine'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['electrolytes'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['psa'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['urine_rbc'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['urine_pus'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['rbs'] ?: '—') ?></td>
                    <td>
                        <?php if ($r['urine_cs_growth'] === 'Growth'): ?>
                            <span class="badge bg-danger">Growth</span>
                        <?php elseif ($r['urine_cs_growth'] === 'No growth'): ?>
                            <span class="badge bg-success">No growth</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($r['urine_cs_sensitivity'] ?: '—') ?></td>
                    <td><?= nl2br(htmlspecialchars($r['others'] ?: '—')) ?></td>
                    <?= rowActions('lab_investigations', $r['id']) ?>
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
.lab-section { font-size: 13px; font-weight: 700; color: #1e40af; background: #eff6ff; border-left: 3px solid #2563eb; padding: 6px 12px; border-radius: 4px; margin: 4px 0; }
.full-rec-table { font-size: 11.5px; }
.full-rec-table th { white-space: nowrap; font-size: 10.5px; background: #f1f5f9; }
.full-rec-table td { vertical-align: top; max-width: 150px; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>