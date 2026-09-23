<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$pageTitle = 'Create Patient';
$error = '';

// Load active units
$unitsList = $conn->query("SELECT id, name FROM units WHERE status=1 ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uro_id = generateUroID($conn);
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $first_admission = !empty($_POST['first_admission_date']) ? $_POST['first_admission_date'] : null;
    $current_visit = !empty($_POST['current_visit_date']) ? $_POST['current_visit_date'] : null;
    $age = !empty($_POST['age']) ? (int)$_POST['age'] : null;

    if (!$age && $dob) {
        $diff = (time() - strtotime($dob)) / (365.25 * 24 * 3600);
        $age = (int)$diff;
    }

    // ---- Unit / Ward (dropdown-only) ----
    $unit_id = intval($_POST['unit_id'] ?? 0) ?: null;
    $ward_id = intval($_POST['ward_id'] ?? 0) ?: null;

    // ---- Bed: either dropdown (bed_id) or manual (bed text) ----
    $bed_source = $_POST['bed_source'] ?? 'dropdown';   // 'dropdown' or 'manual'
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

    // Fetch names for legacy text fields
    $unitName = $wardName = null;
    if ($unit_id) { $r = $conn->query("SELECT name FROM units WHERE id=$unit_id")->fetch_assoc(); $unitName = $r['name'] ?? null; }
    if ($ward_id) { $r = $conn->query("SELECT name FROM wards WHERE id=$ward_id")->fetch_assoc(); $wardName = $r['name'] ?? null; }

    $stmt = $conn->prepare("INSERT INTO patients 
        (uro_id, name, unit, unit_id, ward, ward_id, bed, bed_id, age, dob, sex, guardian_name, occupation, address, mobile, nid,
         first_admission_date, current_visit_date, blood_group, hospital_reg_no,
         mode_of_admission, discharge_date, created_by) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NULL,?)");
    $stmt->bind_param('sssisisisssssssssssssi',
        $uro_id, $_POST['name'], $unitName, $unit_id, $wardName, $ward_id, $bedName, $bed_id,
        $age, $dob, $_POST['sex'],
        $_POST['guardian_name'], $_POST['occupation'], $_POST['address'], $_POST['mobile'], $_POST['nid'],
        $first_admission, $current_visit, $_POST['blood_group'],
        $_POST['hospital_reg_no'], $_POST['mode_of_admission'], $_SESSION['user_id']
    );
    if ($stmt->execute()) {
        header("Location: view.php?id=" . $conn->insert_id);
        exit;
    } else {
        $error = "Error: " . $conn->error;
    }
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-user-plus text-primary me-2"></i>Create Patient</h5>
        <small class="text-muted">Enter patient profile details.</small>
    </div>
    <a href="index.php" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
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
                    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">URO ID</label>
                    <input type="text" class="form-control" disabled value="Auto-generated (URO-<?= date('Y') ?>-XXXXX)">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Hospital Registration No.</label>
                    <input type="text" name="hospital_reg_no" class="form-control" value="<?= htmlspecialchars($_POST['hospital_reg_no'] ?? '') ?>">
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
                    <input type="number" name="age" id="age" class="form-control" min="0" max="150" value="<?= htmlspecialchars($_POST['age'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="dob" id="dob" class="form-control" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sex <span class="text-danger">*</span></label>
                    <select name="sex" class="form-select" required>
                        <option value="">Select</option>
                        <?php foreach (['Male','Female','Others'] as $s): ?>
                            <option <?= (($_POST['sex'] ?? '') === $s) ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-select">
                        <option value="">Select</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown'] as $b): ?>
                            <option <?= (($_POST['blood_group'] ?? '') === $b) ? 'selected' : '' ?>><?= $b ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Name of Guardian</label>
                    <input type="text" name="guardian_name" class="form-control" value="<?= htmlspecialchars($_POST['guardian_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Occupation</label>
                    <input type="text" name="occupation" class="form-control" value="<?= htmlspecialchars($_POST['occupation'] ?? '') ?>">
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
                    <label class="form-label">Mobile No. <span class="text-danger">*</span></label>
                    <input type="text" name="mobile" class="form-control" required value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">NID (Optional)</label>
                    <input type="text" name="nid" class="form-control" value="<?= htmlspecialchars($_POST['nid'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
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
                    <input type="date" name="first_admission_date" class="form-control" value="<?= htmlspecialchars($_POST['first_admission_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date of Current Visit</label>
                    <input type="date" name="current_visit_date" class="form-control" value="<?= htmlspecialchars($_POST['current_visit_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mode of Admission</label>
                    <select name="mode_of_admission" class="form-select">
                        <option value="">Select</option>
                        <?php foreach (['Emergency','OPD','Transfer'] as $m): ?>
                            <option <?= (($_POST['mode_of_admission'] ?? '') === $m) ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"></div>

                <!-- UNIT -->
                <div class="col-md-4">
                    <label class="form-label">Unit</label>
                    <select name="unit_id" id="unit_id" class="form-select" onchange="loadWards(this.value)">
                        <option value="">— Select Unit —</option>
                        <?php $unitsList->data_seek(0); while ($u = $unitsList->fetch_assoc()): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- WARD -->
                <div class="col-md-4">
                    <label class="form-label">Ward</label>
                    <select name="ward_id" id="ward_id" class="form-select" onchange="loadBeds(this.value)">
                        <option value="">— Select Ward —</option>
                    </select>
                    <small class="text-muted">Select Unit first</small>
                </div>

                <!-- BED (dual mode) -->
                <div class="col-md-4">
                    <label class="form-label">Bed</label>

                    <div class="btn-group w-100 mb-2" role="group">
                        <input type="radio" class="btn-check" name="bed_source" id="bed_src_dd" value="dropdown" checked
                               onchange="toggleBedMode()">
                        <label class="btn btn-outline-primary btn-sm" for="bed_src_dd">
                            <i class="fas fa-list me-1"></i> Select existing
                        </label>

                        <input type="radio" class="btn-check" name="bed_source" id="bed_src_mn" value="manual"
                               onchange="toggleBedMode()">
                        <label class="btn btn-outline-secondary btn-sm" for="bed_src_mn">
                            <i class="fas fa-keyboard me-1"></i> Type new
                        </label>
                    </div>

                    <!-- Dropdown mode -->
                    <div id="bed_dropdown_wrap">
                        <select name="bed_id" id="bed_id" class="form-select">
                            <option value="">— Select Bed —</option>
                        </select>
                        <small class="text-muted">Select Ward first</small>
                    </div>

                    <!-- Manual mode -->
                    <div id="bed_manual_wrap" style="display:none">
                        <input type="text" name="bed_manual" id="bed_manual" class="form-control"
                               placeholder="e.g. 14, ICU-2, Extra-1">
                        <small class="text-muted">Type any bed label</small>
                    </div>
                </div>

                <div class="col-12">
                    <div class="alert alert-info py-2 mb-0 small">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Discharge Date</strong> will be set only when the patient is formally discharged.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Review before saving.</small>
                <div class="d-flex gap-2">
                    <a href="index.php" class="btn btn-secondary px-4"><i class="fas fa-times me-1"></i> Cancel</a>
                    <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Patient</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Auto age from DOB
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

    // Clear the unused field so it doesn't submit stale values
    if (manual) {
        document.getElementById('bed_id').value = '';
    } else {
        document.getElementById('bed_manual').value = '';
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>