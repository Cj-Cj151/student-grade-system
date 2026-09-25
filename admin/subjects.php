<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

$pdo = getDbConnection();

$subjects = $pdo->query('
    SELECT subject_id, subject_code, subject_name, units
    FROM subjects
    ORDER BY subject_code
')->fetchAll();

$pageTitle = 'Subjects';
$pageSubtitle = 'Manage the subject catalog';
$activeNav = 'subjects';

include __DIR__ . '/../includes/header.php';
?>

<div class="glass-card panel">

    <div class="panel-header">

        <div>
            <h2 class="panel-title">All Subjects</h2>
            <p class="panel-subtitle">
                View and manage registered subjects
            </p>
        </div>

        <div class="toolbar">

            <div class="search-input-wrap">
                <input
                    type="text"
                    id="searchInput"
                    class="form-control search-input"
                    placeholder="Search subjects..."
                >
            </div>

            <button
                type="button"
                class="btn btn-primary"
                onclick="openAddModal()"
            >
                Add Subject
            </button>

        </div>

    </div>

    <?php if (empty($subjects)): ?>

        <div class="empty-state">
            <p>No subjects yet.</p>
        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table class="data-table" id="subjectsTable">

                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Units</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($subjects as $s): ?>

                        <tr
                            data-search="<?= clean(strtolower(
                                $s['subject_code'] . ' ' .
                                $s['subject_name']
                            )) ?>"
                        >

                            <td>
                                <strong>
                                    <?= clean($s['subject_code']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= clean($s['subject_name']) ?>
                            </td>

                            <td>
                                <?= rtrim(
                                    rtrim(
                                        number_format((float) $s['units'], 1),
                                        '0'
                                    ),
                                    '.'
                                ) ?>
                            </td>

                            <td class="row-actions">

                                <button
                                    type="button"
                                    class="btn btn-secondary btn-sm"
                                    onclick='openEditModal(<?= json_encode($s) ?>)'
                                >
                                    Edit
                                </button>

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
            No subjects match your search.
        </p>

    <?php endif; ?>

</div>

<div class="modal-overlay" id="subjectModal">

    <div class="glass-card modal-box">

        <div class="modal-header">

            <h3 class="modal-title" id="subjectModalTitle">
                Add Subject
            </h3>

            <button
                type="button"
                class="modal-close"
                onclick="closeModal('subjectModal')"
            >
                ×
            </button>

        </div>

        <div
            id="subjectModalAlert"
            class="alert alert-error"
        ></div>

        <form id="subjectForm">

            <input
                type="hidden"
                id="subject_id"
                value=""
            >

            <div class="form-group">

                <label for="subject_code">
                    Subject Code
                </label>

                <input
                    type="text"
                    id="subject_code"
                    class="form-control"
                    placeholder="e.g. CS103"
                    required
                >

            </div>

            <div class="form-group">

                <label for="subject_name">
                    Subject Name
                </label>

                <input
                    type="text"
                    id="subject_name"
                    class="form-control"
                    placeholder="e.g. Database Systems"
                    required
                >

            </div>

            <div class="form-group">

                <label for="units">
                    Units
                </label>

                <input
                    type="number"
                    id="units"
                    class="form-control"
                    min="0.5"
                    max="10"
                    step="0.5"
                    value="3"
                    required
                >

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="closeModal('subjectModal')"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                    id="subjectSaveBtn"
                >
                    Save Subject
                </button>

            </div>

        </form>

    </div>

</div>

<?php

$extraScript = <<<'JS'
const searchInput = document.getElementById('searchInput');
const rows = document.querySelectorAll('#subjectsTable tbody tr');
const noResultsState = document.getElementById('noResultsState');

if (searchInput) {
    searchInput.addEventListener('input', () => {
        const search = searchInput.value.trim().toLowerCase();
        let visible = 0;

        rows.forEach((row) => {
            const show = row.dataset.search.includes(search);

            row.style.display = show ? '' : 'none';

            if (show) {
                visible++;
            }
        });

        if (noResultsState) {
            noResultsState.style.display =
                visible === 0 ? 'block' : 'none';
        }
    });
}

function openAddModal() {
    document.getElementById('subjectForm').reset();

    document.getElementById('subject_id').value = '';

    document.getElementById('subjectModalTitle').textContent =
        'Add Subject';

    document.getElementById('units').value = '3';

    hideSubjectAlert();

    openModal('subjectModal');
}

function openEditModal(subject) {
    document.getElementById('subjectForm').reset();

    document.getElementById('subject_id').value =
        subject.subject_id;

    document.getElementById('subject_code').value =
        subject.subject_code;

    document.getElementById('subject_name').value =
        subject.subject_name;

    document.getElementById('units').value =
        subject.units;

    document.getElementById('subjectModalTitle').textContent =
        'Edit Subject';

    hideSubjectAlert();

    openModal('subjectModal');
}

function showSubjectAlert(message) {
    const alertBox =
        document.getElementById('subjectModalAlert');

    alertBox.textContent = message;
    alertBox.classList.add('show');
}

function hideSubjectAlert() {
    document
        .getElementById('subjectModalAlert')
        .classList.remove('show');
}

document
    .getElementById('subjectForm')
    .addEventListener('submit', async (e) => {

        e.preventDefault();

        hideSubjectAlert();

        const payload = {
            subject_id:
                document.getElementById('subject_id').value || null,

            subject_code:
                document.getElementById('subject_code').value.trim(),

            subject_name:
                document.getElementById('subject_name').value.trim(),

            units:
                document.getElementById('units').value
        };

        const saveBtn =
            document.getElementById('subjectSaveBtn');

        saveBtn.disabled = true;
        saveBtn.innerHTML =
            '<span class="spinner"></span> Saving...';

        const result = await apiFetch('/api/subjects.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Subject';

        if (result.success) {

            showToast(
                result.message || 'Subject saved.',
                'success'
            );

            setTimeout(() => {
                window.location.reload();
            }, 700);

        } else {

            showSubjectAlert(
                result.message || 'Unable to save subject.'
            );
        }
    });
JS;

include __DIR__ . '/../includes/footer.php';
?>