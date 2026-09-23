<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'Operation Types';

$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if (!$name) {
            $msg = 'Type name is required.';
            $msgType = 'danger';
        } else {
            $chk = $conn->prepare("SELECT id FROM op_types WHERE name=?");
            $chk->bind_param('s', $name);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $msg = 'That type name already exists.';
                $msgType = 'danger';
            } else {
                $stmt = $conn->prepare("INSERT INTO op_types (name, description) VALUES (?,?)");
                $stmt->bind_param('ss', $name, $desc);
                $stmt->execute();
                header("Location: op_types.php?saved=1"); exit;
            }
        }
    }

    if ($action === 'update') {
        $id   = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if (!$id || !$name) {
            $msg = 'Type name is required.';
            $msgType = 'danger';
        } else {
            $chk = $conn->prepare("SELECT id FROM op_types WHERE name=? AND id!=?");
            $chk->bind_param('si', $name, $id);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $msg = 'That type name is already in use.';
                $msgType = 'danger';
            } else {
                $stmt = $conn->prepare("UPDATE op_types SET name=?, description=? WHERE id=?");
                $stmt->bind_param('ssi', $name, $desc, $id);
                $stmt->execute();
                header("Location: op_types.php?saved=1"); exit;
            }
        }
    }

    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $conn->query("UPDATE op_types SET status = 1 - status WHERE id=$id");
        header("Location: op_types.php?saved=1"); exit;
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Prevent deleting type with subtypes
    $chk = $conn->query("SELECT COUNT(*) c FROM op_subtypes WHERE type_id=$id")->fetch_assoc();
    if ($chk['c'] > 0) {
        header("Location: op_types.php?error=has_subtypes"); exit;
    }
    $conn->query("DELETE FROM op_types WHERE id=$id");
    header("Location: op_types.php?deleted=1"); exit;
}

$types = $conn->query("
    SELECT t.*, 
           (SELECT COUNT(*) FROM op_subtypes WHERE type_id = t.id) AS subtype_count
    FROM op_types t
    ORDER BY t.id ASC
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-layer-group text-primary me-2"></i>Operation Types</h5>
        <small class="text-muted">Manage top-level operation types (Endoscopic, Laparoscopic, Open).</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTypeModal">
        <i class="fas fa-plus me-1"></i> New Type
    </button>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> Saved.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        <i class="fas fa-trash me-1"></i> Type deleted.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['error']) && $_GET['error'] === 'has_subtypes'): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-1"></i> Cannot delete — type has sub-types assigned.
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
        <span><i class="fas fa-list me-2 text-primary"></i>All Operation Types</span>
        <span class="badge bg-primary"><?= $types->num_rows ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th style="width:120px" class="text-center">Sub-Types</th>
                    <th style="width:100px">Status</th>
                    <th style="width:180px" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($t = $types->fetch_assoc()): ?>
                <tr class="<?= !$t['status'] ? 'table-warning' : '' ?>">
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($t['name']) ?></strong></td>
                    <td><?= htmlspecialchars($t['description'] ?: '—') ?></td>
                    <td class="text-center">
                        <a href="op_subtypes.php?type_id=<?= $t['id'] ?>" class="badge bg-primary text-decoration-none">
                            <?= (int)$t['subtype_count'] ?>
                        </a>
                    </td>
                    <td>
                        <?php if ($t['status']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary" title="Edit"
                                data-bs-toggle="modal" data-bs-target="#editTypeModal"
                                data-id="<?= $t['id'] ?>"
                                data-name="<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>"
                                data-desc="<?= htmlspecialchars($t['description'] ?? '', ENT_QUOTES) ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <button class="btn btn-sm btn-outline-<?= $t['status'] ? 'secondary' : 'success' ?>">
                                <i class="fas fa-<?= $t['status'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                            </button>
                        </form>
                        <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">No operation types yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- CREATE TYPE MODAL -->
<div class="modal fade" id="createTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-layer-group me-2 text-primary"></i>New Operation Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input name="name" class="form-control" required placeholder="e.g. Robotic">
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

<!-- EDIT TYPE MODAL -->
<div class="modal fade" id="editTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_type_id">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2 text-primary"></i>Edit Operation Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input name="name" id="edit_type_name" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_type_desc" class="form-control" rows="2"></textarea>
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
document.getElementById('editTypeModal').addEventListener('show.bs.modal', function (e) {
    const b = e.relatedTarget;
    document.getElementById('edit_type_id').value   = b.dataset.id;
    document.getElementById('edit_type_name').value = b.dataset.name;
    document.getElementById('edit_type_desc').value = b.dataset.desc;
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>