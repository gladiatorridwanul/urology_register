<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$patient_id = intval($_GET['patient_id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
if (!$p) die("Patient not found");
$pageTitle = 'Genitourinary Examination';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $kidney   = $_POST['kidney']   ?? 'Not palpable';
    $meatus   = $_POST['meatus']   ?? 'Adequate';
    $testis   = $_POST['testis']   ?? 'Normal';
    $scrotum  = $_POST['scrotum']  ?? 'Normal';
    $penis    = $_POST['penis']    ?? 'Normal';

    $kidney_details  = ($kidney  === 'Palpable')     ? $nz($_POST['kidney_details']  ?? '') : null;
    $meatus_details  = ($meatus  === 'Not adequate') ? $nz($_POST['meatus_details']  ?? '') : null;
    $testis_details  = ($testis  === 'Abnormal')     ? $nz($_POST['testis_details']  ?? '') : null;
    $scrotum_details = ($scrotum === 'Abnormal')     ? $nz($_POST['scrotum_details'] ?? '') : null;
    $penis_details   = ($penis   === 'Abnormal')     ? $nz($_POST['penis_details']   ?? '') : null;

    $values = [
        $patient_id,
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
        $_SESSION['user_id'],
    ];
    $types = '';
    foreach ($values as $v) $types .= is_int($v) ? 'i' : 's';

    $stmt = $conn->prepare("INSERT INTO genitourinary_exam 
        (patient_id, renal_angle, kidney, kidney_details, bladder_fullness, suprapubic_tenderness, 
         hernial_orifice, meatus, meatus_details, testis, testis_details, scrotum, scrotum_details, 
         penis, penis_details, dre_prostate, anal_tone, bulbocavernosus, others, created_by) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: genitourinary_exam.php?patient_id=$patient_id&saved=1"); exit;
}
$records = $conn->query("SELECT * FROM genitourinary_exam WHERE patient_id=$patient_id ORDER BY id DESC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-male text-primary me-2"></i>Genitourinary Examination</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="../patients/view.php?id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> GU examination saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus-circle me-2 text-primary"></i>Add GU Examination</div>
        <div class="card-body">
            <div class="gu-block"><div class="gu-row">
                <div class="gu-label">Renal Angle</div>
                <div class="gu-input"><select name="renal_angle" class="form-select form-select-sm" style="max-width:200px"><option>Non-tender</option><option>Tender</option></select></div>
            </div></div>
            <div class="gu-block"><div class="gu-row">
                <div class="gu-label">Kidney</div>
                <div class="gu-input">
                    <select name="kidney" class="form-select form-select-sm" style="max-width:200px" onchange="document.getElementById('kidney_details_wrap').style.display=this.value==='Palpable'?'block':'none'">
                        <option>Not palpable</option><option>Palpable</option>
                    </select>
                    <div id="kidney_details_wrap" class="mt-2" style="display:none"><input name="kidney_details" class="form-control form-control-sm"></div>
                </div>
            </div></div>
            <div class="gu-block"><div class="gu-row">
                <div class="gu-label">Suprapubic region — bladder fullness</div>
                <div class="gu-input"><select name="bladder_fullness" class="form-select form-select-sm" style="max-width:200px"><option>Not full</option><option>Full</option></select></div>
            </div></div>
            <div class="gu-block"><div class="gu-row">
                <div class="gu-label">Suprapubic region — tenderness</div>
                <div class="gu-input"><select name="suprapubic_tenderness" class="form-select form-select-sm" style="max-width:200px"><option>Non-tender</option><option>Tender</option></select></div>
            </div></div>
            <div class="gu-block"><div class="gu-row">
                <div class="gu-label">Hernial orifice</div>
                <div class="gu-input"><select name="hernial_orifice" class="form-select form-select-sm" style="max-width:200px"><option>Intact</option><option>Not intact</option></select></div>
            </div></div>
            <div class="gu-block"><div class="gu-row">
                <div class="gu-label">External urethral meatus</div>
                <div class="gu-input">
                    <select name="meatus" class="form-select form-select-sm" style="max-width:200px" onchange="document.getElementById('meatus_details_wrap').style.display=this.value==='Not adequate'?'block':'none'">
                        <option>Adequate</option><option>Not adequate</option>
                    </select>
                    <div id="meatus_details_wrap" class="mt-2" style="display:none"><input name="meatus_details" class="form-control form-control-sm"></div>
                </div>
            </div></div>
            <div class="gu-block"><div class="gu-row">
                <div class="gu-label">Testis</div>
                <div class="gu-input">
                    <select name="testis" class="form-select form-select-sm" style="max-width:200px" onchange="document.getElementById('testis_details_wrap').style.display=this.value==='Abnormal'?'block':'none'">
                        <option>Normal</option><option>Abnormal</option>
                    </select>
                    <div id="testis_details_wrap" class="mt-2" style="display:none"><input name="testis_details" class="form-control form-control-sm"></div>
                </div>
            </div></div>
            <div class="gu-block"><div class="gu-row">
                <div class="gu-label">Scrotum</div>
                <div class="gu-input">
                    <select name="scrotum" class="form-select form-select-sm" style="max-width:200px" onchange="document.getElementById('scrotum_details_wrap').style.display=this.value==='Abnormal'?'block':'none'">
                        <option>Normal</option><option>Abnormal</option>
                    </select>
                    <div id="scrotum_details_wrap" class="mt-2" style="display:none"><input name="scrotum_details" class="form-control form-control-sm"></div>
                </div>
            </div></div>
            <div class="gu-block"><div class="gu-row">
                <div class="gu-label">Penis</div>
                <div class="gu-input">
                    <select name="penis" class="form-select form-select-sm" style="max-width:200px" onchange="document.getElementById('penis_details_wrap').style.display=this.value==='Abnormal'?'block':'none'">
                        <option>Normal</option><option>Abnormal</option>
                    </select>
                    <div id="penis_details_wrap" class="mt-2" style="display:none"><input name="penis_details" class="form-control form-control-sm"></div>
                </div>
            </div></div>
            <div class="gu-block"><div class="gu-row">
                <div class="gu-label">Digital rectal examination — prostate</div>
                <div class="gu-input"><input name="dre_prostate" class="form-control form-control-sm"></div>
            </div></div>
            <div class="gu-block"><div class="gu-row">
                <div class="gu-label">Anal tone</div>
                <div class="gu-input"><input name="anal_tone" class="form-control form-control-sm"></div>
            </div></div>
            <div class="gu-block"><div class="gu-row">
                <div class="gu-label">Bulbocavernosus reflex</div>
                <div class="gu-input"><input name="bulbocavernosus" class="form-control form-control-sm"></div>
            </div></div>
            <div class="gu-block"><div class="gu-row">
                <div class="gu-label">Others</div>
                <div class="gu-input"><textarea name="others" class="form-control form-control-sm" rows="2"></textarea></div>
            </div></div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save GU Examination</button>
        </div>
    </div>
</form>

<!-- ================= SAVED RECORDS — FULL DETAIL ================= -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2 text-primary"></i>Saved Records (<?= $records->num_rows ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle full-rec-table">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Date</th>
                    <th>Renal Angle</th>
                    <th>Kidney</th>
                    <th>Kidney Details</th>
                    <th>Bladder Fullness</th>
                    <th>Suprapubic Tenderness</th>
                    <th>Hernial Orifice</th>
                    <th>Meatus</th>
                    <th>Meatus Details</th>
                    <th>Testis</th>
                    <th>Testis Details</th>
                    <th>Scrotum</th>
                    <th>Scrotum Details</th>
                    <th>Penis</th>
                    <th>Penis Details</th>
                    <th>DRE Prostate</th>
                    <th>Anal Tone</th>
                    <th>Bulbocavernosus</th>
                    <th>Others</th>
                    <th style="width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= date('d-m-Y', strtotime($r['created_at'])) ?></td>
                    <td><?= htmlspecialchars($r['renal_angle']) ?></td>
                    <td><?= htmlspecialchars($r['kidney']) ?></td>
                    <td><?= htmlspecialchars($r['kidney_details'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['bladder_fullness']) ?></td>
                    <td><?= htmlspecialchars($r['suprapubic_tenderness']) ?></td>
                    <td><?= htmlspecialchars($r['hernial_orifice']) ?></td>
                    <td><?= htmlspecialchars($r['meatus']) ?></td>
                    <td><?= htmlspecialchars($r['meatus_details'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['testis']) ?></td>
                    <td><?= htmlspecialchars($r['testis_details'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['scrotum']) ?></td>
                    <td><?= htmlspecialchars($r['scrotum_details'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['penis']) ?></td>
                    <td><?= htmlspecialchars($r['penis_details'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['dre_prostate'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['anal_tone'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['bulbocavernosus'] ?: '—') ?></td>
                    <td><?= nl2br(htmlspecialchars($r['others'] ?: '—')) ?></td>
                    <?= rowActions('genitourinary_exam', $r['id']) ?>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="21" class="text-center text-muted py-3">No records yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<style>
.gu-block { border-bottom: 1px solid #eef2f7; padding: 10px 0; }
.gu-block:last-child { border-bottom: none; }
.gu-row { display: grid; grid-template-columns: 280px 1fr; gap: 16px; align-items: start; }
.gu-label { font-weight: 600; color: #1e293b; font-size: 13px; line-height: 1.5; }
.gu-input { min-width: 0; }
.full-rec-table { font-size: 11.5px; }
.full-rec-table th { white-space: nowrap; font-size: 10.5px; background: #f1f5f9; }
.full-rec-table td { vertical-align: top; max-width: 150px; }
@media (max-width: 768px) {
    .gu-row { grid-template-columns: 1fr; gap: 6px; }
    .gu-input select { max-width: 100% !important; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>