<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

$pdo = getDbConnection();

$users = $pdo->query("
    SELECT u.user_id, u.login_id, u.role, u.is_active, u.created_at,
           COALESCE(s.first_name, t.first_name, 'System') AS first_name,
           COALESCE(s.last_name, t.last_name, 'Administrator') AS last_name
    FROM users u
    LEFT JOIN students s
        ON s.user_id = u.user_id
        AND u.role = 'student'
    LEFT JOIN teachers t
        ON t.user_id = u.user_id
        AND u.role = 'teacher'
    ORDER BY u.created_at DESC
")->fetchAll();

$pageTitle = 'User Accounts';
$pageSubtitle = 'View and manage all system accounts';
$activeNav = 'users';

include __DIR__ . '/../includes/header.php';
?>

<div class="glass-card panel">

    <div class="panel-header">

        <div>
            <h2 class="panel-title">All User Accounts</h2>

            <p class="panel-subtitle">
                View account status and manage user access
            </p>
        </div>

        <div class="toolbar">

            <div class="search-input-wrap">

                <input
                    type="text"
                    id="searchInput"
                    class="form-control search-input"
                    placeholder="Search users..."
                >

            </div>

            <select
                id="roleFilter"
                class="form-control"
            >
                <option value="all">All Roles</option>
                <option value="student">Students</option>
                <option value="teacher">Teachers</option>
                <option value="admin">Admins</option>
            </select>

        </div>

    </div>

    <?php if (empty($users)): ?>

        <div class="empty-state">

            <h3>No User Accounts</h3>

            <p>
                No user accounts have been registered yet.
            </p>

        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table
                class="data-table"
                id="usersTable"
            >

                <thead>

                    <tr>
                        <th>Login ID</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($users as $u): ?>

                        <tr
                            data-search="<?= clean(strtolower(
                                $u['login_id'] . ' ' .
                                $u['first_name'] . ' ' .
                                $u['last_name']
                            )) ?>"
                            data-role="<?= clean($u['role']) ?>"
                        >

                            <td>
                                <strong>
                                    <?= clean($u['login_id']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= clean(
                                    $u['first_name'] . ' ' . $u['last_name']
                                ) ?>
                            </td>

                            <td style="text-transform: capitalize;">
                                <?= clean($u['role']) ?>
                            </td>

                            <td>

                                <span
                                    class="badge <?= $u['is_active']
                                        ? 'badge-active'
                                        : 'badge-inactive' ?>"
                                >
                                    <?= $u['is_active']
                                        ? 'Active'
                                        : 'Inactive' ?>
                                </span>

                            </td>

                            <td class="cell-muted">
                                <?= date(
                                    'M j, Y',
                                    strtotime($u['created_at'])
                                ) ?>
                            </td>

                            <td class="row-actions">

                                <button
                                    type="button"
                                    class="btn btn-secondary btn-sm"
                                    onclick="openResetModal(
                                        <?= (int) $u['user_id'] ?>,
                                        '<?= clean($u['login_id']) ?>'
                                    )"
                                >
                                    Reset Password
                                </button>

                                <?php if (
                                    (int) $u['user_id'] !==
                                    (int) $_SESSION['user_id']
                                ): ?>

                                    <button
                                        type="button"
                                        class="btn btn-sm <?= $u['is_active']
                                            ? 'btn-danger'
                                            : 'btn-primary' ?>"
                                        onclick="toggleStatus(
                                            <?= (int) $u['user_id'] ?>,
                                            <?= $u['is_active']
                                                ? 'false'
                                                : 'true' ?>
                                        )"
                                    >
                                        <?= $u['is_active']
                                            ? 'Deactivate'
                                            : 'Activate' ?>
                                    </button>

                                <?php else: ?>

                                    <span class="cell-muted">
                                        (You)
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <p
            class="empty-state"
            id="noResultsState"
            style="display:none;"
        >
            No users match your search or filter.
        </p>

    <?php endif; ?>

</div>

<div
    class="modal-overlay"
    id="resetModal"
