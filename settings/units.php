<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'Units';

$msg = '';
$msgType = 'success';

// ---------- POST ACTIONS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CREATE
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if (!$name) {
            $msg = 'Unit name is required.';
            $msgType = 'danger';
        } else {
            $chk = $conn->prepare("SELECT id FROM units WHERE name=?");
            $chk->bind_param('s', $name);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $msg = 'That unit name already exists.';
                $msgType = 'danger';
            } else {
                $stmt = $conn->prepare("INSERT INTO units (name, code, description) VALUES (?,?,?)");
                $stmt->bind_param('sss', $name, $code, $desc);
                $stmt->execute();
                header("Location: units.php?saved=1"); exit;
            }
        }
    }

    // UPDATE
    if ($action === 'update') {
        $id   = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if (!$id || !$name) {
            $msg = 'Unit name is required.';
            $msgType = 'danger';
        } else {
            $chk = $conn->prepare("SELECT id FROM units WHERE name=? AND id!=?");
            $chk->bind_param('si', $name, $id);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $msg = 'That unit name is already in use.';
                $msgType = 'danger';
            } else {
                $stmt = $conn->prepare("UPDATE units SET name=?, code=?, description=? WHERE id=?");
                $stmt->bind_param('sssi', $name, $code, $desc, $id);
                $stmt->execute();
                header("Location: units.php?saved=1"); exit;
            }
        }
    }

    // TOGGLE STATUS
    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $conn->query("UPDATE units SET status = 1 - status WHERE id=$id");
        header("Location: units.php?saved=1"); exit;
    }
}

// ---------- DELETE (GET) ----------
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Prevent deleting unit with wards
    $chk = $conn->query("SELECT COUNT(*) c FROM wards WHERE unit_id=$id")->fetch_assoc();
    if ($chk['c'] > 0) {
        header("Location: units.php?error=has_wards"); exit;
    }
    $conn->query("DELETE FROM units WHERE id=$id");
    header("Location: units.php?deleted=1"); exit;
}

$units = $conn->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM wards WHERE unit_id = u.id) AS ward_count
    FROM units u
    ORDER BY u.id ASC
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-building text-primary me-2"></i>Units</h5>
        <small class="text-muted">Manage hospital units. Wards are assigned to units.</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUnitModal">
        <i class="fas fa-plus me-1"></i> New Unit
    </button>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Saved successfully.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        <i class="fas fa-trash me-1"></i> Unit deleted.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['error']) && $_GET['error'] === 'has_wards'): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-1"></i> Cannot delete — unit has wards assigned.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-1"></i> <?= htmlspecialchars($msg) ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2 text-primary"></i>All Units</span>
        <span class="badge bg-primary"><?= $units->num_rows ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th>Name</th>
                    <th style="width:100px">Code</th>
                    <th>Description</th>
                    <th style="width:100px" class="text-center">Wards</th>
                    <th style="width:100px">Status</th>
                    <th style="width:180px" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($u = $units->fetch_assoc()): ?>
                <tr class="<?= !$u['status'] ? 'table-warning' : '' ?>">
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($u['code'] ?: '—') ?></span></td>
                    <td><?= htmlspecialchars($u['description'] ?: '—') ?></td>
                    <td class="text-center">
                        <a href="wards.php?unit_id=<?= $u['id'] ?>" class="badge bg-primary text-decoration-none">
                            <?= (int)$u['ward_count'] ?>
                        </a>
                    </td>
                    <td>
                        <?php if ($u['status']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary" title="Edit"
                                data-bs-toggle="modal" data-bs-target="#editUnitModal"
                                data-id="<?= $u['id'] ?>"
                                data-name="<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>"
                                data-code="<?= htmlspecialchars($u['code'] ?? '', ENT_QUOTES) ?>"
                                data-desc="<?= htmlspecialchars($u['description'] ?? '', ENT_QUOTES) ?>">
                            <i class="fas fa-edit"></i>
                        </button>

                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button class="btn btn-sm btn-outline-<?= $u['status'] ? 'secondary' : 'success' ?>" title="Toggle status">
                                <i class="fas fa-<?= $u['status'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                            </button>
                        </form>

                        <a href="?delete=<?= $u['id'] ?>"
                           class="btn btn-sm btn-outline-danger confirm-delete" title="Delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No units yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- ============================================================
     CREATE UNIT MODAL
     ============================================================ -->
<div class="modal fade" id="createUnitModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-building me-2 text-primary"></i>New Unit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input name="name" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Code</label>
                    <input name="code" class="form-control" placeholder="e.g. U1">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary"><i class="fas fa-save me-1"></i> Create</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     EDIT UNIT MODAL
     ============================================================ -->
<div class="modal fade" id="editUnitModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_unit_id">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2 text-primary"></i>Edit Unit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input name="name" id="edit_unit_name" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Code</label>
                    <input name="code" id="edit_unit_code" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_unit_desc" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary"><i class="fas fa-save me-1"></i> Update</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('editUnitModal').addEventListener('show.bs.modal', function (e) {
    const b = e.relatedTarget;
    document.getElementById('edit_unit_id').value   = b.dataset.id;
    document.getElementById('edit_unit_name').value = b.dataset.name;
    document.getElementById('edit_unit_code').value = b.dataset.code;
    document.getElementById('edit_unit_desc').value = b.dataset.desc;
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>