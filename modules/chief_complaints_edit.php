<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
$r = $conn->query("SELECT * FROM chief_complaints WHERE id=$id")->fetch_assoc();
if (!$r) die("Record not found");
$patient_id = $r['patient_id'];
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
$pageTitle = 'Edit Chief Complaint';

$LUTS_OPTIONS = [
    'Hesitancy','Straining','Weak stream','Intermittency','Incomplete emptying',
    'Frequency','Urgency','Nocturia','Urge incontinence','Dysuria',
    'Terminal dribble','Sense of incomplete emptying','Burning micturition'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lutsArr = $_POST['luts'] ?? [];
    $luts = is_array($lutsArr) ? implode(', ', array_filter($lutsArr)) : '';

    $nz = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim((string)$v) : null;

    $retention_present = ($_POST['retention_present'] ?? 'No') === 'Yes' ? 1 : 0;
    $retention         = $retention_present ? ($_POST['retention'] ?? 'Acute') : 'None';
    $retention_duration = $retention_present ? $nz($_POST['retention_duration'] ?? '') : null;

    $fever_present = ($_POST['fever_present'] ?? 'No') === 'Yes' ? 1 : 0;
    $fever         = $fever_present ? 'Yes' : 'No';
    $fever_details = $fever_present ? $nz($_POST['fever_details'] ?? '') : null;
    $fever_duration = $fever_present ? $nz($_POST['fever_duration'] ?? '') : null;

    // ORDER MUST MATCH THE UPDATE SET CLAUSE
    $values = [
        $luts,                                    // 1  s
        $nz($_POST['luts_specify'] ?? ''),        // 2  s
        $nz($_POST['luts_duration'] ?? ''),       // 3  s
        $retention,                               // 4  s
        $retention_present,                       // 5  i
        $retention_duration,                      // 6  s
        $nz($_POST['pain'] ?? ''),                // 7  s
        $nz($_POST['pain_duration'] ?? ''),       // 8  s
        $nz($_POST['hematuria'] ?? ''),           // 9  s
        $nz($_POST['hematuria_duration'] ?? ''),  // 10 s
        $fever,                                   // 11 s
        $fever_present,                           // 12 i
        $fever_details,                           // 13 s
        $fever_duration,                          // 14 s
        $nz($_POST['others'] ?? ''),              // 15 s
        $nz($_POST['others_duration'] ?? ''),     // 16 s
        $id,                                      // 17 i
    ];

    $types = '';
    foreach ($values as $v) {
        $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
    }

    $sql = "UPDATE chief_complaints SET 
        luts=?, luts_specify=?, luts_duration=?,
        retention=?, retention_present=?, retention_duration=?,
        pain=?, pain_duration=?,
        hematuria=?, hematuria_duration=?,
        fever=?, fever_present=?, fever_details=?, fever_duration=?,
        others=?, others_duration=?
        WHERE id=?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) die("Prepare failed: " . $conn->error);

    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    header("Location: chief_complaints.php?patient_id=$patient_id&saved=1");
    exit;
}

