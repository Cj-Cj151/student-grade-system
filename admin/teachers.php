<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

$pdo = getDbConnection();

$teachers = $pdo->query('
    SELECT t.teacher_id, t.first_name, t.last_name, t.email, t.department,
           u.user_id, u.login_id, u.is_active
    FROM teachers t
    JOIN users u ON u.user_id = t.user_id
    ORDER BY t.last_name, t.first_name
')->fetchAll();

$pageTitle = 'Teachers';
$pageSubtitle = 'Manage teacher records and accounts';
$activeNav = 'teachers';
include __DIR__ . '/../includes/header.php';
?>

<div class="glass-card panel">
    <div class="panel-header">
        <h2 class="panel-title">All Teachers</h2>
        <div class="toolbar">
            <div class="search-input-wrap">
                <span class="search-icon">&#128269;</span>
                <input type="text" id="searchInput" class="form-control search-input" placeholder="Search teachers...">
            </div>
            <button type="button" class="btn btn-primary" onclick="openAddModal()">+ Add Teacher</button>
        </div>
    </div>

    <?php if (empty($teachers)): ?>
        <div class="empty-state">
            <div class="empty-icon">&#128104;</div>
            <p>No teachers yet. Click "Add Teacher" to create one.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table" id="teachersTable">
                <thead>
                    <tr><th>Teacher ID</th><th>Name</th><th>Email</th><th>Department</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers as $t): ?>
                        <tr data-search="<?= clean(strtolower($t['login_id'] . ' ' . $t['first_name'] . ' ' . $t['last_name'] . ' ' . $t['email'])) ?>">
                            <td><?= clean($t['login_id']) ?></td>
                            <td><?= clean($t['first_name'] . ' ' . $t['last_name']) ?></td>
                            <td class="cell-muted"><?= clean($t['email']) ?></td>
                            <td><?= clean($t['department']) ?></td>
                            <td><span class="badge <?= $t['is_active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $t['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                            <td class="row-actions">
                                <button type="button" class="btn btn-secondary btn-sm" onclick='openEditModal(<?= json_encode($t) ?>)'>Edit</button>
                                <button type="button" class="btn btn-sm <?= $t['is_active'] ? 'btn-danger' : 'btn-primary' ?>"
                                    onclick="toggleStatus(<?= (int) $t['user_id'] ?>, <?= $t['is_active'] ? 'false' : 'true' ?>)">
                                    <?= $t['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="empty-state" id="noResultsState" style="display:none;">No teachers match your search.</p>
    <?php endif; ?>
</div>

<!-- Add / Edit Teacher Modal -->
<div class="modal-overlay" id="teacherModal">
    <div class="glass-card modal-box">
        <div class="modal-header">
            <h3 class="modal-title" id="teacherModalTitle">Add Teacher</h3>
            <button type="button" class="modal-close" onclick="closeModal('teacherModal')">&times;</button>
        </div>
        <div id="teacherModalAlert" class="alert alert-error"></div>
        <form id="teacherForm">
            <input type="hidden" id="teacher_id" value="">
            <input type="hidden" id="user_id" value="">

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="department">Department</label>
                <input type="text" id="department" class="form-control" placeholder="e.g. Mathematics" required>
            </div>

            <div class="form-group">
                <label for="login_id">Teacher ID (login)</label>
                <input type="text" id="login_id" class="form-control" placeholder="e.g. T-1003" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="text" id="password" class="form-control" placeholder="Initial password">
                <div class="form-hint">Leave blank when editing to keep the current password.</div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('teacherModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="teacherSaveBtn">Save Teacher</button>
            </div>
        </form>
    </div>
</div>

<?php
$extraScript = <<<'JS'
const searchInput = document.getElementById('searchInput');
const rows = document.querySelectorAll('#teachersTable tbody tr');
const noResultsState = document.getElementById('noResultsState');

if (searchInput) {
    searchInput.addEventListener('input', () => {
        const search = searchInput.value.trim().toLowerCase();
        let visible = 0;
        rows.forEach((row) => {
            const show = row.dataset.search.includes(search);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (noResultsState) noResultsState.style.display = visible === 0 ? 'block' : 'none';
    });
}

function openAddModal() {
    document.getElementById('teacherForm').reset();
    document.getElementById('teacher_id').value = '';
    document.getElementById('user_id').value = '';
    document.getElementById('teacherModalTitle').textContent = 'Add Teacher';
    document.getElementById('password').required = true;
    document.getElementById('password').placeholder = 'Initial password';
    hideTeacherAlert();
    openModal('teacherModal');
}

function openEditModal(teacher) {
    document.getElementById('teacherForm').reset();
    document.getElementById('teacher_id').value = teacher.teacher_id;
    document.getElementById('user_id').value = teacher.user_id;
    document.getElementById('first_name').value = teacher.first_name;
    document.getElementById('last_name').value = teacher.last_name;
    document.getElementById('email').value = teacher.email;
    document.getElementById('department').value = teacher.department;
    document.getElementById('login_id').value = teacher.login_id;
    document.getElementById('password').required = false;
    document.getElementById('password').placeholder = 'Leave blank to keep current password';
    document.getElementById('teacherModalTitle').textContent = 'Edit Teacher';
    hideTeacherAlert();
    openModal('teacherModal');
}

function showTeacherAlert(message) {
    const alertBox = document.getElementById('teacherModalAlert');
    alertBox.textContent = message;
    alertBox.classList.add('show');
}
function hideTeacherAlert() {
    document.getElementById('teacherModalAlert').classList.remove('show');
}

document.getElementById('teacherForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    hideTeacherAlert();

    const payload = {
        teacher_id: document.getElementById('teacher_id').value || null,
        user_id: document.getElementById('user_id').value || null,
        first_name: document.getElementById('first_name').value.trim(),
        last_name: document.getElementById('last_name').value.trim(),
        email: document.getElementById('email').value.trim(),
        department: document.getElementById('department').value.trim(),
        login_id: document.getElementById('login_id').value.trim(),
        password: document.getElementById('password').value,
    };

    const saveBtn = document.getElementById('teacherSaveBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner"></span> Saving...';

    const result = await apiFetch('/api/teachers.php', {
        method: 'POST',
        body: JSON.stringify(payload),
    });

    saveBtn.disabled = false;
    saveBtn.textContent = 'Save Teacher';

    if (result.success) {
        showToast(result.message || 'Teacher saved.', 'success');
        setTimeout(() => window.location.reload(), 700);
    } else {
        showTeacherAlert(result.message || 'Unable to save teacher.');
    }
});

async function toggleStatus(userId, makeActive) {
    const ok = await confirmAction(
        makeActive ? 'Activate this teacher account?' : 'Deactivate this teacher account?',
        makeActive ? 'Activate' : 'Deactivate'
    );
    if (!ok) return;

    const result = await apiFetch('/api/users.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'toggle_status', user_id: userId, is_active: makeActive }),
    });

    if (result.success) {
        showToast(result.message || 'Status updated.', 'success');
        setTimeout(() => window.location.reload(), 600);
    } else {
        showToast(result.message || 'Unable to update status.', 'error');
    }
}
JS;
include __DIR__ . '/../includes/footer.php';
?>
