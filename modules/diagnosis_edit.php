<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT * FROM diagnosis WHERE id=$id")->fetch_assoc();
if (!$r) die("Record not found");
$patient_id = $r['patient_id'];
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
$pageTitle = 'Edit Diagnosis';

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

    $stricture_details   = $stricture ? $nz($_POST['stricture_details']   ?? '') : null;
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
        $renal_stone, $ureteric_stone, $ureteric_location,
        $bl_stone, $bph, $ca_prostate,
        $renal_mass, $utuc, $bl_mass,
        $stricture, $stricture_details, $pelvic, $hypo, $hypospadias_details,
        $puj_obstruction, $testicular_tumor, $ca_penis, $varicocele,
        $nz($_POST['others'] ?? ''),
        $id,
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("UPDATE diagnosis SET 
        renal_stone=?, ureteric_stone=?, ureteric_location=?, 
        bladder_stone=?, bph=?, carcinoma_prostate=?, 
        renal_mass=?, utuc=?, bladder_mass=?, 
        stricture_urethra=?, stricture_details=?, pelvic_fracture=?, 
        hypospadias=?, hypospadias_details=?, puj_obstruction=?, 
        testicular_tumor=?, carcinoma_penis=?, varicocele=?, 
        others=? 
        WHERE id=?");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: diagnosis.php?patient_id=$patient_id&saved=1"); exit;
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Edit Diagnosis</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="diagnosis.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<form method="POST">
    <div class="card">
        <div class="card-body row g-3">

            <div class="col-12"><h6 class="dx-section"><i class="fas fa-gem text-primary me-1"></i> Stone Disease</h6></div>

            <div class="col-md-4">
                <label class="form-label">Renal Stone</label>
                <select name="renal_stone" class="form-select">
                    <?php foreach(['None','Right','Left','Bilateral'] as $o): ?>
                        <option value="<?= $o ?>" <?= $r['renal_stone'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Ureteric Stone</label>
                <select name="ureteric_stone" class="form-select"
                        onchange="document.getElementById('ureteric_loc_wrap').style.display=this.value==='None'?'none':'block'">
                    <?php foreach(['None','Right','Left','Bilateral'] as $o): ?>
                        <option value="<?= $o ?>" <?= $r['ureteric_stone'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="ureteric_loc_wrap" class="col-md-4"
                 style="display:<?= $r['ureteric_stone'] === 'None' ? 'none' : 'block' ?>">
                <label class="form-label">Ureteric Location</label>
                <select name="ureteric_location" class="form-select">
                    <option value="">—</option>
                    <?php foreach(['Upper','Mid','Lower'] as $o): ?>
                        <option <?= $r['ureteric_location'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="bladder_stone" id="bs"
                           <?= $r['bladder_stone'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="bs">Urinary Bladder Stone</label>
                </div>
            </div>

            <div class="col-12"><h6 class="dx-section mt-2"><i class="fas fa-male text-primary me-1"></i> Prostate / Bladder</h6></div>

            <div class="col-md-4">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="bph" id="bph" <?= $r['bph'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="bph">Benign Enlargement of Prostate</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="carcinoma_prostate" id="cap"
                           <?= $r['carcinoma_prostate'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cap">Carcinoma Prostate</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="bladder_mass" id="bm"
                           <?= $r['bladder_mass'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="bm">Urinary Bladder Mass</label>
                </div>
            </div>

            <div class="col-12"><h6 class="dx-section mt-2"><i class="fas fa-kidney text-primary me-1"></i> Upper Tract / Masses</h6></div>

            <div class="col-md-4">
                <label class="form-label">Renal Mass</label>
                <select name="renal_mass" class="form-select">
                    <?php foreach(['None','Right','Left','Bilateral'] as $o): ?>
                        <option value="<?= $o ?>" <?= $r['renal_mass'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Upper Tract Urothelial Carcinoma</label>
                <select name="utuc" class="form-select">
                    <?php foreach(['None','Right','Left','Bilateral'] as $o): ?>
                        <option value="<?= $o ?>" <?= $r['utuc'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Pelviureteric Junction Obstruction</label>
                <select name="puj_obstruction" class="form-select">
                    <?php foreach(['None','Right','Left','Bilateral'] as $o): ?>
                        <option value="<?= $o ?>" <?= $r['puj_obstruction'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12"><h6 class="dx-section mt-2"><i class="fas fa-user-md text-primary me-1"></i> Urethral / Genital</h6></div>

            <div class="col-md-3">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="stricture_urethra" id="su"
                           <?= $r['stricture_urethra'] ? 'checked' : '' ?>
                           onchange="document.getElementById('stricture_wrap').style.display=this.checked?'block':'none'">
                    <label class="form-check-label" for="su">Stricture Urethra</label>
                </div>
            </div>
            <div id="stricture_wrap" class="col-md-9"
                 style="display:<?= $r['stricture_urethra'] ? 'block' : 'none' ?>">
                <label class="form-label small">Stricture Details</label>
                <input name="stricture_details" class="form-control"
                       value="<?= htmlspecialchars($r['stricture_details']) ?>">
            </div>

            <div class="col-md-3">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="pelvic_fracture" id="pf"
                           <?= $r['pelvic_fracture'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="pf">Pelvic Fracture Urethral Injury</label>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="hypospadias" id="hyp"
                           <?= $r['hypospadias'] ? 'checked' : '' ?>
                           onchange="document.getElementById('hypo_wrap').style.display=this.checked?'block':'none'">
                    <label class="form-check-label" for="hyp">Hypospadias</label>
                </div>
            </div>
            <div id="hypo_wrap" class="col-md-6"
                 style="display:<?= $r['hypospadias'] ? 'block' : 'none' ?>">
                <label class="form-label small">Hypospadias Details</label>
                <input name="hypospadias_details" class="form-control"
                       value="<?= htmlspecialchars($r['hypospadias_details']) ?>">
            </div>

            <div class="col-12"><h6 class="dx-section mt-2"><i class="fas fa-venus-mars text-primary me-1"></i> Testicular / Penile</h6></div>

            <div class="col-md-4">
                <label class="form-label">Testicular Tumor</label>
                <select name="testicular_tumor" class="form-select">
                    <?php foreach(['None','Right','Left','Bilateral'] as $o): ?>
                        <option value="<?= $o ?>" <?= $r['testicular_tumor'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Varicocele</label>
                <select name="varicocele" class="form-select">
                    <?php foreach(['None','Right','Left','Bilateral'] as $o): ?>
                        <option value="<?= $o ?>" <?= $r['varicocele'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="carcinoma_penis" id="cp"
                           <?= $r['carcinoma_penis'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cp">Carcinoma Penis</label>
                </div>
            </div>

            <div class="col-12 mt-2">
                <label class="form-label"><i class="fas fa-ellipsis-h text-primary"></i> Others</label>
                <textarea name="others" class="form-control" rows="2"><?= htmlspecialchars($r['others']) ?></textarea>
            </div>

        </div>
        <div class="card-footer text-end">
            <a href="diagnosis.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary me-2">Cancel</a>
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update Diagnosis</button>
        </div>
    </div>
</form>

<style>
.dx-section {
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