// Existing LUTS as array
$existingLuts = array_map('trim', explode(',', $r['luts'] ?? ''));

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Edit Chief Complaint</h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?> (<?= $p['uro_id'] ?>)</small>
    </div>
    <a href="chief_complaints.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<form method="POST">
    <div class="card">
        <div class="card-body">

            <!-- ============ LUTS ============ -->
            <div class="cc-block">
                <div class="cc-row">
                    <div class="cc-label">
                        <i class="fas fa-stream text-primary"></i>
                        Lower Urinary Tract Symptoms (LUTS)
                        <small class="text-muted d-block">Multi-select</small>
                    </div>
                    <div class="cc-input">
                        <div class="luts-grid">
                            <?php foreach ($LUTS_OPTIONS as $opt):
                                $checked = in_array($opt, $existingLuts, true);
                            ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="luts[]"
                                           id="luts_<?= md5($opt) ?>" value="<?= htmlspecialchars($opt) ?>"
                                           <?= $checked ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="luts_<?= md5($opt) ?>"><?= htmlspecialchars($opt) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-8">
                                <label class="form-label small">Specify (additional LUTS)</label>
                                <input name="luts_specify" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($r['luts_specify']) ?>" placeholder="Free text">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Duration</label>
                                <input name="luts_duration" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($r['luts_duration']) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ RETENTION ============ -->
            <div class="cc-block">
                <div class="cc-row">
                    <div class="cc-label"><i class="fas fa-tint text-primary"></i> Retention of Urine</div>
                    <div class="cc-input">
                        <div class="yn-group">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="retention_present"
                                       id="retention_no" value="No"
                                       <?= !$r['retention_present'] ? 'checked' : '' ?>
                                       onchange="document.getElementById('retention_extra').style.display='none'">
                                <label class="form-check-label" for="retention_no">No</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="retention_present"
                                       id="retention_yes" value="Yes"
                                       <?= $r['retention_present'] ? 'checked' : '' ?>
                                       onchange="document.getElementById('retention_extra').style.display='flex'">
                                <label class="form-check-label" for="retention_yes">Yes</label>
                            </div>
                        </div>
                        <div id="retention_extra" class="yn-extra"
                             style="display:<?= $r['retention_present'] ? 'flex' : 'none' ?>">
                            <label class="form-label small mb-0">Type</label>
                            <select name="retention" class="form-select form-select-sm"
                                    style="width:auto;min-width:160px">
                                <option <?= $r['retention'] === 'Acute' ? 'selected' : '' ?>>Acute</option>
                                <option <?= $r['retention'] === 'Chronic' ? 'selected' : '' ?>>Chronic</option>
                            </select>
                            <input name="retention_duration" class="form-control form-control-sm"
                                   style="width:180px"
                                   value="<?= htmlspecialchars($r['retention_duration']) ?>"
                                   placeholder="Duration (optional)">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ PAIN ============ -->
            <div class="cc-block">
                <div class="cc-row">
                    <div class="cc-label"><i class="fas fa-bolt text-primary"></i> Pain</div>
                    <div class="cc-input">
                        <div class="row g-2">
                            <div class="col-md-8">
                                <input name="pain" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($r['pain']) ?>">
                            </div>
                            <div class="col-md-4">
                                <input name="pain_duration" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($r['pain_duration']) ?>" placeholder="Duration">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ HEMATURIA ============ -->
            <div class="cc-block">
                <div class="cc-row">
                    <div class="cc-label"><i class="fas fa-tint text-danger"></i> Hematuria</div>
                    <div class="cc-input">
                        <div class="row g-2">
                            <div class="col-md-8">
                                <input name="hematuria" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($r['hematuria']) ?>">
                            </div>
                            <div class="col-md-4">
                                <input name="hematuria_duration" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($r['hematuria_duration']) ?>" placeholder="Duration">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ FEVER ============ -->
            <div class="cc-block">
                <div class="cc-row">
                    <div class="cc-label"><i class="fas fa-thermometer-half text-primary"></i> Fever</div>
                    <div class="cc-input">
                        <div class="yn-group">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="fever_present"
                                       id="fever_no" value="No"
                                       <?= !$r['fever_present'] ? 'checked' : '' ?>
                                       onchange="document.getElementById('fever_extra').style.display='none'">
                                <label class="form-check-label" for="fever_no">No</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="fever_present"
                                       id="fever_yes" value="Yes"
                                       <?= $r['fever_present'] ? 'checked' : '' ?>
                                       onchange="document.getElementById('fever_extra').style.display='flex'">
                                <label class="form-check-label" for="fever_yes">Yes</label>
                            </div>
                        </div>
                        <div id="fever_extra" class="yn-extra"
                             style="display:<?= $r['fever_present'] ? 'flex' : 'none' ?>">
                            <label class="form-label small mb-0">Specify</label>
                            <input name="fever_details" class="form-control form-control-sm"
                                   style="min-width:260px"
                                   value="<?= htmlspecialchars($r['fever_details']) ?>"
                                   placeholder="Details of fever">
                            <input name="fever_duration" class="form-control form-control-sm"
                                   style="width:180px"
                                   value="<?= htmlspecialchars($r['fever_duration']) ?>"
                                   placeholder="Duration (optional)">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ OTHERS ============ -->
            <div class="cc-block">
                <div class="cc-row">
                    <div class="cc-label"><i class="fas fa-ellipsis-h text-primary"></i> Others</div>
                    <div class="cc-input">
                        <div class="row g-2">
                            <div class="col-md-8">
                                <input name="others" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($r['others']) ?>">
                            </div>
                            <div class="col-md-4">
                                <input name="others_duration" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($r['others_duration']) ?>" placeholder="Duration">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="card-footer text-end">
            <a href="chief_complaints.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary me-2">Cancel</a>
            <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update</button>
        </div>
    </div>
</form>

<style>
.cc-block { border-bottom: 1px solid #eef2f7; padding: 12px 0; }
.cc-block:last-child { border-bottom: none; }
.cc-row { display: grid; grid-template-columns: 240px 1fr; gap: 16px; align-items: start; }
.cc-label { font-weight: 600; color: #1e293b; font-size: 13px; line-height: 1.5; }
.cc-input { min-width: 0; }

.luts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 4px 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 10px 12px;
}
.luts-grid .form-check-label { font-size: 12.5px; }

.yn-group {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 4px 0;
}
.yn-group .form-check { margin: 0; }
.yn-group .form-check-label { font-size: 13px; font-weight: 500; }

.yn-extra {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 8px;
    padding: 10px 12px;
    background: #f1f5f9;
    border-left: 3px solid #2563eb;
    border-radius: 6px;
}

@media (max-width: 768px) {
    .cc-row { grid-template-columns: 1fr; gap: 6px; }
    .luts-grid { grid-template-columns: 1fr 1fr; }
    .yn-extra { flex-direction: column; align-items: stretch; }
    .yn-extra select, .yn-extra input { width: 100% !important; min-width: 0 !important; }
}
@media (max-width: 480px) {
    .luts-grid { grid-template-columns: 1fr; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>