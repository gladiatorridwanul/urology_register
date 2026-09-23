<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT * FROM general_exam WHERE id=$id")->fetch_assoc();
if (!$r) die("Record not found");
$patient_id = $r['patient_id'];
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
$pageTitle = 'Edit General Exam';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $anaemia  = $_POST['anaemia']  ?? 'Absent';
    $jaundice = $_POST['jaundice'] ?? 'Absent';
    $edema    = $_POST['edema']    ?? 'Absent';

    $anaemia_details  = ($anaemia  === 'Present') ? $nz($_POST['anaemia_details']  ?? '') : null;
    $jaundice_details = ($jaundice === 'Present') ? $nz($_POST['jaundice_details'] ?? '') : null;
    $edema_details    = ($edema    === 'Present') ? $nz($_POST['edema_details']    ?? '') : null;

    $values = [
        $anaemia, $anaemia_details,
        $jaundice, $jaundice_details,
        $edema, $edema_details,
        $nz($_POST['pulse'] ?? ''),
        $nz($_POST['bp'] ?? ''),
        $nz($_POST['temperature'] ?? ''),
        $nz($_POST['lymph_node'] ?? ''),
        $nz($_POST['others'] ?? ''),
        $id,
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("UPDATE general_exam SET 
        anaemia=?, anaemia_details=?, 
        jaundice=?, jaundice_details=?, 
        edema=?, edema_details=?, 
        pulse=?, bp=?, temperature=?, 
        lymph_node=?, others=? 
        WHERE id=?");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: general_exam.php?patient_id=$patient_id&saved=1"); exit;
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Edit General Exam</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="general_exam.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<form method="POST">
    <div class="card">
        <div class="card-body">

            <div class="ge-block">
                <div class="ge-row">
                    <div class="ge-label">Anaemia</div>
                    <div class="ge-input">
                        <select name="anaemia" class="form-select form-select-sm" style="max-width:200px"
                                onchange="document.getElementById('anaemia_details_wrap').style.display=this.value==='Present'?'block':'none'">
                            <?php foreach(['Absent','Present'] as $o): ?>
                                <option <?= $r['anaemia'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="anaemia_details_wrap" class="mt-2"
                             style="display:<?= $r['anaemia'] === 'Present' ? 'block' : 'none' ?>">
                            <input name="anaemia_details" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($r['anaemia_details']) ?>"
                                   placeholder="Specify">
                        </div>
                    </div>
                </div>
            </div>

            <div class="ge-block">
                <div class="ge-row">
                    <div class="ge-label">Jaundice</div>
                    <div class="ge-input">
                        <select name="jaundice" class="form-select form-select-sm" style="max-width:200px"
                                onchange="document.getElementById('jaundice_details_wrap').style.display=this.value==='Present'?'block':'none'">
                            <?php foreach(['Absent','Present'] as $o): ?>
                                <option <?= $r['jaundice'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="jaundice_details_wrap" class="mt-2"
                             style="display:<?= $r['jaundice'] === 'Present' ? 'block' : 'none' ?>">
                            <input name="jaundice_details" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($r['jaundice_details']) ?>"
                                   placeholder="Specify">
                        </div>
                    </div>
                </div>
            </div>

            <div class="ge-block">
                <div class="ge-row">
                    <div class="ge-label">Edema</div>
                    <div class="ge-input">
                        <select name="edema" class="form-select form-select-sm" style="max-width:200px"
                                onchange="document.getElementById('edema_details_wrap').style.display=this.value==='Present'?'block':'none'">
                            <?php foreach(['Absent','Present'] as $o): ?>
                                <option <?= $r['edema'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="edema_details_wrap" class="mt-2"
                             style="display:<?= $r['edema'] === 'Present' ? 'block' : 'none' ?>">
                            <input name="edema_details" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($r['edema_details']) ?>"
                                   placeholder="Specify">
                        </div>
                    </div>
                </div>
            </div>

            <div class="ge-block">
                <div class="ge-row">
                    <div class="ge-label">Pulse / BP / Temperature</div>
                    <div class="ge-input">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small mb-0">Pulse (beats/min)</label>
                                <input type="number" name="pulse" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($r['pulse']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-0">BP (mmHg)</label>
                                <input name="bp" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($r['bp']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-0">Temperature</label>
                                <input name="temperature" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($r['temperature']) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ge-block">
                <div class="ge-row">
                    <div class="ge-label">Lymph Node Status</div>
                    <div class="ge-input">
                        <input name="lymph_node" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($r['lymph_node']) ?>">
                    </div>
                </div>
            </div>

            <div class="ge-block">
                <div class="ge-row">
                    <div class="ge-label">Others</div>
                    <div class="ge-input">
                        <textarea name="others" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($r['others']) ?></textarea>
                    </div>
                </div>
            </div>

        </div>
        <div class="card-footer text-end">
            <a href="general_exam.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary me-2">Cancel</a>
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update</button>
        </div>
    </div>
</form>

<style>
.ge-block { border-bottom: 1px solid #eef2f7; padding: 12px 0; }
.ge-block:last-child { border-bottom: none; }
.ge-row { display: grid; grid-template-columns: 260px 1fr; gap: 16px; align-items: start; }
.ge-label { font-weight: 600; color: #1e293b; font-size: 13px; line-height: 1.5; }
.ge-input { min-width: 0; }
@media (max-width: 768px) {
    .ge-row { grid-template-columns: 1fr; gap: 6px; }
    .ge-input select { max-width: 100% !important; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>