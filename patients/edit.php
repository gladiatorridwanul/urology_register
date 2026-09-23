<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$pageTitle = 'Edit Patient';
$id = intval($_GET['id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$id")->fetch_assoc();
if (!$p) die("Patient not found");
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discharge_date    = !empty($_POST['discharge_date'])       ? $_POST['discharge_date']    : null;
    $dob               = !empty($_POST['dob'])                  ? $_POST['dob']               : null;
    $first_admission   = !empty($_POST['first_admission_date']) ? $_POST['first_admission_date'] : null;
    $current_visit     = !empty($_POST['current_visit_date'])   ? $_POST['current_visit_date']   : null;
    $age               = !empty($_POST['age'])                  ? (int)$_POST['age']          : null;
    $hospital_reg_no   = !empty($_POST['hospital_reg_no'])      ? $_POST['hospital_reg_no']   : null;
    $nid               = !empty($_POST['nid'])                  ? $_POST['nid']               : null;
    $blood_group       = !empty($_POST['blood_group'])          ? $_POST['blood_group']       : null;
    $mode_of_admission = !empty($_POST['mode_of_admission'])    ? $_POST['mode_of_admission'] : null;
    $guardian_name     = !empty($_POST['guardian_name'])        ? $_POST['guardian_name']     : null;
    $occupation        = !empty($_POST['occupation'])           ? $_POST['occupation']        : null;
    $address           = !empty($_POST['address'])              ? $_POST['address']           : null;
    $sex               = !empty($_POST['sex'])                  ? $_POST['sex']               : null;
    $mobile            = !empty($_POST['mobile'])               ? $_POST['mobile']            : null;

    $unit_id = intval($_POST['unit_id'] ?? 0) ?: null;
    $ward_id = intval($_POST['ward_id'] ?? 0) ?: null;

    // Bed dual-mode
    $bed_source = $_POST['bed_source'] ?? 'dropdown';
    $bed_id     = null;
    $bedName    = null;

    if ($bed_source === 'manual') {
        $bedName = trim($_POST['bed_manual'] ?? '');
        $bedName = $bedName !== '' ? $bedName : null;
    } else {
        $bed_id = intval($_POST['bed_id'] ?? 0) ?: null;
        if ($bed_id) {
            $r = $conn->query("SELECT name FROM beds WHERE id=$bed_id")->fetch_assoc();
            $bedName = $r['name'] ?? null;
        }
    }

    // Fetch unit / ward names
    $unitName = $wardName = null;
    if ($unit_id) { $r = $conn->query("SELECT name FROM units WHERE id=$unit_id")->fetch_assoc(); $unitName = $r['name'] ?? null; }
    if ($ward_id) { $r = $conn->query("SELECT name FROM wards WHERE id=$ward_id")->fetch_assoc(); $wardName = $r['name'] ?? null; }

    if (!$age && $dob) {
        $diff = (time() - strtotime($dob)) / (365.25 * 24 * 3600);
        $age = (int)$diff;
    }

    $stmt = $conn->prepare("UPDATE patients SET 
        name=?, unit=?, unit_id=?, ward=?, ward_id=?, bed=?, bed_id=?,
        age=?, dob=?, sex=?, guardian_name=?, occupation=?, address=?, mobile=?, nid=?,
        first_admission_date=?, current_visit_date=?, blood_group=?, hospital_reg_no=?,
        mode_of_admission=?, discharge_date=? WHERE id=?");
    $stmt->bind_param('sssisisissssssssssssssi',
        $_POST['name'], $unitName, $unit_id, $wardName, $ward_id, $bedName, $bed_id,
        $age, $dob, $sex, $guardian_name, $occupation, $address, $mobile, $nid,
        $first_admission, $current_visit, $blood_group, $hospital_reg_no,
        $mode_of_admission, $discharge_date, $id
    );
    if ($stmt->execute()) {
        header("Location: view.php?id=$id&updated=1"); exit;
    } else {
        $error = "Error: " . $conn->error;
    }
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$isActive = empty($p['discharge_date']) || $p['discharge_date'] === '0000-00-00';

// Decide default bed source: if bed_id is set → dropdown; if only bed text with no id → manual
$defaultBedSource = (!empty($p['bed_id'])) ? 'dropdown' : (!empty($p['bed']) ? 'manual' : 'dropdown');
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2 align-items-start">
    <div>
        <h5 class="mb-0">
            <i class="fas fa-user-edit text-primary me-2"></i>
            Edit Patient — <?= htmlspecialchars($p['uro_id']) ?>
            <span class="badge bg-<?= $isActive ? 'success' : 'secondary' ?> align-middle ms-2">
                <?= $isActive ? 'Active' : 'Discharged' ?>
            </span>
        </h5>
        <small class="text-muted"><?= htmlspecialchars($p['name']) ?></small>
    </div>

    <div class="pc-actions-view">
        <a href="view.php?id=<?= $id ?>" class="btn btn-icon btn-info" title="View Patient">
            <i class="fas fa-eye"></i>
        </a>
        <a href="../modules/discharge_print.php?id=<?= $id ?>" target="_blank" class="btn btn-icon btn-warning" title="Print Discharge Summary">
            <i class="fas fa-print"></i>
        </a>
        <a href="index.php" class="btn btn-icon btn-secondary" title="Back to Patients">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="POST" class="row g-3">

    <!-- IDENTIFICATION -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="fas fa-id-card me-2 text-primary"></i>Identification</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Patient Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= htmlspecialchars($p['name']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">URO ID</label>
                    <input type="text" class="form-control" disabled
                           value="<?= htmlspecialchars($p['uro_id']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Hospital Registration No.</label>
                    <input type="text" name="hospital_reg_no" class="form-control"
                           value="<?= htmlspecialchars($p['hospital_reg_no']) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- DEMOGRAPHICS -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="fas fa-user me-2 text-primary"></i>Demographics</div>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label">Age (years)</label>
                    <input type="number" name="age" id="age" class="form-control" min="0" max="150"
                           value="<?= (int)$p['age'] ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="dob" id="dob" class="form-control"
                           value="<?= htmlspecialchars($p['dob']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sex</label>
                    <select name="sex" class="form-select">
                        <?php foreach (['Male','Female','Others'] as $s): ?>
                            <option <?= ($p['sex'] === $s) ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-select">
                        <option value="">Select</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown'] as $b): ?>
                            <option <?= ($p['blood_group'] === $b) ? 'selected' : '' ?>><?= $b ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Name of Guardian</label>
                    <input type="text" name="guardian_name" class="form-control"
                           value="<?= htmlspecialchars($p['guardian_name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Occupation</label>
                    <input type="text" name="occupation" class="form-control"
                           value="<?= htmlspecialchars($p['occupation']) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- CONTACT -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="fas fa-address-book me-2 text-primary"></i>Contact</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Mobile No.</label>
                    <input type="text" name="mobile" class="form-control"
                           value="<?= htmlspecialchars($p['mobile']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">NID (Optional)</label>
                    <input type="text" name="nid" class="form-control"
                           value="<?= htmlspecialchars($p['nid']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($p['address']) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- ADMISSION -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="fas fa-hospital me-2 text-primary"></i>Admission Details</div>
            <div class="card-body row g-3">

                <div class="col-md-3">
                    <label class="form-label">Date of First Admission</label>
                    <input type="date" name="first_admission_date" class="form-control" value="<?= htmlspecialchars($p['first_admission_date']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date of Current Visit</label>
                    <input type="date" name="current_visit_date" class="form-control" value="<?= htmlspecialchars($p['current_visit_date']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mode of Admission</label>
                    <select name="mode_of_admission" class="form-select">
                        <option value="">Select</option>
                        <?php foreach (['Emergency','OPD','Transfer'] as $m): ?>
                            <option <?= ($p['mode_of_admission'] === $m) ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"></div>

                <!-- UNIT -->
                <div class="col-md-4">
                    <label class="form-label">Unit</label>
                    <select name="unit_id" id="unit_id" class="form-select" onchange="loadWards(this.value)">
                        <option value="">— Select Unit —</option>
                        <?php
                        $unitsAll = $conn->query("SELECT id, name FROM units WHERE status=1 ORDER BY name");
                        while ($u = $unitsAll->fetch_assoc()):
                        ?>
                            <option value="<?= $u['id'] ?>" <?= ($p['unit_id'] ?? 0) == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- WARD -->
                <div class="col-md-4">
                    <label class="form-label">Ward</label>
                    <select name="ward_id" id="ward_id" class="form-select" onchange="loadBeds(this.value)">
                        <option value="">— Select Ward —</option>
                        <?php
                        if (!empty($p['unit_id'])) {
                            $wid = (int)$p['unit_id'];
                            $wards = $conn->query("SELECT id, name FROM wards WHERE unit_id=$wid AND status=1 ORDER BY name");
                            while ($w = $wards->fetch_assoc()):
                        ?>
                            <option value="<?= $w['id'] ?>" <?= ($p['ward_id'] ?? 0) == $w['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($w['name']) ?>
                            </option>
                        <?php endwhile; } ?>
                    </select>
                </div>

                <!-- BED (dual mode) -->
                <div class="col-md-4">
                    <label class="form-label">Bed</label>

                    <div class="btn-group w-100 mb-2" role="group">
                        <input type="radio" class="btn-check" name="bed_source" id="bed_src_dd" value="dropdown"
                               <?= $defaultBedSource === 'dropdown' ? 'checked' : '' ?>
                               onchange="toggleBedMode()">
                        <label class="btn btn-outline-primary btn-sm" for="bed_src_dd">
                            <i class="fas fa-list me-1"></i> Select existing
                        </label>

                        <input type="radio" class="btn-check" name="bed_source" id="bed_src_mn" value="manual"
                               <?= $defaultBedSource === 'manual' ? 'checked' : '' ?>
                               onchange="toggleBedMode()">
                        <label class="btn btn-outline-secondary btn-sm" for="bed_src_mn">
                            <i class="fas fa-keyboard me-1"></i> Type new
                        </label>
                    </div>

                    <!-- Dropdown mode -->
                    <div id="bed_dropdown_wrap" style="display:<?= $defaultBedSource === 'dropdown' ? 'block' : 'none' ?>">
                        <select name="bed_id" id="bed_id" class="form-select">
                            <option value="">— Select Bed —</option>
                            <?php
                            if (!empty($p['ward_id'])) {
                                $bid = (int)$p['ward_id'];
                                $beds = $conn->query("SELECT id, name FROM beds WHERE ward_id=$bid AND status=1 ORDER BY name");
                                while ($b = $beds->fetch_assoc()):
                            ?>
                                <option value="<?= $b['id'] ?>" <?= ($p['bed_id'] ?? 0) == $b['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['name']) ?>
                                </option>
                            <?php endwhile; } ?>
                        </select>
                    </div>

                    <!-- Manual mode -->
                    <div id="bed_manual_wrap" style="display:<?= $defaultBedSource === 'manual' ? 'block' : 'none' ?>">
                        <input type="text" name="bed_manual" id="bed_manual" class="form-control"
                               value="<?= $defaultBedSource === 'manual' ? htmlspecialchars($p['bed']) : '' ?>"
                               placeholder="e.g. 14, ICU-2, Extra-1">
                        <small class="text-muted">Type any bed label</small>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Discharge Date</label>
                    <input type="date" name="discharge_date" class="form-control"
                           value="<?= $isActive ? '' : htmlspecialchars($p['discharge_date']) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- SUBMIT -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Review all changes before saving.</small>
                <div class="d-flex gap-2">
                    <a href="view.php?id=<?= $id ?>" class="btn btn-secondary px-4"><i class="fas fa-times me-1"></i> Cancel</a>
                    <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update Patient</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.getElementById('dob').addEventListener('change', function () {
    const dob = this.value;
    if (!dob) return;
    const ageInput = document.getElementById('age');
    if (ageInput.value) return;
    const birth = new Date(dob);
    const now = new Date();
    let age = now.getFullYear() - birth.getFullYear();
    const m = now.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && now.getDate() < birth.getDate())) age--;
    if (age >= 0 && age <= 150) ageInput.value = age;
});

// Load wards by unit
function loadWards(unitId) {
    const wardSel = document.getElementById('ward_id');
    const bedSel  = document.getElementById('bed_id');
    wardSel.innerHTML = '<option value="">— Select Ward —</option>';
    bedSel.innerHTML  = '<option value="">— Select Bed —</option>';
    if (!unitId) return;

    fetch('../settings/api.php?action=wards&unit_id=' + unitId)
        .then(r => r.json())
        .then(data => {
            data.forEach(w => {
                const o = document.createElement('option');
                o.value = w.id; o.textContent = w.name;
                wardSel.appendChild(o);
            });
        });
}

// Load beds by ward
function loadBeds(wardId) {
    const bedSel = document.getElementById('bed_id');
    bedSel.innerHTML = '<option value="">— Select Bed —</option>';
    if (!wardId) return;

    fetch('../settings/api.php?action=beds&ward_id=' + wardId)
        .then(r => r.json())
        .then(data => {
            data.forEach(b => {
                const o = document.createElement('option');
                o.value = b.id; o.textContent = b.name;
                bedSel.appendChild(o);
            });
        });
}

// Toggle between dropdown / manual bed
function toggleBedMode() {
    const manual = document.getElementById('bed_src_mn').checked;
    document.getElementById('bed_dropdown_wrap').style.display = manual ? 'none' : 'block';
    document.getElementById('bed_manual_wrap').style.display   = manual ? 'block' : 'none';

    // Clear the unused field
    if (manual) {
        document.getElementById('bed_id').value = '';
    } else {
        document.getElementById('bed_manual').value = '';
    }
}

// On page load, ensure correct visibility
document.addEventListener('DOMContentLoaded', toggleBedMode);
</script>

<!-- UNIFORM ICON BUTTON STYLES -->
<style>
.pc-actions-view { display: flex; gap: 8px; flex-wrap: wrap; }
.btn-icon {
    width: 34px; height: 34px; padding: 0;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 7px; font-size: 13px; color: #fff; border: none;
    transition: transform .15s, box-shadow .15s, filter .15s;
}
.btn-icon:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,.18);
    color: #fff; filter: brightness(1.05);
}
.btn-icon.disabled { opacity: .4; pointer-events: none; }
@media (max-width: 576px) {
    .btn-icon { width: 30px; height: 30px; font-size: 12px; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>