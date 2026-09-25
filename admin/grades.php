<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

$pdo = getDbConnection();

$subjects = $pdo->query('
    SELECT subject_id, subject_code, subject_name
    FROM subjects
    ORDER BY subject_code
')->fetchAll();

$terms = $pdo->query('
    SELECT
        term_id,
        school_year,
        semester,
        grading_period
    FROM academic_terms
    ORDER BY
        school_year DESC,
        CASE semester
            WHEN \'1st Semester\' THEN 1
            WHEN \'2nd Semester\' THEN 2
            WHEN \'Summer\' THEN 3
            ELSE 4
        END,
        CASE grading_period
            WHEN \'Prelim\' THEN 1
            WHEN \'Midterm\' THEN 2
            WHEN \'Semi-Final\' THEN 3
            WHEN \'Final\' THEN 4
            ELSE 5
        END
')->fetchAll();

$grades = $pdo->query('
    SELECT
        g.grade_id,
        g.grade,
        g.remarks,
        g.updated_at,

        st.first_name AS student_first,
        st.last_name AS student_last,
        su.login_id AS student_login,

        tc.first_name AS teacher_first,
        tc.last_name AS teacher_last,

        sub.subject_id,
        sub.subject_code,
        sub.subject_name,

        t.term_id,
        t.school_year,
        t.semester,
        t.grading_period

    FROM grades g

    JOIN students st
        ON st.student_id = g.student_id

    JOIN users su
        ON su.user_id = st.user_id

    JOIN teachers tc
        ON tc.teacher_id = g.teacher_id

    JOIN subjects sub
        ON sub.subject_id = g.subject_id

    JOIN academic_terms t
        ON t.term_id = g.term_id

    ORDER BY g.updated_at DESC
')->fetchAll();

$pageTitle = 'Grade Records';
$pageSubtitle = 'View, search, and edit all grade records';
$activeNav = 'grades';

include __DIR__ . '/../includes/header.php';
?>

<div class="glass-card panel">

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                All Grade Records
            </h2>

            <p class="panel-subtitle">
                View and manage student grade records
            </p>

        </div>

        <div class="toolbar grade-records-toolbar">

            <div class="search-input-wrap">

                <input
                    type="text"
                    id="searchInput"
                    class="form-control search-input"
                    placeholder="Search student or subject..."
                    autocomplete="off"
                >

            </div>

            <select
                id="subjectFilter"
                class="form-control"
            >

                <option value="all">
                    All Subjects
                </option>

                <?php foreach ($subjects as $subject): ?>

                    <option
                        value="<?= (int) $subject['subject_id'] ?>"
                    >
                        <?= clean($subject['subject_code']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <select
                id="termFilter"
                class="form-control"
            >

                <option value="all">
                    All Academic Terms
                </option>

                <?php foreach ($terms as $term): ?>

                    <option
                        value="<?= (int) $term['term_id'] ?>"
                    >
                        <?= clean(
                            $term['school_year']
                            . ' - '
                            . $term['semester']
                            . ' - '
                            . $term['grading_period']
                        ) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <select
                id="gradingPeriodFilter"
                class="form-control"
            >

                <option value="all">
                    All Grading Periods
                </option>

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

    </div>

    <?php if (empty($grades)): ?>

        <div class="empty-state">

            <h3>
                No Grade Records
            </h3>

            <p>
                No grade records exist yet.
            </p>

        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table
                class="data-table"
                id="gradesTable"
            >

                <thead>

                    <tr>
                        <th>Student</th>
                        <th>Subject</th>
                        <th>Teacher</th>
                        <th>Academic Term</th>
                        <th>Grading Period</th>
                        <th>Grade</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($grades as $grade): ?>

                        <tr
                            data-subject="<?= (int) $grade['subject_id'] ?>"
                            data-term="<?= (int) $grade['term_id'] ?>"
                            data-grading-period="<?= clean($grade['grading_period']) ?>"
                            data-search="<?= clean(strtolower(
                                $grade['student_first'] . ' ' .
                                $grade['student_last'] . ' ' .
                                $grade['student_login'] . ' ' .
                                $grade['subject_code'] . ' ' .
                                $grade['subject_name'] . ' ' .
                                $grade['teacher_first'] . ' ' .
                                $grade['teacher_last']
                            )) ?>"
                        >

                            <td>

                                <?= clean(
                                    $grade['student_first']
                                    . ' '
                                    . $grade['student_last']
                                ) ?>

                                <br>

                                <span class="cell-muted">
                                    <?= clean($grade['student_login']) ?>
                                </span>

                            </td>

                            <td>

                                <strong>
                                    <?= clean($grade['subject_code']) ?>
                                </strong>

                                <br>

                                <span class="cell-muted">
                                    <?= clean($grade['subject_name']) ?>
                                </span>

                            </td>

                            <td>

                                <?= clean(
                                    $grade['teacher_first']
                                    . ' '
                                    . $grade['teacher_last']
                                ) ?>

                            </td>

                            <td class="cell-muted">

                                <?= clean(
                                    $grade['school_year']
                                    . ' - '
                                    . $grade['semester']
                                ) ?>

                            </td>

                            <td>

                                <span class="badge badge-info">
                                    <?= clean($grade['grading_period']) ?>
                                </span>

                            </td>

                            <td>

                                <?php if ($grade['grade'] !== null): ?>

                                    <?= number_format(
                                        (float) $grade['grade'],
                                        2
                                    ) ?>

                                <?php else: ?>

                                    &mdash;

                                <?php endif; ?>

                            </td>

                            <td>

                                <span
                                    class="badge <?= gradeStatusClass(
                                        $grade['grade']
                                    ) ?>"
                                >
                                    <?= gradeStatus($grade['grade']) ?>
                                </span>

                            </td>

                            <td class="row-actions">

                                <button
                                    type="button"
                                    class="btn btn-secondary btn-sm edit-grade-btn"
                                    data-grade-id="<?= (int) $grade['grade_id'] ?>"
                                    data-grade="<?= $grade['grade'] !== null
                                        ? htmlspecialchars(
                                            (string) $grade['grade'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                        : ''
                                    ?>"
                                    data-remarks="<?= htmlspecialchars(
                                        $grade['remarks'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    data-label="<?= htmlspecialchars(
                                        $grade['student_first']
                                        . ' '
                                        . $grade['student_last']
                                        . ' - '
                                        . $grade['subject_code']
                                        . ' - '
                                        . $grade['grading_period'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
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
            No grade records match your search or filters.
        </p>

    <?php endif; ?>

</div>

<div
    class="modal-overlay"
    id="gradeModal"
    aria-hidden="true"
>

    <div class="glass-card modal-box">

        <div class="modal-header">

            <div>

                <h3 class="modal-title">
                    Edit Grade
                </h3>

                <p class="modal-subtitle">
                    Update the selected grade record.
                </p>

            </div>

            <button
                type="button"
                class="modal-close"
                id="closeGradeModal"
                aria-label="Close"
            >
                &times;
            </button>

        </div>

        <div
            id="gradeModalAlert"
            class="alert alert-error"
        ></div>

        <form id="gradeForm">

            <input
                type="hidden"
                id="grade_id"
                value=""
            >

            <div class="form-group">

                <label for="gradeModalLabel">
                    Record
                </label>

                <input
                    type="text"
                    id="gradeModalLabel"
                    class="form-control"
                    disabled
                >

            </div>

            <div class="form-group">

                <label for="grade_value">
                    Grade
                </label>

                <input
                    type="number"
                    id="grade_value"
                    class="form-control"
                    min="0"
                    max="100"
                    step="0.01"
                    placeholder="0-100, leave blank for pending"
                >

                <div class="form-hint">
                    Enter a grade from 0 to 100. Leave blank if pending.
                </div>

            </div>

            <div class="form-group">

                <label for="remarks_value">
                    Remarks
                </label>

                <input
                    type="text"
                    id="remarks_value"
                    class="form-control"
                    maxlength="255"
                    placeholder="Optional remarks"
                >

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    id="cancelGradeBtn"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                    id="gradeSaveBtn"
                >
                    Save Grade
                </button>

            </div>

        </form>

    </div>

</div>

<?php

$extraScript = <<<'JS'

const searchInput =
    document.getElementById('searchInput');

const subjectFilter =
    document.getElementById('subjectFilter');

const termFilter =
    document.getElementById('termFilter');

const gradingPeriodFilter =
    document.getElementById('gradingPeriodFilter');

const rows =
    document.querySelectorAll(
        '#gradesTable tbody tr'
    );

const noResultsState =
    document.getElementById('noResultsState');

const gradeModal =
    document.getElementById('gradeModal');

const gradeForm =
    document.getElementById('gradeForm');

const closeGradeModal =
    document.getElementById('closeGradeModal');

const cancelGradeBtn =
    document.getElementById('cancelGradeBtn');

function applyFilters() {

    const search =
        searchInput.value
            .trim()
            .toLowerCase();

    const subject =
        subjectFilter.value;

    const term =
        termFilter.value;

    const gradingPeriod =
        gradingPeriodFilter.value;

    let visible = 0;

    rows.forEach((row) => {

        const matchesSearch =
            row.dataset.search.includes(search);

        const matchesSubject =
            subject === 'all' ||
            row.dataset.subject === subject;

        const matchesTerm =
            term === 'all' ||
            row.dataset.term === term;

        const matchesGradingPeriod =
            gradingPeriod === 'all' ||
            row.dataset.gradingPeriod === gradingPeriod;

        const show =
            matchesSearch &&
            matchesSubject &&
            matchesTerm &&
            matchesGradingPeriod;

        row.style.display =
            show ? '' : 'none';

        if (show) {
            visible++;
        }

    });

    if (noResultsState) {

        noResultsState.style.display =
            visible === 0
                ? 'block'
                : 'none';

    }

}

searchInput?.addEventListener(
    'input',
    applyFilters
);

subjectFilter?.addEventListener(
    'change',
    applyFilters
);

termFilter?.addEventListener(
    'change',
    applyFilters
);

gradingPeriodFilter?.addEventListener(
    'change',
    applyFilters
);

function openEditModal(record) {

    gradeForm.reset();

    document.getElementById('grade_id').value =
        record.gradeId;

    document.getElementById('gradeModalLabel').value =
        record.label;

    document.getElementById('grade_value').value =
        record.grade !== ''
            ? record.grade
            : '';

    document.getElementById('remarks_value').value =
        record.remarks || '';

    document
        .getElementById('gradeModalAlert')
        .classList.remove('show');

    gradeModal.classList.add('show');

    gradeModal.setAttribute(
        'aria-hidden',
        'false'
    );

}

function closeGradeModalWindow() {

    gradeModal.classList.remove('show');

    gradeModal.setAttribute(
        'aria-hidden',
        'true'
    );

    gradeForm.reset();

    document
        .getElementById('gradeModalAlert')
        .classList.remove('show');

}

document
    .querySelectorAll('.edit-grade-btn')
    .forEach((button) => {

        button.addEventListener(
            'click',
            () => {

                openEditModal({
                    gradeId:
                        button.dataset.gradeId,

                    grade:
                        button.dataset.grade,

                    remarks:
                        button.dataset.remarks,

                    label:
                        button.dataset.label
                });

            }
        );

    });

closeGradeModal?.addEventListener(
    'click',
    closeGradeModalWindow
);

cancelGradeBtn?.addEventListener(
    'click',
    closeGradeModalWindow
);

gradeModal?.addEventListener(
    'click',
    (event) => {

        if (event.target === gradeModal) {
            closeGradeModalWindow();
        }

    }
);

document.addEventListener(
    'keydown',
    (event) => {

        if (event.key === 'Escape') {
            closeGradeModalWindow();
        }

    }
);

gradeForm?.addEventListener(
    'submit',
    async (event) => {

        event.preventDefault();

        const alertBox =
            document.getElementById(
                'gradeModalAlert'
            );

        alertBox.classList.remove('show');

        const gradeInput =
            document.getElementById(
                'grade_value'
            );

        const remarksInput =
            document.getElementById(
                'remarks_value'
            );

        const gradeValue =
            gradeInput.value.trim();

        const remarks =
            remarksInput.value.trim();

        if (gradeValue !== '') {

            const numericGrade =
                Number(gradeValue);

            if (
                Number.isNaN(numericGrade) ||
                numericGrade < 0 ||
                numericGrade > 100
            ) {

                alertBox.textContent =
                    'Grade must be a number between 0 and 100.';

                alertBox.classList.add('show');

                gradeInput.focus();

                return;

            }

        }

        const saveBtn =
            document.getElementById(
                'gradeSaveBtn'
            );

        saveBtn.disabled = true;

        saveBtn.innerHTML =
            '<span class="spinner"></span> Saving...';

        const result =
            await apiFetch(
                '/api/admin_grades.php',
                {
                    method: 'POST',
                    body: JSON.stringify({
                        grade_id:
                            Number(
                                document.getElementById(
                                    'grade_id'
                                ).value
                            ),

                        grade:
                            gradeValue === ''
                                ? null
                                : Number(gradeValue),

                        remarks:
                            remarks
                    })
                }
            );

        saveBtn.disabled = false;

        saveBtn.textContent =
            'Save Grade';

        if (!result.success) {

            alertBox.textContent =
                result.message ||
                'Unable to save grade.';

            alertBox.classList.add('show');

            return;

        }

        showToast(
            result.message ||
            'Grade updated successfully.',
            'success'
        );

        setTimeout(
            () => {
                window.location.reload();
            },
            700
        );

    }
);

JS;

include __DIR__ . '/../includes/footer.php';

?>