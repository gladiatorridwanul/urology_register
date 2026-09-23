<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'Wards';

$msg = '';
$msgType = 'success';

// ---------- POST ACTIONS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $unit_id = intval($_POST['unit_id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $desc    = trim($_POST['description'] ?? '');

        if (!$unit_id || !$name) {
            $msg = 'Unit and Ward name are required.';
            $msgType = 'danger';
        } else {
            $chk = $conn->prepare("SELECT id FROM wards WHERE unit_id=? AND name=?");
            $chk->bind_param('is', $unit_id, $name);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $msg = 'Ward already exists under that unit.';
                $msgType = 'danger';
            } else {
                $stmt = $conn->prepare("INSERT INTO wards (unit_id, name, description) VALUES (?,?,?)");
                $stmt->bind_param('iss', $unit_id, $name, $desc);
                $stmt->execute();
                header("Location: wards.php?saved=1"); exit;
            }
        }
    }

    if ($action === 'update') {
        $id      = intval($_POST['id'] ?? 0);
        $unit_id = intval($_POST['unit_id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $desc    = trim($_POST['description'] ?? '');

        if (!$id || !$unit_id || !$name) {
            $msg = 'All required fields must be filled.';
            $msgType = 'danger';
        } else {
            $stmt = $conn->prepare("UPDATE wards SET unit_id=?, name=?, description=? WHERE id=?");
            $stmt->bind_param('issi', $unit_id, $name, $desc, $id);
            $stmt->execute();
            header("Location: wards.php?saved=1"); exit;
        }
    }

    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $conn->query("UPDATE wards SET status = 1 - status WHERE id=$id");
        header("Location: wards.php?saved=1"); exit;
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $chk = $conn->query("SELECT COUNT(*) c FROM beds WHERE ward_id=$id")->fetch_assoc();
    if ($chk['c'] > 0) {
        header("Location: wards.php?error=has_beds"); exit;
    }
    $conn->query("DELETE FROM wards WHERE id=$id");
    header("Location: wards.php?deleted=1"); exit;
}

$units = $conn->query("SELECT id, name FROM units WHERE status=1 ORDER BY name");

$filterUnit = intval($_GET['unit_id'] ?? 0);
$whereSQL = $filterUnit ? "WHERE w.unit_id = $filterUnit" : '';

$wards = $conn->query("
    SELECT w.*, u.name AS unit_name,
           (SELECT COUNT(*) FROM beds WHERE ward_id = w.id) AS bed_count
    FROM wards w
    LEFT JOIN units u ON u.id = w.unit_id
    $whereSQL
    ORDER BY u.name, w.name
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-hospital text-primary me-2"></i>Wards</h5>
        <small class="text-muted">Manage wards. Beds are assigned to wards.</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWardModal">
        <i class="fas fa-plus me-1"></i> New Ward
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
        <i class="fas fa-trash me-1"></i> Ward deleted.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['error']) && $_GET['error'] === 'has_beds'): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-1"></i> Cannot delete — ward has beds assigned.
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
        <span><i class="fas fa-list me-2 text-primary"></i>All Wards</span>
        <span class="badge bg-primary"><?= $wards->num_rows ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th>Unit</th>
                    <th>Ward Name</th>
                    <th>Description</th>
                    <th style="width:100px" class="text-center">Beds</th>
                    <th style="width:100px">Status</th>
                    <th style="width:180px" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($w = $wards->fetch_assoc()): ?>
                <tr class="<?= !$w['status'] ? 'table-warning' : '' ?>">
                    <td><?= $i++ ?></td>
                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($w['unit_name'] ?? '—') ?></span></td>
                    <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
                    <td><?= htmlspecialchars($w['description'] ?: '—') ?></td>
                    <td class="text-center">
                        <a href="beds.php?ward_id=<?= $w['id'] ?>" class="badge bg-primary text-decoration-none">
                            <?= (int)$w['bed_count'] ?>
                        </a>
                    </td>
                    <td>
                        <?php if ($w['status']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary" title="Edit"
                                data-bs-toggle="modal" data-bs-target="#editWardModal"
                                data-id="<?= $w['id'] ?>"
                                data-unit-id="<?= $w['unit_id'] ?>"
                                data-name="<?= htmlspecialchars($w['name'], ENT_QUOTES) ?>"
                                data-desc="<?= htmlspecialchars($w['description'] ?? '', ENT_QUOTES) ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $w['id'] ?>">
                            <button class="btn btn-sm btn-outline-<?= $w['status'] ? 'secondary' : 'success' ?>">
                                <i class="fas fa-<?= $w['status'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                            </button>
                        </form>
                        <a href="?delete=<?= $w['id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No wards yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- CREATE WARD MODAL -->
<div class="modal fade" id="createWardModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-hospital me-2 text-primary"></i>New Ward</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Unit <span class="text-danger">*</span></label>
                    <select name="unit_id" class="form-select" required>
                        <option value="">— Select unit —</option>
                        <?php $units->data_seek(0); while ($u = $units->fetch_assoc()): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Ward Name <span class="text-danger">*</span></label>
                    <input name="name" class="form-control" required>
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

<!-- EDIT WARD MODAL -->
<div class="modal fade" id="editWardModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_ward_id">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2 text-primary"></i>Edit Ward</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Unit <span class="text-danger">*</span></label>
                    <select name="unit_id" id="edit_ward_unit" class="form-select" required>
                        <?php $units->data_seek(0); while ($u = $units->fetch_assoc()): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Ward Name <span class="text-danger">*</span></label>
                    <input name="name" id="edit_ward_name" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_ward_desc" class="form-control" rows="2"></textarea>
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
document.getElementById('editWardModal').addEventListener('show.bs.modal', function (e) {
    const b = e.relatedTarget;
    document.getElementById('edit_ward_id').value   = b.dataset.id;
    document.getElementById('edit_ward_unit').value = b.dataset.unitId;
    document.getElementById('edit_ward_name').value = b.dataset.name;
    document.getElementById('edit_ward_desc').value = b.dataset.desc;
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>