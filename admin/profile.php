<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$pageTitle = 'My Profile';

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// ============================================================
// HANDLE FORM SUBMISSIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---------- UPDATE PROFILE ----------
    if (($_POST['action'] ?? '') === 'update_profile') {
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $spec  = trim($_POST['specialization'] ?? '');

        if (!$name || !$email) {
            $error = 'Name and email are required.';
        } else {
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->bind_param('si', $email, $userId);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = 'That email is already taken by another user.';
            } else {
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, specialization=? WHERE id=?");
                $stmt->bind_param('ssssi', $name, $email, $phone, $spec, $userId);
                if ($stmt->execute()) {
                    $_SESSION['name']  = $name;
                    $_SESSION['email'] = $email;
                    $success = 'Profile updated successfully.';
                } else {
                    $error = 'Failed to update profile.';
                }
            }
        }
    }

    // ---------- CHANGE PASSWORD ----------
    if (($_POST['action'] ?? '') === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!password_verify($current, $row['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $upd->bind_param('si', $hash, $userId);
            if ($upd->execute()) {
                $success = 'Password changed successfully.';
            } else {
                $error = 'Failed to change password.';
            }
        }
    }
}

// ============================================================
// LOAD USER + ACTIVITY DATA
// ============================================================
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

$isAdmin = ($u['role'] === 'admin');

// ---- My activity counts ----
$myStats = [
    'patients'   => 0,
    'operations' => 0,
    'followups'  => 0,
    'imaging'    => 0,
    'discharges' => 0,
];
$q = $conn->prepare("SELECT COUNT(*) c FROM patients   WHERE created_by = ?");
$q->bind_param('i', $userId); $q->execute(); $myStats['patients']   = $q->get_result()->fetch_assoc()['c'];
$q = $conn->prepare("SELECT COUNT(*) c FROM operations WHERE created_by = ?");
$q->bind_param('i', $userId); $q->execute(); $myStats['operations'] = $q->get_result()->fetch_assoc()['c'];
$q = $conn->prepare("SELECT COUNT(*) c FROM followup   WHERE created_by = ?");
$q->bind_param('i', $userId); $q->execute(); $myStats['followups']  = $q->get_result()->fetch_assoc()['c'];
$q = $conn->prepare("SELECT COUNT(*) c FROM imaging_reports WHERE created_by = ?");
$q->bind_param('i', $userId); $q->execute(); $myStats['imaging']    = $q->get_result()->fetch_assoc()['c'];
$q = $conn->prepare("SELECT COUNT(*) c FROM discharge_summary WHERE created_by = ?");
$q->bind_param('i', $userId); $q->execute(); $myStats['discharges'] = $q->get_result()->fetch_assoc()['c'];

// ---- Global stats (admin only) ----
$globalStats = [];
if ($isAdmin) {
    $globalStats = [
        'patients'   => $conn->query("SELECT COUNT(*) c FROM patients")->fetch_assoc()['c'],
        'doctors'    => $conn->query("SELECT COUNT(*) c FROM users WHERE role='doctor' AND status=1")->fetch_assoc()['c'],
        'operations' => $conn->query("SELECT COUNT(*) c FROM operations")->fetch_assoc()['c'],
        'followups'  => $conn->query("SELECT COUNT(*) c FROM followup")->fetch_assoc()['c'],
    ];
}

// ---- My recent activity (latest 5 each) ----
$myRecentPatients = $conn->prepare("SELECT id, uro_id, name, created_at FROM patients WHERE created_by = ? ORDER BY id DESC LIMIT 5");
$myRecentPatients->bind_param('i', $userId); $myRecentPatients->execute();
$recentPatients = $myRecentPatients->get_result();