>

    <div
        class="glass-card modal-box"
        style="max-width:400px;"
    >

        <div class="modal-header">

            <div>

                <h3 class="modal-title">
                    Reset Password
                </h3>

                <p class="modal-subtitle">
                    Set a new password for this account.
                </p>

            </div>

            <button
                type="button"
                class="modal-close"
                onclick="closeModal('resetModal')"
            >
                &times;
            </button>

        </div>

        <div
            id="resetModalAlert"
            class="alert alert-error"
        ></div>

        <form id="resetForm">

            <input
                type="hidden"
                id="reset_user_id"
                value=""
            >

            <div class="form-group">

                <label for="reset_login_id">
                    Login ID
                </label>

                <input
                    type="text"
                    id="reset_login_id"
                    class="form-control"
                    disabled
                >

            </div>

            <div class="form-group">

                <label for="new_password">
                    New Password
                </label>

                <input
                    type="password"
                    id="new_password"
                    class="form-control"
                    placeholder="At least 6 characters"
                    minlength="6"
                    required
                >

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="closeModal('resetModal')"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                    id="resetSaveBtn"
                >
                    Reset Password
                </button>

            </div>

        </form>

    </div>

</div>

<?php

$extraScript = <<<'JS'
const searchInput = document.getElementById('searchInput');
const roleFilter = document.getElementById('roleFilter');
const rows = document.querySelectorAll('#usersTable tbody tr');
const noResultsState = document.getElementById('noResultsState');

function applyFilters() {
    const search = searchInput.value.trim().toLowerCase();
    const role = roleFilter.value;

    let visible = 0;

    rows.forEach((row) => {

        const matchesSearch =
            row.dataset.search.includes(search);

        const matchesRole =
            role === 'all' ||
            row.dataset.role === role;

        const show =
            matchesSearch &&
            matchesRole;

        row.style.display =
            show ? '' : 'none';

        if (show) {
            visible++;
        }

    });

    if (noResultsState) {
        noResultsState.style.display =
            visible === 0 ? 'block' : 'none';
    }
}

if (searchInput) {
    searchInput.addEventListener(
        'input',
        applyFilters
    );
}

if (roleFilter) {
    roleFilter.addEventListener(
        'change',
        applyFilters
    );
}

async function toggleStatus(userId, makeActive) {

    const ok = await confirmAction(
        makeActive
            ? 'Activate this account?'
            : 'Deactivate this account?',
        makeActive
            ? 'Activate'
            : 'Deactivate'
    );

    if (!ok) {
        return;
    }

    const result = await apiFetch(
        '/api/users.php',
        {
            method: 'POST',
            body: JSON.stringify({
                action: 'toggle_status',
                user_id: userId,
                is_active: makeActive
            })
        }
    );

    if (result.success) {

        showToast(
            result.message || 'Status updated.',
            'success'
        );

        setTimeout(() => {
            window.location.reload();
        }, 600);

    } else {

        showToast(
            result.message ||
            'Unable to update status.',
            'error'
        );

    }
}

function openResetModal(userId, loginId) {

    document
        .getElementById('resetForm')
        .reset();

    document
        .getElementById('reset_user_id')
        .value = userId;

    document
        .getElementById('reset_login_id')
        .value = loginId;

    document
        .getElementById('resetModalAlert')
        .classList.remove('show');

    openModal('resetModal');
}

document
    .getElementById('resetForm')
    .addEventListener('submit', async (e) => {

        e.preventDefault();

        const alertBox =
            document.getElementById(
                'resetModalAlert'
            );

        alertBox.classList.remove('show');

        const payload = {

            action: 'reset_password',

            user_id:
                document.getElementById(
                    'reset_user_id'
                ).value,

            password:
                document.getElementById(
                    'new_password'
                ).value

        };

        const saveBtn =
            document.getElementById(
                'resetSaveBtn'
            );

        saveBtn.disabled = true;

        saveBtn.innerHTML =
            '<span class="spinner"></span> Saving...';

        const result = await apiFetch(
            '/api/users.php',
            {
                method: 'POST',
                body: JSON.stringify(payload)
            }
        );

        saveBtn.disabled = false;

        saveBtn.textContent =
            'Reset Password';

        if (result.success) {

            showToast(
                result.message ||
                'Password reset successfully.',
                'success'
            );

            closeModal('resetModal');

        } else {

            alertBox.textContent =
                result.message ||
                'Unable to reset password.';

            alertBox.classList.add('show');

        }

    });
JS;

include __DIR__ . '/../includes/footer.php';
?>