<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'Operation Sub-Types';

$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $type_id = intval($_POST['type_id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $desc    = trim($_POST['description'] ?? '');

        if (!$type_id || !$name) {
            $msg = 'Type and Name are required.';
            $msgType = 'danger';
        } else {
            $chk = $conn->prepare("SELECT id FROM op_subtypes WHERE type_id=? AND name=?");
            $chk->bind_param('is', $type_id, $name);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $msg = 'Sub-type already exists for that operation type.';
                $msgType = 'danger';
            } else {
                $stmt = $conn->prepare("INSERT INTO op_subtypes (type_id, name, description) VALUES (?,?,?)");
                $stmt->bind_param('iss', $type_id, $name, $desc);
                $stmt->execute();
                header("Location: op_subtypes.php?saved=1"); exit;
            }
        }
    }

    if ($action === 'update') {
        $id      = intval($_POST['id'] ?? 0);
        $type_id = intval($_POST['type_id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $desc    = trim($_POST['description'] ?? '');

        if (!$id || !$type_id || !$name) {
            $msg = 'All required fields must be filled.';
            $msgType = 'danger';
        } else {
            $stmt = $conn->prepare("UPDATE op_subtypes SET type_id=?, name=?, description=? WHERE id=?");
            $stmt->bind_param('issi', $type_id, $name, $desc, $id);
            $stmt->execute();
            header("Location: op_subtypes.php?saved=1"); exit;
        }
    }

    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $conn->query("UPDATE op_subtypes SET status = 1 - status WHERE id=$id");
        header("Location: op_subtypes.php?saved=1"); exit;
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM op_subtypes WHERE id=$id");
    header("Location: op_subtypes.php?deleted=1"); exit;
}

$types = $conn->query("SELECT id, name FROM op_types WHERE status=1 ORDER BY name");

$filterType = intval($_GET['type_id'] ?? 0);
$whereSQL = $filterType ? "WHERE s.type_id = $filterType" : '';

$subtypes = $conn->query("
    SELECT s.*, t.name AS type_name
    FROM op_subtypes s
    LEFT JOIN op_types t ON t.id = s.type_id
    $whereSQL
    ORDER BY t.name, s.name
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="fas fa-list-ul text-primary me-2"></i>Operation Sub-Types</h5>
        <small class="text-muted">Manage sub-types grouped under each Operation Type.</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSubModal">
        <i class="fas fa-plus me-1"></i> New Sub-Type
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
        <i class="fas fa-trash me-1"></i> Sub-type deleted.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2 text-primary"></i>All Sub-Types</span>
        <span class="badge bg-primary"><?= $subtypes->num_rows ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th>Operation Type</th>
                    <th>Sub-Type Name</th>
                    <th>Description</th>
                    <th style="width:100px">Status</th>
                    <th style="width:180px" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($s = $subtypes->fetch_assoc()): ?>
                <tr class="<?= !$s['status'] ? 'table-warning' : '' ?>">
                    <td><?= $i++ ?></td>
                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($s['type_name'] ?? '—') ?></span></td>
                    <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                    <td><?= htmlspecialchars($s['description'] ?: '—') ?></td>
                    <td>
                        <?php if ($s['status']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#editSubModal"
                                data-id="<?= $s['id'] ?>"
                                data-type-id="<?= $s['type_id'] ?>"
                                data-name="<?= htmlspecialchars($s['name'], ENT_QUOTES) ?>"
                                data-desc="<?= htmlspecialchars($s['description'] ?? '', ENT_QUOTES) ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button class="btn btn-sm btn-outline-<?= $s['status'] ? 'secondary' : 'success' ?>">
                                <i class="fas fa-<?= $s['status'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                            </button>
                        </form>
                        <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; if ($i === 1): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">No sub-types yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- CREATE SUB-TYPE MODAL -->
<div class="modal fade" id="createSubModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-list-ul me-2 text-primary"></i>New Sub-Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Operation Type <span class="text-danger">*</span></label>
                    <select name="type_id" class="form-select" required>
                        <option value="">— Select type —</option>
                        <?php $types->data_seek(0); while ($t = $types->fetch_assoc()): ?>
                            <option value="<?= $t['id'] ?>" <?= $filterType == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Sub-Type Name <span class="text-danger">*</span></label>
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

<!-- EDIT SUB-TYPE MODAL -->
<div class="modal fade" id="editSubModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_sub_id">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2 text-primary"></i>Edit Sub-Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Operation Type <span class="text-danger">*</span></label>
                    <select name="type_id" id="edit_sub_type" class="form-select" required>
                        <?php $types->data_seek(0); while ($t = $types->fetch_assoc()): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Sub-Type Name <span class="text-danger">*</span></label>
                    <input name="name" id="edit_sub_name" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_sub_desc" class="form-control" rows="2"></textarea>
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
document.getElementById('editSubModal').addEventListener('show.bs.modal', function (e) {
    const b = e.relatedTarget;
    document.getElementById('edit_sub_id').value    = b.dataset.id;
    document.getElementById('edit_sub_type').value  = b.dataset.typeId;
    document.getElementById('edit_sub_name').value  = b.dataset.name;
    document.getElementById('edit_sub_desc').value  = b.dataset.desc;
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>