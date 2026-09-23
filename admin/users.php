<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'Manage Users';

$msg = '';
$msgType = 'success';

// ============================================================
// POST ACTIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---------- CREATE NEW USER ----------
    if ($action === 'create') {
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password']   ?? '';
        $role  = $_POST['role']       ?? 'doctor';
        $phone = trim($_POST['phone'] ?? '');
        $spec  = trim($_POST['specialization'] ?? '');

        if (!$name || !$email || !$pass) {
            $msg = 'Name, email and password are required.';
            $msgType = 'danger';
        } else {
            $chk = $conn->prepare("SELECT id FROM users WHERE email=?");
            $chk->bind_param('s', $email);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $msg = 'That email is already in use.';
                $msgType = 'danger';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone, specialization, status) VALUES (?,?,?,?,?,?,1)");
                $stmt->bind_param('ssssss', $name, $email, $hash, $role, $phone, $spec);
                $stmt->execute();
                header("Location: users.php?saved=1"); exit;
            }
        }
    }

    // ---------- UPDATE USER ----------
    if ($action === 'update') {
        $uid   = intval($_POST['id'] ?? 0);
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = $_POST['role']       ?? 'doctor';
        $phone = trim($_POST['phone'] ?? '');
        $spec  = trim($_POST['specialization'] ?? '');

        if (!$uid || !$name || !$email) {
            $msg = 'Name and email are required.';
            $msgType = 'danger';
        } else {
            $chk = $conn->prepare("SELECT id FROM users WHERE email=? AND id!=?");
            $chk->bind_param('si', $email, $uid);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $msg = 'That email is already in use by another user.';
                $msgType = 'danger';
            } else {
                if ($uid == $_SESSION['user_id'] && $role !== 'admin') {
                    $msg = 'You cannot change your own admin role.';
                    $msgType = 'danger';
                } else {
                    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=?, phone=?, specialization=? WHERE id=?");
                    $stmt->bind_param('sssssi', $name, $email, $role, $phone, $spec, $uid);
                    $stmt->execute();
                    if ($uid == $_SESSION['user_id']) {
                        $_SESSION['name']  = $name;
                        $_SESSION['email'] = $email;
                    }
                    header("Location: users.php?saved=1"); exit;
                }
            }
        }
    }

    // ---------- CHANGE PASSWORD ----------
    if ($action === 'change_password') {
        $uid  = intval($_POST['id'] ?? 0);
        $pass = $_POST['new_password'] ?? '';

        if (strlen($pass) < 6) {
            $msg = 'New password must be at least 6 characters.';
            $msgType = 'danger';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param('si', $hash, $uid);
            $stmt->execute();
            header("Location: users.php?saved=1"); exit;
        }
    }

    // ---------- TOGGLE STATUS ----------
    if ($action === 'toggle_status') {
        $uid = intval($_POST['id'] ?? 0);
        if ($uid == $_SESSION['user_id']) {
            $msg = 'You cannot deactivate your own account.';
            $msgType = 'danger';
        } else {
            $conn->query("UPDATE users SET status = 1 - status WHERE id=$uid");
            header("Location: users.php?saved=1"); exit;
        }
    }
}

// ============================================================
// DELETE
// ============================================================
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id != $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id=$id");
    }
    header("Location: users.php?deleted=1"); exit;
}

// ============================================================
// LOAD DATA — with per-user activity counts
// ============================================================
$users = $conn->query("SELECT * FROM users ORDER BY role, name");

// Precompute per-user counts in one pass per table (efficient)
$userStats = [];

$fetchCounts = function($table, $key) use ($conn, &$userStats) {
    $r = $conn->query("SELECT created_by, COUNT(*) AS c FROM $table WHERE created_by IS NOT NULL GROUP BY created_by");
    while ($row = $r->fetch_assoc()) {
        $userStats[$row['created_by']][$key] = (int)$row['c'];
    }
};

$fetchCounts('patients',           'patients');
$fetchCounts('operations',         'operations');
$fetchCounts('followup',           'followups');
$fetchCounts('imaging_reports',    'imaging');
$fetchCounts('discharge_summary',  'discharges');

