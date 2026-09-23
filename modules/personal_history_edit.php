<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT * FROM personal_history WHERE id=$id")->fetch_assoc();
if (!$r) die("Record not found");
$patient_id = $r['patient_id'];
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
$pageTitle = 'Edit Personal History';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $smoking = $_POST['smoking'] ?? 'Never';
    $alcohol = $_POST['alcohol'] ?? 'No';
    $betel   = $_POST['betel']   ?? 'No';

    $smoking_details = ($smoking !== 'Never') ? $nz($_POST['smoking_details'] ?? '') : null;
    $alcohol_details = ($alcohol === 'Yes')    ? $nz($_POST['alcohol_details'] ?? '') : null;
    $betel_details   = ($betel   === 'Yes')    ? $nz($_POST['betel_details'] ?? '')   : null;

    $others = $nz($_POST['others'] ?? '');

    $values = [
        $smoking,          // 1 s
        $smoking_details,  // 2 s
        $alcohol,          // 3 s
        $alcohol_details,  // 4 s
        $betel,            // 5 s
        $betel_details,    // 6 s
        $others,           // 7 s
        $id,               // 8 i
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("UPDATE personal_history SET 
        smoking=?, smoking_details=?, 
        alcohol=?, alcohol_details=?, 
        betel=?, betel_details=?, 
        others=? 
        WHERE id=?");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: personal_history.php?patient_id=$patient_id&saved=1");
    exit;
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Edit Personal History</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="personal_history.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<form method="POST">
    <div class="card">
        <div class="card-body">

            <!-- Smoking -->
            <div class="ph-block">
                <div class="ph-row">
                    <div class="ph-label">
                        <i class="fas fa-smoking text-primary"></i> Smoking
                    </div>
                    <div class="ph-input">
                        <select name="smoking" class="form-select form-select-sm"
                                onchange="document.getElementById('smoking_details_wrap').style.display=this.value!=='Never'?'block':'none'">
                            <?php foreach (['Never','Current','Former'] as $o): ?>
                                <option <?= $r['smoking'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="smoking_details_wrap" class="mt-2"
                             style="display:<?= ($r['smoking'] !== 'Never') ? 'block' : 'none' ?>">
                            <input name="smoking_details" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($r['smoking_details']) ?>"
                                   placeholder="e.g. 10 cigarettes/day for 5 years">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alcohol -->
            <div class="ph-block">
                <div class="ph-row">
                    <div class="ph-label">
                        <i class="fas fa-wine-glass text-primary"></i> Alcohol
                    </div>
                    <div class="ph-input">
                        <select name="alcohol" class="form-select form-select-sm"
                                onchange="document.getElementById('alcohol_details_wrap').style.display=this.value==='Yes'?'block':'none'">
                            <?php foreach (['No','Yes'] as $o): ?>
                                <option <?= $r['alcohol'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="alcohol_details_wrap" class="mt-2"
                             style="display:<?= ($r['alcohol'] === 'Yes') ? 'block' : 'none' ?>">
                            <input name="alcohol_details" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($r['alcohol_details']) ?>"
                                   placeholder="e.g. Occasional, weekly, daily">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Betel -->
            <div class="ph-block">
                <div class="ph-row">
                    <div class="ph-label">
                        <i class="fas fa-leaf text-primary"></i> Betel Nut
                    </div>
                    <div class="ph-input">
                        <select name="betel" class="form-select form-select-sm"
                                onchange="document.getElementById('betel_details_wrap').style.display=this.value==='Yes'?'block':'none'">
                            <?php foreach (['No','Yes'] as $o): ?>
                                <option <?= $r['betel'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="betel_details_wrap" class="mt-2"
                             style="display:<?= ($r['betel'] === 'Yes') ? 'block' : 'none' ?>">
                            <input name="betel_details" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($r['betel_details']) ?>"
                                   placeholder="e.g. 3–4 times/day for 10 years">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Others -->
            <div class="ph-block">
                <div class="ph-row">
                    <div class="ph-label"><i class="fas fa-ellipsis-h text-primary"></i> Others</div>
                    <div class="ph-input">
                        <textarea name="others" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($r['others']) ?></textarea>
                    </div>
                </div>
            </div>

        </div>
        <div class="card-footer text-end">
            <a href="personal_history.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary me-2">Cancel</a>
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update</button>
        </div>
    </div>
</form>

<style>
.ph-block { border-bottom: 1px solid #eef2f7; padding: 12px 0; }
.ph-block:last-child { border-bottom: none; }
.ph-row { display: grid; grid-template-columns: 220px 1fr; gap: 16px; align-items: start; }
.ph-label { font-weight: 600; color: #1e293b; font-size: 13px; line-height: 1.5; }
.ph-input { min-width: 0; }
.ph-input .form-select { max-width: 200px; }
@media (max-width: 768px) {
    .ph-row { grid-template-columns: 1fr; gap: 6px; }
    .ph-input .form-select { max-width: 100%; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>