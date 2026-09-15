<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

$pdo = getDbConnection();

$students = $pdo->query('
    SELECT st.student_id, st.first_name, st.last_name, st.email, st.course, st.year_level,
           u.user_id, u.login_id, u.is_active
    FROM students st
    JOIN users u ON u.user_id = st.user_id
    ORDER BY st.last_name, st.first_name
')->fetchAll();

$pageTitle = 'Students';
$pageSubtitle = 'Manage student records and accounts';
$activeNav = 'students';
include __DIR__ . '/../includes/header.php';
?>

<div class="glass-card panel">
    <div class="panel-header">
        <h2 class="panel-title">All Students</h2>
        <div class="toolbar">
            <div class="search-input-wrap">
                <span class="search-icon">&#128269;</span>
                <input type="text" id="searchInput" class="form-control search-input" placeholder="Search students...">
            </div>
            <button type="button" class="btn btn-primary" onclick="openAddModal()">+ Add Student</button>
        </div>
    </div>

    <?php if (empty($students)): ?>
        <div class="empty-state">
            <div class="empty-icon">&#127891;</div>
            <p>No students yet. Click "Add Student" to create one.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table" id="studentsTable">
                <thead>
                    <tr>
                        <th>Student ID</th><th>Name</th><th>Email</th><th>Course</th><th>Year</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr data-search="<?= clean(strtolower($s['login_id'] . ' ' . $s['first_name'] . ' ' . $s['last_name'] . ' ' . $s['email'])) ?>">
                            <td><?= clean($s['login_id']) ?></td>
                            <td><?= clean($s['first_name'] . ' ' . $s['last_name']) ?></td>
                            <td class="cell-muted"><?= clean($s['email']) ?></td>
                            <td><?= clean($s['course']) ?></td>
                            <td>Year <?= (int) $s['year_level'] ?></td>
                            <td><span class="badge <?= $s['is_active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $s['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                            <td class="row-actions">
                                <button type="button" class="btn btn-secondary btn-sm"
                                    onclick='openEditModal(<?= json_encode($s) ?>)'>Edit</button>
                                <button type="button" class="btn btn-sm <?= $s['is_active'] ? 'btn-danger' : 'btn-primary' ?>"
                                    onclick="toggleStatus(<?= (int) $s['user_id'] ?>, <?= $s['is_active'] ? 'false' : 'true' ?>)">
                                    <?= $s['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="empty-state" id="noResultsState" style="display:none;">No students match your search.</p>
    <?php endif; ?>
</div>

<!-- Add / Edit Student Modal -->
<div class="modal-overlay" id="studentModal">
    <div class="glass-card modal-box">
        <div class="modal-header">
            <h3 class="modal-title" id="studentModalTitle">Add Student</h3>
            <button type="button" class="modal-close" onclick="closeModal('studentModal')">&times;</button>
        </div>
        <div id="studentModalAlert" class="alert alert-error"></div>
        <form id="studentForm">
            <input type="hidden" id="student_id" value="">
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

            <div class="form-row">
                <div class="form-group">
                    <label for="course">Course</label>
                    <input type="text" id="course" class="form-control" placeholder="e.g. BS Computer Science" required>
                </div>
                <div class="form-group">
                    <label for="year_level">Year Level</label>
                    <select id="year_level" class="form-control" required>
                        <option value="1">Year 1</option>
                        <option value="2">Year 2</option>
                        <option value="3">Year 3</option>
                        <option value="4">Year 4</option>
                        <option value="5">Year 5</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="login_id">Student ID (login)</label>
                <input type="text" id="login_id" class="form-control" placeholder="e.g. S-2005" required>
            </div>

            <div class="form-group" id="passwordGroup">
                <label for="password">Password</label>
                <input type="text" id="password" class="form-control" placeholder="Initial password">
                <div class="form-hint">Leave blank when editing to keep the current password.</div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('studentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="studentSaveBtn">Save Student</button>
            </div>
        </form>
    </div>
</div>

<?php
$extraScript = <<<'JS'
const searchInput = document.getElementById('searchInput');
const rows = document.querySelectorAll('#studentsTable tbody tr');
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
    document.getElementById('studentForm').reset();
    document.getElementById('student_id').value = '';
    document.getElementById('user_id').value = '';
    document.getElementById('studentModalTitle').textContent = 'Add Student';
    document.getElementById('login_id').disabled = false;
    document.getElementById('password').placeholder = 'Initial password';
    document.getElementById('password').required = true;
    hideStudentAlert();
    openModal('studentModal');
}

function openEditModal(student) {
    document.getElementById('studentForm').reset();
    document.getElementById('student_id').value = student.student_id;
    document.getElementById('user_id').value = student.user_id;
    document.getElementById('first_name').value = student.first_name;
    document.getElementById('last_name').value = student.last_name;
    document.getElementById('email').value = student.email;
    document.getElementById('course').value = student.course;
    document.getElementById('year_level').value = student.year_level;
    document.getElementById('login_id').value = student.login_id;
    document.getElementById('password').required = false;
    document.getElementById('password').placeholder = 'Leave blank to keep current password';
    document.getElementById('studentModalTitle').textContent = 'Edit Student';
    hideStudentAlert();
    openModal('studentModal');
}

function showStudentAlert(message) {
    const alertBox = document.getElementById('studentModalAlert');
    alertBox.textContent = message;
    alertBox.classList.add('show');
}
function hideStudentAlert() {
    document.getElementById('studentModalAlert').classList.remove('show');
}

document.getElementById('studentForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    hideStudentAlert();

    const payload = {
        student_id: document.getElementById('student_id').value || null,
        user_id: document.getElementById('user_id').value || null,
        first_name: document.getElementById('first_name').value.trim(),
        last_name: document.getElementById('last_name').value.trim(),
        email: document.getElementById('email').value.trim(),
        course: document.getElementById('course').value.trim(),
        year_level: document.getElementById('year_level').value,
        login_id: document.getElementById('login_id').value.trim(),
        password: document.getElementById('password').value,
    };

    const saveBtn = document.getElementById('studentSaveBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner"></span> Saving...';

    const result = await apiFetch('/api/students.php', {
        method: 'POST',
        body: JSON.stringify(payload),
    });

    saveBtn.disabled = false;
    saveBtn.textContent = 'Save Student';

    if (result.success) {
        showToast(result.message || 'Student saved.', 'success');
        setTimeout(() => window.location.reload(), 700);
    } else {
        showStudentAlert(result.message || 'Unable to save student.');
    }
});

async function toggleStatus(userId, makeActive) {
    const ok = await confirmAction(
        makeActive ? 'Activate this student account?' : 'Deactivate this student account?',
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
