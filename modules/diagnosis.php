<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$patient_id = intval($_GET['patient_id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
if (!$p) die("Patient not found");
$pageTitle = 'Diagnosis';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $bl_stone    = isset($_POST['bladder_stone'])       ? 1 : 0;
    $bph         = isset($_POST['bph'])                  ? 1 : 0;
    $ca_prostate = isset($_POST['carcinoma_prostate'])   ? 1 : 0;
    $bl_mass     = isset($_POST['bladder_mass'])         ? 1 : 0;
    $stricture   = isset($_POST['stricture_urethra'])    ? 1 : 0;
    $pelvic      = isset($_POST['pelvic_fracture'])      ? 1 : 0;
    $hypo        = isset($_POST['hypospadias'])          ? 1 : 0;
    $ca_penis    = isset($_POST['carcinoma_penis'])      ? 1 : 0;

    $stricture_details  = $stricture ? $nz($_POST['stricture_details']  ?? '') : null;
    $hypospadias_details = $hypo     ? $nz($_POST['hypospadias_details'] ?? '') : null;

    $renal_stone       = $_POST['renal_stone']       ?? 'None';
    $ureteric_stone    = $_POST['ureteric_stone']    ?? 'None';
    $ureteric_location = ($ureteric_stone !== 'None') ? $nz($_POST['ureteric_location'] ?? '') : null;
    $renal_mass        = $_POST['renal_mass']        ?? 'None';
    $utuc              = $_POST['utuc']              ?? 'None';
    $puj_obstruction   = $_POST['puj_obstruction']   ?? 'None';
    $testicular_tumor  = $_POST['testicular_tumor']  ?? 'None';
    $varicocele        = $_POST['varicocele']        ?? 'None';

    $values = [
        $patient_id,
        $renal_stone, $ureteric_stone, $ureteric_location,
        $bl_stone, $bph, $ca_prostate,
        $renal_mass, $utuc, $bl_mass,
        $stricture, $stricture_details, $pelvic, $hypo, $hypospadias_details,
        $puj_obstruction, $testicular_tumor, $ca_penis, $varicocele,
        $nz($_POST['others'] ?? ''),
        $_SESSION['user_id'],
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("INSERT INTO diagnosis 
        (patient_id, renal_stone, ureteric_stone, ureteric_location, bladder_stone, bph, carcinoma_prostate, 
         renal_mass, utuc, bladder_mass, stricture_urethra, stricture_details, pelvic_fracture, 
         hypospadias, hypospadias_details, puj_obstruction, testicular_tumor, carcinoma_penis, varicocele, 
         others, created_by) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: diagnosis.php?patient_id=$patient_id&saved=1"); exit;
}
$records = $conn->query("SELECT * FROM diagnosis WHERE patient_id=$patient_id ORDER BY id DESC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-diagnoses text-primary me-2"></i>Diagnosis</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="../patients/view.php?id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Diagnosis saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Diagnosis</div>
        <div class="card-body row g-3">
            <div class="col-12"><h6 class="dx-section"><i class="fas fa-gem text-primary me-1"></i> Stone Disease</h6></div>

            <div class="col-md-4"><label class="form-label">Renal Stone</label>
                <select name="renal_stone" class="form-select"><?php foreach(['None','Right','Left','Bilateral'] as $o): ?><option><?= $o ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Ureteric Stone</label>
                <select name="ureteric_stone" class="form-select" onchange="document.getElementById('ureteric_loc_wrap').style.display=this.value==='None'?'none':'block'"><?php foreach(['None','Right','Left','Bilateral'] as $o): ?><option><?= $o ?></option><?php endforeach; ?></select></div>
            <div id="ureteric_loc_wrap" class="col-md-4" style="display:none">
                <label class="form-label">Ureteric Location</label>
                <select name="ureteric_location" class="form-select"><option value="">—</option><option>Upper</option><option>Mid</option><option>Lower</option></select></div>
            <div class="col-md-4"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="bladder_stone" id="bs"><label class="form-check-label" for="bs">Urinary Bladder Stone</label></div></div>

            <div class="col-12"><h6 class="dx-section mt-2"><i class="fas fa-male text-primary me-1"></i> Prostate / Bladder</h6></div>
            <div class="col-md-4"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="bph" id="bph"><label class="form-check-label" for="bph">Benign Enlargement of Prostate</label></div></div>
            <div class="col-md-4"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="carcinoma_prostate" id="cap"><label class="form-check-label" for="cap">Carcinoma Prostate</label></div></div>
            <div class="col-md-4"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="bladder_mass" id="bm"><label class="form-check-label" for="bm">Urinary Bladder Mass</label></div></div>

            <div class="col-12"><h6 class="dx-section mt-2"><i class="fas fa-kidney text-primary me-1"></i> Upper Tract / Masses</h6></div>
            <div class="col-md-4"><label class="form-label">Renal Mass</label>
                <select name="renal_mass" class="form-select"><?php foreach(['None','Right','Left','Bilateral'] as $o): ?><option><?= $o ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Upper Tract Urothelial Carcinoma</label>
                <select name="utuc" class="form-select"><?php foreach(['None','Right','Left','Bilateral'] as $o): ?><option><?= $o ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Pelviureteric Junction Obstruction</label>
                <select name="puj_obstruction" class="form-select"><?php foreach(['None','Right','Left','Bilateral'] as $o): ?><option><?= $o ?></option><?php endforeach; ?></select></div>

            <div class="col-12"><h6 class="dx-section mt-2"><i class="fas fa-user-md text-primary me-1"></i> Urethral / Genital</h6></div>
            <div class="col-md-3"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="stricture_urethra" id="su" onchange="document.getElementById('stricture_wrap').style.display=this.checked?'block':'none'"><label class="form-check-label" for="su">Stricture Urethra</label></div></div>
            <div id="stricture_wrap" class="col-md-9" style="display:none">
                <label class="form-label small">Stricture Details</label>
                <input name="stricture_details" class="form-control"></div>
            <div class="col-md-3"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="pelvic_fracture" id="pf"><label class="form-check-label" for="pf">Pelvic Fracture Urethral Injury</label></div></div>
            <div class="col-md-3"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="hypospadias" id="hyp" onchange="document.getElementById('hypo_wrap').style.display=this.checked?'block':'none'"><label class="form-check-label" for="hyp">Hypospadias</label></div></div>
            <div id="hypo_wrap" class="col-md-6" style="display:none">
                <label class="form-label small">Hypospadias Details</label>
                <input name="hypospadias_details" class="form-control"></div>

            <div class="col-12"><h6 class="dx-section mt-2"><i class="fas fa-venus-mars text-primary me-1"></i> Testicular / Penile</h6></div>
            <div class="col-md-4"><label class="form-label">Testicular Tumor</label>
                <select name="testicular_tumor" class="form-select"><?php foreach(['None','Right','Left','Bilateral'] as $o): ?><option><?= $o ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Varicocele</label>
                <select name="varicocele" class="form-select"><?php foreach(['None','Right','Left','Bilateral'] as $o): ?><option><?= $o ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="carcinoma_penis" id="cp"><label class="form-check-label" for="cp">Carcinoma Penis</label></div></div>

            <div class="col-12 mt-2">
                <label class="form-label"><i class="fas fa-ellipsis-h text-primary"></i> Others</label>
                <textarea name="others" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Diagnosis</button>
        </div>
    </div>
</form>

<!-- ================= SAVED RECORDS — FULL DETAIL ================= -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2 text-primary"></i>Diagnosis Records (<?= $records->num_rows ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle full-rec-table">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Renal Stone</th>
                    <th>Ureteric Stone</th>
                    <th>Ureteric Location</th>
                    <th>Bladder Stone</th>
                    <th>BPH</th>
                    <th>Ca Prostate</th>
                    <th>Renal Mass</th>
                    <th>UTUC</th>
                    <th>Bladder Mass</th>
                    <th>Stricture Urethra</th>
                    <th>Stricture Details</th>
                    <th>Pelvic Fracture</th>
                    <th>Hypospadias</th>
                    <th>Hypospadias Details</th>
                    <th>PUJ Obstruction</th>
                    <th>Testicular Tumor</th>
                    <th>Ca Penis</th>
                    <th>Varicocele</th>
                    <th>Others</th>
                    <th>Date</th>
                    <th style="width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($r['renal_stone']) ?></td>
                    <td><?= htmlspecialchars($r['ureteric_stone']) ?></td>
                    <td><?= htmlspecialchars($r['ureteric_location'] ?: '—') ?></td>
                    <td><?= $r['bladder_stone'] ? '<span class="badge bg-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td><?= $r['bph'] ? '<span class="badge bg-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td><?= $r['carcinoma_prostate'] ? '<span class="badge bg-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td><?= htmlspecialchars($r['renal_mass']) ?></td>
                    <td><?= htmlspecialchars($r['utuc']) ?></td>
                    <td><?= $r['bladder_mass'] ? '<span class="badge bg-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td><?= $r['stricture_urethra'] ? '<span class="badge bg-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td><?= htmlspecialchars($r['stricture_details'] ?: '—') ?></td>
                    <td><?= $r['pelvic_fracture'] ? '<span class="badge bg-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td><?= $r['hypospadias'] ? '<span class="badge bg-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td><?= htmlspecialchars($r['hypospadias_details'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['puj_obstruction']) ?></td>
                    <td><?= htmlspecialchars($r['testicular_tumor']) ?></td>
                    <td><?= $r['carcinoma_penis'] ? '<span class="badge bg-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td><?= htmlspecialchars($r['varicocele']) ?></td>
                    <td><?= htmlspecialchars($r['others'] ?: '—') ?></td>
                    <td><?= date('d-m-Y', strtotime($r['created_at'])) ?></td>
                    <?= rowActions('diagnosis', $r['id']) ?>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="22" class="text-center text-muted py-3">No records yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<style>
.dx-section { font-size: 13px; font-weight: 700; color: #1e40af; background: #eff6ff; border-left: 3px solid #2563eb; padding: 6px 12px; border-radius: 4px; margin: 4px 0; }
.full-rec-table { font-size: 11.5px; }
.full-rec-table th { white-space: nowrap; font-size: 10.5px; background: #f1f5f9; }
.full-rec-table td { vertical-align: top; max-width: 180px; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>