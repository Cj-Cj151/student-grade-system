<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

$pdo = getDbConnection();

$terms = $pdo->query('
    SELECT term_id, school_year, semester, grading_period, status
    FROM academic_terms
    ORDER BY school_year DESC, semester DESC,
             CASE grading_period
                 WHEN \'Prelim\' THEN 1
                 WHEN \'Midterm\' THEN 2
                 WHEN \'Semi-Final\' THEN 3
                 WHEN \'Final\' THEN 4
                 ELSE 5
             END
')->fetchAll();

$pageTitle = 'Academic Terms';
$pageSubtitle = 'Manage school years, semesters, and grading periods';
$activeNav = 'terms';

include __DIR__ . '/../includes/header.php';
?>

<div class="glass-card panel">

    <div class="panel-header">

        <div>
            <h2 class="panel-title">Academic Terms</h2>

            <p class="panel-subtitle">
                Manage school years, semesters, and grading periods
            </p>
        </div>

        <button
            type="button"
            class="btn btn-primary"
            onclick="openAddModal()"
        >
            Add Term
        </button>

    </div>

    <?php if (empty($terms)): ?>

        <div class="empty-state">
            <h3>No Academic Terms</h3>

            <p>
                Click "Add Term" to create an academic term.
            </p>
        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table class="data-table" id="termsTable">

                <thead>

                    <tr>
                        <th>School Year</th>
                        <th>Semester</th>
                        <th>Grading Period</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($terms as $t): ?>

                        <tr>

                            <td>
                                <strong>
                                    <?= clean($t['school_year']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= clean($t['semester']) ?>
                            </td>

                            <td>
                                <span class="badge badge-info">
                                    <?= clean($t['grading_period']) ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge <?= $t['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $t['status'] === 'active' ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>

                            <td class="row-actions">

                                <button
                                    type="button"
                                    class="btn btn-secondary btn-sm"
                                    onclick='openEditModal(<?= json_encode($t) ?>)'
                                >
                                    Edit
                                </button>

                                <?php if ($t['status'] !== 'active'): ?>

                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm"
                                        onclick="setActive(<?= (int) $t['term_id'] ?>)"
                                    >
                                        Set Active
                                    </button>

                                <?php endif; ?>

                                <button
                                    type="button"
                                    class="btn btn-danger btn-sm"
                                    onclick="deleteTerm(<?= (int) $t['term_id'] ?>)"
                                >
                                    Delete
                                </button>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<div class="modal-overlay" id="termModal">

    <div class="glass-card modal-box">

        <div class="modal-header">

            <div>

                <h3 class="modal-title" id="termModalTitle">
                    Add Academic Term
                </h3>

                <p class="modal-subtitle">
                    Enter the academic period information.
                </p>

            </div>

            <button
                type="button"
                class="modal-close"
                onclick="closeModal('termModal')"
            >
                &times;
            </button>

        </div>

        <div
            id="termModalAlert"
            class="alert alert-error"
        ></div>

        <form id="termForm">

            <input
                type="hidden"
                id="term_id"
                value=""
            >

            <div class="form-group">

                <label for="school_year">
                    School Year
                </label>

                <input
                    type="text"
                    id="school_year"
                    class="form-control"
                    placeholder="e.g. 2026-2027"
                    required
                >

            </div>

            <div class="form-group">

                <label for="semester">
                    Semester
                </label>

                <select
                    id="semester"
                    class="form-control"
                    required
                >

                    <option value="1st Semester">
                        1st Semester
                    </option>

                    <option value="2nd Semester">
                        2nd Semester
                    </option>

                    <option value="Summer">
                        Summer
                    </option>

                </select>

            </div>

            <div class="form-group">

                <label for="grading_period">
                    Grading Period
                </label>

                <select
                    id="grading_period"
                    class="form-control"
                    required
                >

                    <option value="Prelim">
                        Prelim
                    </option>

                    <option value="Midterm">
                        Midterm
                    </option>

                    <option value="Semi-Final">
                        Semi-Final
                    </option>

                    <option value="Final">
                        Final
                    </option>

                </select>

            </div>

            <div class="form-group">

                <label class="checkbox-label">

                    <input
                        type="checkbox"
                        id="make_active"
                    >

                    <span>
                        Set as the active term
                    </span>

                </label>

                <div class="form-hint">
                    Only one academic term can be active at a time.
                    Setting this active will deactivate the current active term.
                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="closeModal('termModal')"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                    id="termSaveBtn"
                >
                    Save Term
                </button>

            </div>

        </form>

    </div>

</div>

<?php

$extraScript = <<<'JS'
function openAddModal() {
    document.getElementById('termForm').reset();

    document.getElementById('term_id').value = '';
    document.getElementById('grading_period').value = 'Prelim';
    document.getElementById('make_active').checked = false;

    document.getElementById('termModalTitle').textContent =
        'Add Academic Term';

    hideTermAlert();

    openModal('termModal');
}

function openEditModal(term) {
    document.getElementById('termForm').reset();

    document.getElementById('term_id').value =
        term.term_id;

    document.getElementById('school_year').value =
        term.school_year;

    document.getElementById('semester').value =
        term.semester;

    document.getElementById('grading_period').value =
        term.grading_period;

    document.getElementById('make_active').checked =
        term.status === 'active';

    document.getElementById('termModalTitle').textContent =
        'Edit Academic Term';

    hideTermAlert();

    openModal('termModal');
}

function showTermAlert(message) {
    const alertBox =
        document.getElementById('termModalAlert');

    alertBox.textContent = message;
    alertBox.classList.add('show');
}

function hideTermAlert() {
    document
        .getElementById('termModalAlert')
        .classList.remove('show');
}

document
    .getElementById('termForm')
    .addEventListener('submit', async (e) => {

        e.preventDefault();

        hideTermAlert();

        const payload = {
            term_id:
                document.getElementById('term_id').value || null,

            school_year:
                document.getElementById('school_year').value.trim(),

            semester:
                document.getElementById('semester').value,

            grading_period:
                document.getElementById('grading_period').value,

            make_active:
                document.getElementById('make_active').checked
        };

        const saveBtn =
            document.getElementById('termSaveBtn');

        saveBtn.disabled = true;

        saveBtn.innerHTML =
            '<span class="spinner"></span> Saving...';

        const result = await apiFetch('/api/terms.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Term';

        if (result.success) {

            showToast(
                result.message || 'Term saved.',
                'success'
            );

            setTimeout(() => {
                window.location.reload();
            }, 700);

        } else {

            showTermAlert(
                result.message || 'Unable to save term.'
            );

        }
    });

async function setActive(termId) {

    const ok = await confirmAction(
        'Set this term as the active academic term? Any other active term will be deactivated.',
        'Set Active'
    );

    if (!ok) {
        return;
    }

    const result = await apiFetch('/api/terms.php', {
        method: 'POST',
        body: JSON.stringify({
            term_id: termId,
            action: 'set_active'
        })
    });

    if (result.success) {

        showToast(
            result.message || 'Active term updated.',
            'success'
        );

        setTimeout(() => {
            window.location.reload();
        }, 600);

    } else {

        showToast(
            result.message || 'Unable to update active term.',
            'error'
        );

    }
}

async function deleteTerm(termId) {

    const ok = await confirmAction(
        'Delete this academic term? This action cannot be undone.',
        'Delete Term'
    );

    if (!ok) {
        return;
    }

    const result = await apiFetch('/api/terms.php', {
        method: 'POST',
        body: JSON.stringify({
            term_id: termId,
            action: 'delete'
        })
    });

    if (result.success) {

        showToast(
            result.message || 'Academic term deleted.',
            'success'
        );

        setTimeout(() => {
            window.location.reload();
        }, 600);

    } else {

        showToast(
            result.message || 'Unable to delete academic term.',
            'error'
        );

    }
}
JS;

include __DIR__ . '/../includes/footer.php';
?>