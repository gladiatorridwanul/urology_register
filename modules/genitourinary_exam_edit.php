<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT * FROM genitourinary_exam WHERE id=$id")->fetch_assoc();
if (!$r) die("Record not found");
$patient_id = $r['patient_id'];
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
$pageTitle = 'Edit GU Exam';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $kidney  = $_POST['kidney']  ?? 'Not palpable';
    $meatus  = $_POST['meatus']  ?? 'Adequate';
    $testis  = $_POST['testis']  ?? 'Normal';
    $scrotum = $_POST['scrotum'] ?? 'Normal';
    $penis   = $_POST['penis']   ?? 'Normal';

    $kidney_details  = ($kidney  === 'Palpable')     ? $nz($_POST['kidney_details']  ?? '') : null;
    $meatus_details  = ($meatus  === 'Not adequate') ? $nz($_POST['meatus_details']  ?? '') : null;
    $testis_details  = ($testis  === 'Abnormal')     ? $nz($_POST['testis_details']  ?? '') : null;
    $scrotum_details = ($scrotum === 'Abnormal')     ? $nz($_POST['scrotum_details'] ?? '') : null;
    $penis_details   = ($penis   === 'Abnormal')     ? $nz($_POST['penis_details']   ?? '') : null;

    $values = [
        $_POST['renal_angle'] ?? 'Non-tender',
        $kidney, $kidney_details,
        $_POST['bladder_fullness'] ?? 'Not full',
        $_POST['suprapubic_tenderness'] ?? 'Non-tender',
        $_POST['hernial_orifice'] ?? 'Intact',
        $meatus, $meatus_details,
        $testis, $testis_details,
        $scrotum, $scrotum_details,
        $penis, $penis_details,
        $nz($_POST['dre_prostate'] ?? ''),
        $nz($_POST['anal_tone'] ?? ''),
        $nz($_POST['bulbocavernosus'] ?? ''),
        $nz($_POST['others'] ?? ''),
        $id,
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("UPDATE genitourinary_exam SET 
        renal_angle=?, kidney=?, kidney_details=?, bladder_fullness=?, suprapubic_tenderness=?, 
        hernial_orifice=?, meatus=?, meatus_details=?, testis=?, testis_details=?, 
        scrotum=?, scrotum_details=?, penis=?, penis_details=?, 
        dre_prostate=?, anal_tone=?, bulbocavernosus=?, others=? 
        WHERE id=?");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: genitourinary_exam.php?patient_id=$patient_id&saved=1"); exit;
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Edit GU Exam</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="genitourinary_exam.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<form method="POST">
    <div class="card">
        <div class="card-body">

            <!-- Renal Angle -->
            <div class="gu-block">
                <div class="gu-row">
                    <div class="gu-label">Renal Angle</div>
                    <div class="gu-input">
                        <select name="renal_angle" class="form-select form-select-sm" style="max-width:200px">
                            <?php foreach(['Non-tender','Tender'] as $o): ?>
                                <option <?= $r['renal_angle'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Kidney -->
            <div class="gu-block">
                <div class="gu-row">
                    <div class="gu-label">Kidney</div>
                    <div class="gu-input">
                        <select name="kidney" class="form-select form-select-sm" style="max-width:200px"
                                onchange="document.getElementById('kidney_details_wrap').style.display=this.value==='Palpable'?'block':'none'">
                            <?php foreach(['Not palpable','Palpable'] as $o): ?>
                                <option <?= $r['kidney'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="kidney_details_wrap" class="mt-2"
                             style="display:<?= $r['kidney'] === 'Palpable' ? 'block' : 'none' ?>">
                            <input name="kidney_details" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($r['kidney_details']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bladder fullness -->
            <div class="gu-block">
                <div class="gu-row">
                    <div class="gu-label">Bladder Fullness</div>
                    <div class="gu-input">
                        <select name="bladder_fullness" class="form-select form-select-sm" style="max-width:200px">
                            <?php foreach(['Not full','Full'] as $o): ?>
                                <option <?= $r['bladder_fullness'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Suprapubic tenderness -->
            <div class="gu-block">
                <div class="gu-row">
                    <div class="gu-label">Suprapubic Tenderness</div>
                    <div class="gu-input">
                        <select name="suprapubic_tenderness" class="form-select form-select-sm" style="max-width:200px">
                            <?php foreach(['Non-tender','Tender'] as $o): ?>
                                <option <?= $r['suprapubic_tenderness'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Hernial orifice -->
            <div class="gu-block">
                <div class="gu-row">
                    <div class="gu-label">Hernial Orifice</div>
                    <div class="gu-input">
                        <select name="hernial_orifice" class="form-select form-select-sm" style="max-width:200px">
                            <?php foreach(['Intact','Not intact'] as $o): ?>
                                <option <?= $r['hernial_orifice'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Meatus -->
            <div class="gu-block">
                <div class="gu-row">
                    <div class="gu-label">External Urethral Meatus</div>
                    <div class="gu-input">
                        <select name="meatus" class="form-select form-select-sm" style="max-width:200px"
                                onchange="document.getElementById('meatus_details_wrap').style.display=this.value==='Not adequate'?'block':'none'">
                            <?php foreach(['Adequate','Not adequate'] as $o): ?>
                                <option <?= $r['meatus'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="meatus_details_wrap" class="mt-2"
                             style="display:<?= $r['meatus'] === 'Not adequate' ? 'block' : 'none' ?>">
                            <input name="meatus_details" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($r['meatus_details']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Testis -->
            <div class="gu-block">
                <div class="gu-row">
                    <div class="gu-label">Testis</div>
                    <div class="gu-input">
                        <select name="testis" class="form-select form-select-sm" style="max-width:200px"
                                onchange="document.getElementById('testis_details_wrap').style.display=this.value==='Abnormal'?'block':'none'">
                            <?php foreach(['Normal','Abnormal'] as $o): ?>
                                <option <?= $r['testis'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="testis_details_wrap" class="mt-2"
                             style="display:<?= $r['testis'] === 'Abnormal' ? 'block' : 'none' ?>">
                            <input name="testis_details" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($r['testis_details']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scrotum -->
            <div class="gu-block">
                <div class="gu-row">
                    <div class="gu-label">Scrotum</div>
                    <div class="gu-input">
                        <select name="scrotum" class="form-select form-select-sm" style="max-width:200px"
                                onchange="document.getElementById('scrotum_details_wrap').style.display=this.value==='Abnormal'?'block':'none'">
                            <?php foreach(['Normal','Abnormal'] as $o): ?>
                                <option <?= $r['scrotum'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="scrotum_details_wrap" class="mt-2"
                             style="display:<?= $r['scrotum'] === 'Abnormal' ? 'block' : 'none' ?>">
                            <input name="scrotum_details" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($r['scrotum_details']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Penis -->
            <div class="gu-block">
                <div class="gu-row">
                    <div class="gu-label">Penis</div>
                    <div class="gu-input">
                        <select name="penis" class="form-select form-select-sm" style="max-width:200px"
                                onchange="document.getElementById('penis_details_wrap').style.display=this.value==='Abnormal'?'block':'none'">
                            <?php foreach(['Normal','Abnormal'] as $o): ?>
                                <option <?= $r['penis'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="penis_details_wrap" class="mt-2"
                             style="display:<?= $r['penis'] === 'Abnormal' ? 'block' : 'none' ?>">
                            <input name="penis_details" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($r['penis_details']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- DRE / Anal tone / Bulbocavernosus -->
            <div class="gu-block">
                <div class="gu-row">
                    <div class="gu-label">Digital rectal examination — prostate</div>
                    <div class="gu-input">
                        <input name="dre_prostate" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($r['dre_prostate']) ?>">
                    </div>
                </div>
            </div>

            <div class="gu-block">
                <div class="gu-row">
                    <div class="gu-label">Anal tone</div>
                    <div class="gu-input">
                        <input name="anal_tone" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($r['anal_tone']) ?>">
                    </div>
                </div>
            </div>

            <div class="gu-block">
                <div class="gu-row">
                    <div class="gu-label">Bulbocavernosus reflex</div>
                    <div class="gu-input">
                        <input name="bulbocavernosus" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($r['bulbocavernosus']) ?>">
                    </div>
                </div>
            </div>

            <div class="gu-block">
                <div class="gu-row">
                    <div class="gu-label">Others</div>
                    <div class="gu-input">
                        <textarea name="others" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($r['others']) ?></textarea>
                    </div>
                </div>
            </div>

        </div>
        <div class="card-footer text-end">
            <a href="genitourinary_exam.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary me-2">Cancel</a>
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update</button>
        </div>
    </div>
</form>

<style>
.gu-block { border-bottom: 1px solid #eef2f7; padding: 10px 0; }
.gu-block:last-child { border-bottom: none; }
.gu-row { display: grid; grid-template-columns: 280px 1fr; gap: 16px; align-items: start; }
.gu-label { font-weight: 600; color: #1e293b; font-size: 13px; line-height: 1.5; }
.gu-input { min-width: 0; }
@media (max-width: 768px) {
    .gu-row { grid-template-columns: 1fr; gap: 6px; }
    .gu-input select { max-width: 100% !important; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>