// Fetch per-user "last activity" timestamp
$lastActivity = [];
$la = $conn->query("
    SELECT created_by, MAX(created_at) AS last_at FROM patients WHERE created_by IS NOT NULL GROUP BY created_by
    UNION
    SELECT created_by, MAX(created_at) AS last_at FROM operations WHERE created_by IS NOT NULL GROUP BY created_by
    UNION
    SELECT created_by, MAX(created_at) AS last_at FROM followup WHERE created_by IS NOT NULL GROUP BY created_by
    UNION
    SELECT created_by, MAX(created_at) AS last_at FROM imaging_reports WHERE created_by IS NOT NULL GROUP BY created_by
    UNION
    SELECT created_by, MAX(created_at) AS last_at FROM discharge_summary WHERE created_by IS NOT NULL GROUP BY created_by
");
while ($row = $la->fetch_assoc()) {
    $uid = (int)$row['created_by'];
    if (!isset($lastActivity[$uid]) || $row['last_at'] > $lastActivity[$uid]) {
        $lastActivity[$uid] = $row['last_at'];
    }
}

// Count totals
$totalUsers   = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$totalAdmins  = $conn->query("SELECT COUNT(*) c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$totalDoctors = $conn->query("SELECT COUNT(*) c FROM users WHERE role='doctor'")->fetch_assoc()['c'];
$activeUsers  = $conn->query("SELECT COUNT(*) c FROM users WHERE status=1")->fetch_assoc()['c'];
$inactiveUsers = $totalUsers - $activeUsers;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<!-- ============================================================
     HERO HEADER
     ============================================================ -->
<div class="users-hero mb-3">
    <div class="hero-bg"></div>
    <div class="hero-content">
        <div class="hero-icon"><i class="fas fa-user-cog"></i></div>
        <div class="hero-info">
            <h4 class="mb-1">Manage Users</h4>
            <p class="mb-0 small">Create, edit, activate/deactivate, delete users and change passwords.</p>
        </div>
    </div>
</div>

<!-- ============================================================
     STAT TILES
     ============================================================ -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-tile">
            <div class="stat-icon bg-primary"><i class="fas fa-users"></i></div>
            <div class="stat-value"><?= $totalUsers ?></div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-tile">
            <div class="stat-icon bg-success"><i class="fas fa-user-md"></i></div>
            <div class="stat-value"><?= $totalDoctors ?></div>
            <div class="stat-label">Doctors</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-tile">
            <div class="stat-icon bg-danger"><i class="fas fa-user-shield"></i></div>
            <div class="stat-value"><?= $totalAdmins ?></div>
            <div class="stat-label">Admins</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-tile">
            <div class="stat-icon bg-info"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?= $activeUsers ?></div>
            <div class="stat-label">Active <small class="text-muted">/ <?= $inactiveUsers ?> inactive</small></div>
        </div>
    </div>
</div>

<!-- ============================================================
     ALERTS
     ============================================================ -->
<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Changes saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        <i class="fas fa-trash me-1"></i> User deleted.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-1"></i> <?= htmlspecialchars($msg) ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">

    <!-- ============================================================
         LEFT: Create New User
         ============================================================ -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-user-plus me-2 text-primary"></i>Create New User</div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <input type="hidden" name="action" value="create">

                    <div class="col-12">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input name="name" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                        <small class="text-muted">Minimum 6 characters.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="doctor">Doctor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Phone</label>
                        <input name="phone" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Specialization</label>
                        <input name="specialization" class="form-control">
                    </div>
                    <div class="col-12 text-end">
                        <button class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i> Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================
         RIGHT: All Users
         ============================================================ -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users me-2 text-primary"></i>All Users</span>
                <span class="badge bg-primary"><?= $totalUsers ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle users-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th style="width:80px">Role</th>
                            <th style="width:90px">Status</th>
                            <th style="width:120px">Activity</th>
                            <th style="width:200px" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($u = $users->fetch_assoc()):
                        $uid       = (int)$u['id'];
                        $isSelf    = ($uid == $_SESSION['user_id']);
                        $isActive  = (int)$u['status'] === 1;
                        $stats     = $userStats[$uid] ?? [];
                        $totalAct  = array_sum($stats);
                        $lastSeen  = $lastActivity[$uid] ?? null;
                    ?>
                        <tr class="<?= !$isActive ? 'row-inactive' : '' ?>">
                            <td>
                                <div class="user-name">
                                    <strong><?= htmlspecialchars($u['name']) ?></strong>
                                    <?php if ($isSelf): ?>
                                        <span class="badge bg-secondary ms-1" style="font-size:9px">You</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($u['specialization']): ?>
                                    <small class="text-muted d-block"><?= htmlspecialchars($u['specialization']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small"><?= htmlspecialchars($u['email']) ?></div>
                                <?php if ($u['phone']): ?>
                                    <small class="text-muted"><i class="fas fa-phone me-1"></i><?= htmlspecialchars($u['phone']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $u['role'] == 'admin' ? 'danger' : 'primary' ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($isActive): ?>
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="fas fa-ban me-1"></i>Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary w-100"
                                        data-bs-toggle="modal" data-bs-target="#activityModal"
                                        data-id="<?= $uid ?>"
                                        data-name="<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>"
                                        data-email="<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>"
                                        data-role="<?= htmlspecialchars($u['role'], ENT_QUOTES) ?>"
                                        data-spec="<?= htmlspecialchars($u['specialization'] ?? '', ENT_QUOTES) ?>"
                                        data-phone="<?= htmlspecialchars($u['phone'] ?? '', ENT_QUOTES) ?>"
                                        data-patients="<?= (int)($stats['patients'] ?? 0) ?>"
                                        data-operations="<?= (int)($stats['operations'] ?? 0) ?>"
                                        data-followups="<?= (int)($stats['followups'] ?? 0) ?>"
                                        data-imaging="<?= (int)($stats['imaging'] ?? 0) ?>"
                                        data-discharges="<?= (int)($stats['discharges'] ?? 0) ?>"
                                        data-last="<?= $lastSeen ? date('d M Y, H:i', strtotime($lastSeen)) : 'No activity yet' ?>">
                                    <i class="fas fa-chart-line me-1"></i>
                                    <strong><?= $totalAct ?></strong>
                                </button>
                            </td>
                            <td class="text-end text-nowrap">
                                <!-- Edit -->
                                <button class="btn btn-sm btn-outline-primary" title="Edit user"
                                        data-bs-toggle="modal" data-bs-target="#editUserModal"
                                        data-id="<?= $uid ?>"
                                        data-name="<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>"
                                        data-email="<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>"
                                        data-role="<?= htmlspecialchars($u['role'], ENT_QUOTES) ?>"
                                        data-phone="<?= htmlspecialchars($u['phone'] ?? '', ENT_QUOTES) ?>"
                                        data-spec="<?= htmlspecialchars($u['specialization'] ?? '', ENT_QUOTES) ?>">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <!-- Change Password -->
                                <button class="btn btn-sm btn-outline-warning" title="Change password"
                                        data-bs-toggle="modal" data-bs-target="#pwdModal"
                                        data-id="<?= $uid ?>"
                                        data-name="<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>">
                                    <i class="fas fa-key"></i>
                                </button>

                                <!-- Toggle Status -->
                                <?php if (!$isSelf): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('<?= $isActive ? 'Deactivate' : 'Activate' ?> this user?');">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $uid ?>">
                                        <button class="btn btn-sm btn-outline-<?= $isActive ? 'secondary' : 'success' ?>"
                                                title="<?= $isActive ? 'Deactivate' : 'Activate' ?>">
                                            <i class="fas fa-<?= $isActive ? 'toggle-on' : 'toggle-off' ?>"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Delete -->
                                <?php if (!$isSelf): ?>
                                    <a href="?delete=<?= $uid ?>"
                                       class="btn btn-sm btn-outline-danger confirm-delete" title="Delete user">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     EDIT USER MODAL
     ============================================================ -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-edit me-2 text-primary"></i>Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input name="name" id="edit_name" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role</label>
                    <select name="role" id="edit_role" class="form-select">
                        <option value="doctor">Doctor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input name="phone" id="edit_phone" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Specialization</label>
                    <input name="specialization" id="edit_spec" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     CHANGE PASSWORD MODAL
     ============================================================ -->
<div class="modal fade" id="pwdModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="id" id="pwd_id">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key me-2 text-warning"></i>Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">
                    Setting a new password for <strong id="pwd_name"></strong>.
                </p>
                <label class="form-label">New Password <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="password" name="new_password" id="pwd_input"
                           class="form-control" required minlength="6">
                    <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="pwd_input">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small class="text-muted">Minimum 6 characters.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-warning"><i class="fas fa-key me-1"></i> Change Password</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     ACTIVITY MODAL
     ============================================================ -->
<div class="modal fade" id="activityModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header activity-modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="activity-avatar">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="act_name"></h5>
                        <small class="text-muted" id="act_email"></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-4 col-md-3">
                        <div class="mini-stat">
                            <div class="mini-value text-primary" id="act_patients">0</div>
                            <div class="mini-label">Patients</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-3">
                        <div class="mini-stat">
                            <div class="mini-value text-success" id="act_operations">0</div>
                            <div class="mini-label">Operations</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-3">
                        <div class="mini-stat">
                            <div class="mini-value text-warning" id="act_followups">0</div>
                            <div class="mini-label">Follow-ups</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-3">
                        <div class="mini-stat">
                            <div class="mini-value text-info" id="act_imaging">0</div>
                            <div class="mini-label">Reports</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-3">
                        <div class="mini-stat">
                            <div class="mini-value text-danger" id="act_discharges">0</div>
                            <div class="mini-label">Discharges</div>
                        </div>
                    </div>
                    <div class="col-8 col-md-9 d-flex align-items-center">
                        <div class="mini-stat w-100 text-start">
                            <div class="mini-label">Last Activity</div>
                            <div class="fw-bold" id="act_last">—</div>
                        </div>
                    </div>
                </div>
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr><td class="text-muted small" style="width:140px">Role</td><td><span id="act_role" class="badge bg-primary">—</span></td></tr>
                        <tr><td class="text-muted small">Specialization</td><td id="act_spec">—</td></tr>
                        <tr><td class="text-muted small">Phone</td><td id="act_phone">—</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     STYLES
     ============================================================ -->
<style>
/* ============ HERO HEADER ============ */
.users-hero {
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
        radial-gradient(circle at 15% 20%, rgba(255,255,255,.15) 0%, transparent 45%),
        radial-gradient(circle at 85% 80%, rgba(255,255,255,.10) 0%, transparent 50%);
    pointer-events: none;
}
.hero-content {
    position: relative;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
}
.hero-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    background: rgba(255,255,255,.22);
    border: 2px solid rgba(255,255,255,.5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #fff;
    flex-shrink: 0;
}
.hero-info h4 { font-weight: 700; letter-spacing: .2px; }

/* ============ STAT TILES ============ */
.stat-tile {
    background: #fff;
    border: 1px solid #e9eef5;
    border-radius: 10px;
    padding: 12px;
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

/* ============ USERS TABLE ============ */
.users-table { font-size: 13px; }
.users-table thead th {
    background: #f8fafc;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .3px;
    color: #64748b;
    font-weight: 700;
    padding: 10px 12px;
    white-space: nowrap;
}
.users-table tbody td { padding: 10px 12px; vertical-align: middle; }
.user-name { display: flex; align-items: center; gap: 6px; }
.row-inactive { background: #fffbeb; }
.row-inactive:hover { background: #fef3c7; }

/* ============ ACTIVITY MODAL ============ */
.activity-modal-header {
    background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
    color: #fff;
}
.activity-avatar {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: rgba(255,255,255,.22);
    border: 2px solid rgba(255,255,255,.5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #fff;
}
.mini-stat {
    background: #f8fafc;
    border: 1px solid #eef2f7;
    border-radius: 8px;
    padding: 10px 8px;
    text-align: center;
    height: 100%;
}
.mini-value { font-size: 22px; font-weight: 800; line-height: 1; }
.mini-label { font-size: 10px; text-transform: uppercase; letter-spacing: .3px; color: #64748b; font-weight: 600; margin-top: 4px; }

/* ============ SHOW/HIDE PASSWORD ============ */
.btn-eye {
    cursor: pointer;
}

/* ============ RESPONSIVE ============ */
@media (max-width: 991px) {
    .users-table { font-size: 12px; }
    .users-table thead th { padding: 8px 6px; font-size: 10px; }
    .users-table tbody td { padding: 8px 6px; }
}
@media (max-width: 576px) {
    .hero-content { padding: 16px; }
    .hero-icon { width: 46px; height: 46px; font-size: 20px; }
    .hero-info h4 { font-size: 1.05rem; }
    .stat-value { font-size: 18px; }
    .stat-icon { width: 34px; height: 34px; font-size: 14px; }
    .users-table .btn-sm { padding: 3px 6px; font-size: 10px; }
}
</style>

<!-- ============================================================
     MODAL DATA BINDING
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ---- Edit User Modal ----
    const editModal = document.getElementById('editUserModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('edit_id').value    = btn.dataset.id;
        document.getElementById('edit_name').value  = btn.dataset.name;
        document.getElementById('edit_email').value = btn.dataset.email;
        document.getElementById('edit_role').value  = btn.dataset.role;
        document.getElementById('edit_phone').value = btn.dataset.phone;
        document.getElementById('edit_spec').value  = btn.dataset.spec;
    });

    // ---- Change Password Modal ----
    const pwdModal = document.getElementById('pwdModal');
    pwdModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('pwd_id').value    = btn.dataset.id;
        document.getElementById('pwd_name').textContent = btn.dataset.name;
        document.getElementById('pwd_input').value = '';
    });

    // ---- Activity Modal ----
    const actModal = document.getElementById('activityModal');
    actModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('act_name').textContent     = btn.dataset.name;
        document.getElementById('act_email').textContent    = btn.dataset.email;
        document.getElementById('act_role').textContent     = btn.dataset.role.charAt(0).toUpperCase() + btn.dataset.role.slice(1);
        document.getElementById('act_role').className       = 'badge bg-' + (btn.dataset.role === 'admin' ? 'danger' : 'primary');
        document.getElementById('act_spec').textContent     = btn.dataset.spec || '—';
        document.getElementById('act_phone').textContent    = btn.dataset.phone || '—';
        document.getElementById('act_patients').textContent   = btn.dataset.patients;
        document.getElementById('act_operations').textContent = btn.dataset.operations;
        document.getElementById('act_followups').textContent  = btn.dataset.followups;
        document.getElementById('act_imaging').textContent    = btn.dataset.imaging;
        document.getElementById('act_discharges').textContent = btn.dataset.discharges;
        document.getElementById('act_last').textContent       = btn.dataset.last;
    });

    // ---- Show / Hide Password ----
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
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>