$myRecentOps = $conn->prepare("SELECT o.id, o.operation_name, o.operation_date, p.name AS patient_name 
                               FROM operations o 
                               LEFT JOIN patients p ON p.id = o.patient_id 
                               WHERE o.created_by = ? 
                               ORDER BY o.id DESC LIMIT 5");
$myRecentOps->bind_param('i', $userId); $myRecentOps->execute();
$recentOps = $myRecentOps->get_result();

$myRecentFollowups = $conn->prepare("SELECT f.id, f.followup_date, f.visit_type, p.name AS patient_name 
                                     FROM followup f 
                                     LEFT JOIN patients p ON p.id = f.patient_id 
                                     WHERE f.created_by = ? 
                                     ORDER BY f.id DESC LIMIT 5");
$myRecentFollowups->bind_param('i', $userId); $myRecentFollowups->execute();
$recentFollowups = $myRecentFollowups->get_result();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<!-- ============================================================
     HERO HEADER
     ============================================================ -->
<div class="profile-hero mb-3">
    <div class="hero-bg"></div>
    <div class="hero-content">
        <div class="hero-avatar">
            <i class="fas fa-user-md"></i>
        </div>
        <div class="hero-info">
            <h4 class="mb-1"><?= htmlspecialchars($u['name']) ?></h4>
            <div class="hero-meta">
                <span class="badge bg-<?= $isAdmin ? 'danger' : 'primary' ?>">
                    <i class="fas fa-<?= $isAdmin ? 'user-shield' : 'user-md' ?> me-1"></i>
                    <?= ucfirst($u['role']) ?>
                </span>
                <span class="hero-text"><i class="fas fa-envelope me-1"></i><?= htmlspecialchars($u['email']) ?></span>
                <span class="hero-text"><i class="fas fa-calendar-alt me-1"></i>Joined <?= date('d M Y', strtotime($u['created_at'])) ?></span>
            </div>
        </div>
        <div class="hero-actions">
            <a href="<?= BASE_URL ?>index.php" class="btn btn-icon btn-light" title="Back to Dashboard">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>
</div>

<!-- ============================================================
     ALERTS
     ============================================================ -->
<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> <?= htmlspecialchars($success) ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ============================================================
     TABS
     ============================================================ -->
<ul class="nav nav-pills profile-tabs mb-3" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-profile">
            <i class="fas fa-id-card me-1"></i> Profile
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-password">
            <i class="fas fa-lock me-1"></i> Password
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-activity">
            <i class="fas fa-chart-line me-1"></i> Activity
        </button>
    </li>
</ul>

<div class="tab-content">

    <!-- ============================================================
         TAB 1: PROFILE
         ============================================================ -->
    <div class="tab-pane fade show active" id="tab-profile">
        <div class="row g-3">

            <!-- Left card: quick summary -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header"><i class="fas fa-info-circle me-2 text-primary"></i>Account Summary</div>
                    <div class="card-body">
                        <div class="summary-row">
                            <span class="summary-label">Name</span>
                            <span class="summary-value"><?= htmlspecialchars($u['name']) ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Role</span>
                            <span class="summary-value">
                                <span class="badge bg-<?= $isAdmin ? 'danger' : 'primary' ?>"><?= ucfirst($u['role']) ?></span>
                            </span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Email</span>
                            <span class="summary-value"><?= htmlspecialchars($u['email']) ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Phone</span>
                            <span class="summary-value"><?= htmlspecialchars($u['phone'] ?: '—') ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Specialization</span>
                            <span class="summary-value"><?= htmlspecialchars($u['specialization'] ?: '—') ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Status</span>
                            <span class="summary-value">
                                <?php if ((int)$u['status'] === 1): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Joined</span>
                            <span class="summary-value"><?= date('d M Y, H:i', strtotime($u['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right card: profile form -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header"><i class="fas fa-user-edit me-2 text-primary"></i>Update Profile</div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                       value="<?= htmlspecialchars($u['name']) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?= htmlspecialchars($u['email']) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control"
                                       value="<?= htmlspecialchars($u['phone']) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Specialization</label>
                                <input type="text" name="specialization" class="form-control"
                                       value="<?= htmlspecialchars($u['specialization']) ?>"
                                       placeholder="e.g. Urologist, Uro-oncologist">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" disabled
                                       value="<?= ucfirst($u['role']) ?>">
                                <small class="text-muted">Only admin can change roles via Manage Users.</small>
                            </div>

                            <div class="col-12 text-end mt-3">
                                <button class="btn btn-primary px-4">
                                    <i class="fas fa-save me-1"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ============================================================
         TAB 2: PASSWORD
         ============================================================ -->
    <div class="tab-pane fade" id="tab-password">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><i class="fas fa-key me-2 text-danger"></i>Change Password</div>
                    <div class="card-body">
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle me-1"></i>
                            Use a strong password (at least 6 characters, mix letters and numbers).
                        </div>
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="action" value="change_password">

                            <div class="col-12">
                                <label class="form-label">Current Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="current_password" id="cp" class="form-control" required>
                                    <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="cp">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="new_password" id="np" class="form-control" required minlength="6">
                                    <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="np">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Minimum 6 characters.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id="cfp" class="form-control" required minlength="6">
                                    <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="cfp">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-12 text-end">
                                <button class="btn btn-danger px-4">
                                    <i class="fas fa-key me-1"></i> Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         TAB 3: ACTIVITY
         ============================================================ -->
    <div class="tab-pane fade" id="tab-activity">

        <!-- ========== MY ACTIVITY STATS ========== -->
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-tile">
                    <div class="stat-icon bg-primary"><i class="fas fa-users"></i></div>
                    <div class="stat-value"><?= $myStats['patients'] ?></div>
                    <div class="stat-label">Patients</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-tile">
                    <div class="stat-icon bg-success"><i class="fas fa-procedures"></i></div>
                    <div class="stat-value"><?= $myStats['operations'] ?></div>
                    <div class="stat-label">Operations</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-tile">
                    <div class="stat-icon bg-warning"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-value"><?= $myStats['followups'] ?></div>
                    <div class="stat-label">Follow-ups</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-tile">
                    <div class="stat-icon bg-info"><i class="fas fa-x-ray"></i></div>
                    <div class="stat-value"><?= $myStats['imaging'] ?></div>
                    <div class="stat-label">Reports</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-tile">
                    <div class="stat-icon bg-danger"><i class="fas fa-file-medical-alt"></i></div>
                    <div class="stat-value"><?= $myStats['discharges'] ?></div>
                    <div class="stat-label">Discharges</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-tile stat-tile-total">
                    <div class="stat-icon bg-dark"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-value"><?= array_sum($myStats) ?></div>
                    <div class="stat-label">Total Records</div>
                </div>
            </div>
        </div>

        <!-- ========== GLOBAL STATS (ADMIN ONLY) ========== -->
        <?php if ($isAdmin): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-globe me-2 text-danger"></i>System Overview (Admin Only)</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="global-tile">
                            <div class="global-value"><?= $globalStats['patients'] ?></div>
                            <div class="global-label">Total Patients</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="global-tile">
                            <div class="global-value"><?= $globalStats['doctors'] ?></div>
                            <div class="global-label">Active Doctors</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="global-tile">
                            <div class="global-value"><?= $globalStats['operations'] ?></div>
                            <div class="global-label">All Operations</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="global-tile">
                            <div class="global-value"><?= $globalStats['followups'] ?></div>
                            <div class="global-label">All Follow-ups</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ========== RECENT ACTIVITY ========== -->
        <div class="row g-3">

            <!-- Recent Patients -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-plus me-2 text-primary"></i>My Recent Patients</span>
                        <span class="badge bg-primary"><?= $myStats['patients'] ?> total</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle mini-rec">
                            <thead>
                                <tr><th>URO ID</th><th>Name</th><th>Created</th></tr>
                            </thead>
                            <tbody>
                            <?php if ($recentPatients->num_rows === 0): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">No patients yet.</td></tr>
                            <?php else: while ($r = $recentPatients->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge bg-light text-primary"><?= htmlspecialchars($r['uro_id']) ?></span></td>
                                    <td><?= htmlspecialchars($r['name']) ?></td>
                                    <td><small class="text-muted"><?= date('d M Y', strtotime($r['created_at'])) ?></small></td>
                                </tr>
                            <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Operations -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-procedures me-2 text-success"></i>My Recent Operations</span>
                        <span class="badge bg-success"><?= $myStats['operations'] ?> total</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle mini-rec">
                            <thead>
                                <tr><th>Date</th><th>Operation</th><th>Patient</th></tr>
                            </thead>
                            <tbody>
                            <?php if ($recentOps->num_rows === 0): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">No operations yet.</td></tr>
                            <?php else: while ($r = $recentOps->fetch_assoc()): ?>
                                <tr>
                                    <td><small><?= $r['operation_date'] ?: '—' ?></small></td>
                                    <td><?= htmlspecialchars($r['operation_name'] ?: '—') ?></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($r['patient_name'] ?: '—') ?></small></td>
                                </tr>
                            <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Follow-ups -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-calendar-check me-2 text-warning"></i>My Recent Follow-ups</span>
                        <span class="badge bg-warning text-dark"><?= $myStats['followups'] ?> total</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle mini-rec">
                            <thead>
                                <tr><th>Date</th><th>Visit Type</th><th>Patient</th></tr>
                            </thead>
                            <tbody>
                            <?php if ($recentFollowups->num_rows === 0): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">No follow-ups yet.</td></tr>
                            <?php else: while ($r = $recentFollowups->fetch_assoc()): ?>
                                <tr>
                                    <td><small><?= $r['followup_date'] ?: '—' ?></small></td>
                                    <td><?= htmlspecialchars($r['visit_type'] ?: '—') ?></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($r['patient_name'] ?: '—') ?></small></td>
                                </tr>
                            <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Login / Security info -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><i class="fas fa-shield-alt me-2 text-info"></i>Account Security</div>
                    <div class="card-body">
                        <div class="security-row">
                            <div class="security-icon bg-success"><i class="fas fa-check-circle"></i></div>
                            <div>
                                <strong>Account Active</strong>
                                <div class="small text-muted">Status: <?= (int)$u['status'] === 1 ? 'Enabled' : 'Disabled' ?></div>
                            </div>
                        </div>
                        <div class="security-row">
                            <div class="security-icon bg-primary"><i class="fas fa-user-shield"></i></div>
                            <div>
                                <strong>Role: <?= ucfirst($u['role']) ?></strong>
                                <div class="small text-muted">
                                    <?= $isAdmin ? 'Full system access' : 'Standard user access' ?>
                                </div>
                            </div>
                        </div>
                        <div class="security-row">
                            <div class="security-icon bg-warning"><i class="fas fa-clock"></i></div>
                            <div>
                                <strong>Last Updated</strong>
                                <div class="small text-muted">
                                    <?= isset($u['updated_at']) && $u['updated_at']
                                        ? date('d M Y, H:i', strtotime($u['updated_at']))
                                        : date('d M Y, H:i', strtotime($u['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="<?= BASE_URL ?>auth/logout.php" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- ============================================================
     STYLES
     ============================================================ -->
<style>
/* ============ HERO HEADER ============ */
.profile-hero {
    position: relative;
    border-radius: 14px;
    overflow: hidden;
    background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
    color: #fff;
    box-shadow: 0 8px 24px rgba(37,99,235,.25);
}
.hero-bg {
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(circle at 20% 20%, rgba(255,255,255,.15) 0%, transparent 40%),
        radial-gradient(circle at 80% 80%, rgba(255,255,255,.10) 0%, transparent 45%);
    pointer-events: none;
}
.hero-content {
    position: relative;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}
.hero-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,.22);
    border: 3px solid rgba(255,255,255,.5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    color: #fff;
    flex-shrink: 0;
}
.hero-info { flex: 1; min-width: 0; }
.hero-info h4 { font-weight: 700; letter-spacing: .2px; }
.hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 16px;
    margin-top: 4px;
    font-size: 13px;
    opacity: .95;
}
.hero-meta .badge { font-size: 11px; padding: 4px 10px; }
.hero-text { display: inline-flex; align-items: center; }
.hero-actions { display: flex; gap: 8px; }

/* ============ TABS ============ */
.profile-tabs {
    background: #fff;
    padding: 6px;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
    gap: 4px;
    display: flex;
    flex-wrap: wrap;
}
.profile-tabs .nav-link {
    color: #64748b;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13.5px;
    padding: 8px 16px;
    border: none;
    transition: .15s;
}
.profile-tabs .nav-link:hover { background: #f1f5f9; color: #2563eb; }
.profile-tabs .nav-link.active {
    background: #2563eb;
    color: #fff;
    box-shadow: 0 2px 8px rgba(37,99,235,.3);
}

/* ============ SUMMARY CARD ============ */
.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px dashed #eef2f7;
    font-size: 13px;
    gap: 10px;
}
.summary-row:last-child { border-bottom: none; }
.summary-label { color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; font-size: 10.5px; }
.summary-value { color: #1e293b; font-weight: 600; text-align: right; word-break: break-word; }

/* ============ ACTIVITY STAT TILES ============ */
.stat-tile {
    background: #fff;
    border: 1px solid #e9eef5;
    border-radius: 10px;
    padding: 12px 10px;
    text-align: center;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
    transition: .15s;
    height: 100%;
}
.stat-tile:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(37,99,235,.10);
    border-color: #2563eb;
}
.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 16px;
    margin-bottom: 6px;
}
.stat-value { font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1; }
.stat-label { font-size: 10.5px; text-transform: uppercase; letter-spacing: .4px; color: #64748b; font-weight: 600; margin-top: 4px; }
.stat-tile-total { background: #0f172a; color: #fff; border-color: #0f172a; }
.stat-tile-total .stat-value { color: #fff; }
.stat-tile-total .stat-label { color: #cbd5e1; }

/* ============ GLOBAL TILES ============ */
.global-tile {
    background: #f8fafc;
    border-left: 3px solid #dc2626;
    border-radius: 8px;
    padding: 12px 14px;
}
.global-value { font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1; }
.global-label { font-size: 11px; text-transform: uppercase; letter-spacing: .4px; color: #64748b; font-weight: 600; margin-top: 4px; }

/* ============ MINI RECORDS TABLE ============ */
.mini-rec { font-size: 12.5px; }
.mini-rec thead th {
    background: #f8fafc;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .3px;
    color: #64748b;
    font-weight: 700;
    padding: 8px 10px;
    white-space: nowrap;
}
.mini-rec tbody td { padding: 8px 10px; vertical-align: middle; }

/* ============ SECURITY ROWS ============ */
.security-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px dashed #eef2f7;
}
.security-row:last-child { border-bottom: none; }
.security-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 15px;
    flex-shrink: 0;
}

/* ============ ICON BUTTON ============ */
.btn-icon {
    width: 36px;
    height: 36px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 14px;
    border: none;
    transition: .15s;
}
.btn-icon:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,.2); }

/* ============ RESPONSIVE ============ */
@media (max-width: 768px) {
    .hero-content { padding: 18px; gap: 14px; }
    .hero-avatar { width: 64px; height: 64px; font-size: 28px; }
    .hero-info h4 { font-size: 1.1rem; }
    .hero-meta { font-size: 12px; }
    .profile-tabs .nav-link { padding: 6px 12px; font-size: 12.5px; }
    .stat-value { font-size: 18px; }
    .stat-icon { width: 34px; height: 34px; font-size: 14px; }
    .global-value { font-size: 18px; }
}
@media (max-width: 576px) {
    .hero-content { padding: 14px; }
    .hero-avatar { width: 54px; height: 54px; font-size: 24px; border-width: 2px; }
    .hero-info h4 { font-size: 1rem; }
    .hero-meta { gap: 6px 12px; font-size: 11.5px; }
    .hero-actions { width: 100%; justify-content: flex-end; }
    .profile-tabs { padding: 4px; }
    .profile-tabs .nav-link { padding: 5px 10px; font-size: 11.5px; }
    .profile-tabs .nav-link i { display: none; }
}
</style>

<!-- ============ SHOW/HIDE PASSWORD TOGGLE ============ -->
<script>
document.querySelectorAll('.toggle-pw').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const input = document.getElementById(this.dataset.target);
        const icon  = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye','fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash','fa-eye');
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>