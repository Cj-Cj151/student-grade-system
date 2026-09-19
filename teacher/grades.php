<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('teacher');

$pdo = getDbConnection();
$teacherId = $_SESSION['teacher_id'];

// Subjects handled by this teacher.
$stmt = $pdo->prepare('
    SELECT DISTINCT s.subject_id, s.subject_code, s.subject_name
    FROM grades g
    JOIN subjects s ON s.subject_id = g.subject_id
    WHERE g.teacher_id = :tid
    ORDER BY s.subject_code
');
$stmt->execute(['tid' => $teacherId]);
$subjects = $stmt->fetchAll();

// Academic terms used by this teacher.
$stmt = $pdo->prepare('
    SELECT DISTINCT t.term_id, t.school_year, t.semester
    FROM grades g
    JOIN academic_terms t ON t.term_id = g.term_id
    WHERE g.teacher_id = :tid
    ORDER BY t.school_year DESC, t.term_id DESC
');
$stmt->execute(['tid' => $teacherId]);
$terms = $stmt->fetchAll();

$selectedSubject = isset($_GET['subject_id'])
    ? (int) $_GET['subject_id']
    : ($subjects[0]['subject_id'] ?? 0);

$selectedTerm = isset($_GET['term_id'])
    ? (int) $_GET['term_id']
    : ($terms[0]['term_id'] ?? 0);

$students = [];

if ($selectedSubject && $selectedTerm) {
    $stmt = $pdo->prepare('
        SELECT
            g.grade_id,
            g.grade,
            g.remarks,
            st.student_id,
            st.first_name,
            st.last_name,
            u.login_id
        FROM grades g
        JOIN students st ON st.student_id = g.student_id
        JOIN users u ON u.user_id = st.user_id
        WHERE g.teacher_id = :tid
          AND g.subject_id = :subid
          AND g.term_id = :termid
        ORDER BY st.last_name, st.first_name
    ');

    $stmt->execute([
        'tid' => $teacherId,
        'subid' => $selectedSubject,
        'termid' => $selectedTerm
    ]);

    $students = $stmt->fetchAll();
}

// Students who do not yet have a grade record for the selected
// subject and term.
$availableStudents = [];

if ($selectedSubject && $selectedTerm) {
    $stmt = $pdo->prepare('
        SELECT
            st.student_id,
            st.first_name,
            st.last_name,
            u.login_id
        FROM students st
        JOIN users u ON u.user_id = st.user_id
        WHERE u.is_active = TRUE
          AND NOT EXISTS (
              SELECT 1
              FROM grades g
              WHERE g.student_id = st.student_id
                AND g.subject_id = :subid
                AND g.term_id = :termid
          )
        ORDER BY st.last_name, st.first_name
    ');

    $stmt->execute([
        'subid' => $selectedSubject,
        'termid' => $selectedTerm
    ]);

    $availableStudents = $stmt->fetchAll();
}

$pageTitle = 'Grade Management';
$pageSubtitle = 'Encode and update grades for your students';
$activeNav = 'grades';

include __DIR__ . '/../includes/header.php';
?>

<!-- ================================================================
     SUBJECT + TERM FILTER
     ================================================================ -->
<div class="glass-card panel grade-filter-card">

    <div class="grade-page-heading">
        <div>
            <div class="grade-heading-icon">&#128221;</div>

            <div>
                <h2 class="grade-main-title">Grade Management</h2>
                <p class="grade-main-subtitle">
                    Encode and update grades for your students
                </p>
            </div>
        </div>

        <div class="grade-motto">
            Better Grades<br>
            <span>Brighter Future</span>
        </div>
    </div>

    <form method="GET" id="filterForm" class="grade-filter-grid">

        <div class="grade-filter-field">
            <label for="subjectSelect">
                &#128218; Subject
            </label>

            <select
                name="subject_id"
                id="subjectSelect"
                class="form-control"
                onchange="document.getElementById('filterForm').submit()"
            >
                <option value="">-- Select Subject --</option>

                <?php foreach ($subjects as $subject): ?>
                    <option
                        value="<?= (int) $subject['subject_id'] ?>"
                        <?= $selectedSubject == $subject['subject_id'] ? 'selected' : '' ?>
                    >
                        <?= clean($subject['subject_code'] . ' - ' . $subject['subject_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grade-filter-field">
            <label for="termSelect">
                &#128197; Academic Term
            </label>

            <select
                name="term_id"
                id="termSelect"
                class="form-control"
                onchange="document.getElementById('filterForm').submit()"
            >
                <option value="">-- Select Term --</option>

                <?php foreach ($terms as $term): ?>
                    <option
                        value="<?= (int) $term['term_id'] ?>"
                        <?= $selectedTerm == $term['term_id'] ? 'selected' : '' ?>
                    >
                        <?= clean($term['school_year'] . ' - ' . $term['semester']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

    </form>
</div>


<!-- ================================================================
     STUDENT GRADES
     ================================================================ -->
<div class="glass-card panel">

    <div class="grade-table-header">

        <div>
            <h2 class="panel-title">
                &#128101; Student Grades
            </h2>

            <p class="grade-table-subtitle">
                Manage and encode grades for the selected subject and term.
            </p>
        </div>

        <div class="grade-table-actions">

            <div class="search-input-wrap">
                <span class="search-icon">&#128269;</span>

                <input
                    type="text"
                    id="searchInput"
                    class="form-control search-input"
                    placeholder="Search student name or ID..."
                >
            </div>

            <?php if ($selectedSubject && $selectedTerm): ?>
                <button
                    type="button"
                    class="btn btn-primary"
                    id="openAddGradeBtn"
                >
                    + Add Grade
                </button>
            <?php endif; ?>

        </div>
    </div>


    <?php if (empty($subjects) || empty($terms)): ?>

        <div class="empty-state">
            <div class="empty-icon">&#128218;</div>

            <p>
                You have no subjects or academic terms available yet.
            </p>
        </div>


    <?php elseif (empty($students)): ?>

        <div class="empty-state" id="emptyStudentsState">

            <div class="empty-icon">&#128100;</div>

            <p>
                No grade records found for this subject and term.
            </p>

            <p class="grade-empty-hint">
                Click <strong>+ Add Grade</strong> to encode a grade.
            </p>

        </div>


    <?php else: ?>

        <div class="table-wrap">

            <table class="data-table grade-data-table" id="studentsTable">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Grade</th>
                        <th>Remarks</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($students as $index => $student): ?>

                        <tr
                            data-name="<?= clean(strtolower(
                                $student['first_name'] . ' ' .
                                $student['last_name'] . ' ' .
                                $student['login_id']
                            )) ?>"
                            data-grade-id="<?= (int) $student['grade_id'] ?>"
                        >

                            <td>
                                <span class="row-number">
                                    <?= $index + 1 ?>
                                </span>
                            </td>

                            <td>
                                <strong>
                                    <?= clean($student['login_id']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= clean(
                                    $student['first_name'] . ' ' .
                                    $student['last_name']
                                ) ?>
                            </td>

                            <td>
                                <span class="grade-display">
                                    <?= $student['grade'] !== null
                                        ? number_format((float) $student['grade'], 2)
                                        : '—'
                                    ?>
                                </span>
                            </td>

                            <td>
                                <span class="remarks-display">
                                    <?= $student['remarks']
                                        ? clean($student['remarks'])
                                        : '—'
                                    ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge status-badge <?= gradeStatusClass($student['grade']) ?>">
                                    <?= gradeStatus($student['grade']) ?>
                                </span>
                            </td>

                            <td>
                                <div class="row-actions">

                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm edit-grade-btn"
                                        data-grade-id="<?= (int) $student['grade_id'] ?>"
                                        data-student-id="<?= (int) $student['student_id'] ?>"
                                        data-student-name="<?= clean(
                                            $student['first_name'] . ' ' .
                                            $student['last_name']
                                        ) ?>"
                                        data-grade="<?= $student['grade'] !== null
                                            ? htmlspecialchars((string) $student['grade'])
                                            : ''
                                        ?>"
                                        data-remarks="<?= clean($student['remarks'] ?? '') ?>"
                                    >
                                        &#9998; Edit
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-danger btn-sm delete-grade-btn"
                                        data-grade-id="<?= (int) $student['grade_id'] ?>"
                                        data-student-name="<?= clean(
                                            $student['first_name'] . ' ' .
                                            $student['last_name']
                                        ) ?>"
                                    >
                                        &#128465; Delete
                                    </button>

                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <p class="empty-state grade-no-results" id="noResultsState">
            <span class="empty-icon">&#128269;</span>
            <br>
            No students match your search.
        </p>

    <?php endif; ?>

</div>


<!-- ================================================================
     ADD / EDIT GRADE MODAL
     ================================================================ -->
<div
    class="modal-overlay"
    id="gradeModal"
    aria-hidden="true"
>

       <div class="glass-card modal-box grade-modal-box">

        <div class="modal-header">

            <div>
                <h3 class="modal-title" id="gradeModalTitle">
                    Add Student Grade
                </h3>

                <p class="grade-modal-subtitle">
                    Enter the student's grade and remarks.
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


        <form id="gradeForm">

            <input type="hidden" id="gradeId">

            <!-- STUDENT SEARCH -->
            <div class="form-group student-search-group">

                <label for="studentSearch">
                    Student
                </label>

                <input
                    type="text"
                    id="studentSearch"
                    class="form-control"
                    placeholder="Search student name or ID..."
                    autocomplete="off"
                    required
                >

                <!-- Actual student ID sent to the API -->
                <input
                    type="hidden"
                    id="studentId"
                    value=""
                >

                <!-- Search results -->
                <div
                    id="studentSuggestions"
                    class="student-suggestions"
                ></div>

                <div class="form-hint" id="studentEditHint">
                    Type a student name or ID, then select a student.
                </div>

            </div>


            <!-- GRADE -->
            <div class="form-group">

                <label for="gradeValue">
                    Grade
                </label>

                <input
                    type="number"
                    id="gradeValue"
                    class="form-control"
                    min="0"
                    max="100"
                    step="0.01"
                    placeholder="Enter grade (e.g. 90.00)"
                    required
                >

                <div class="form-hint">
                    Enter a grade from 0 to 100.
                </div>

            </div>


            <!-- REMARKS -->
            <div class="form-group">

                <label for="remarksValue">
                    Remarks
                </label>

                <input
                    type="text"
                    id="remarksValue"
                    class="form-control"
                    maxlength="255"
                    placeholder="Enter remarks (e.g. Satisfactory)"
                >

            </div>


            <!-- BUTTONS -->
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
                    id="saveGradeBtn"
                >
                    <span id="saveGradeText">
                        Save Grade
                    </span>
                </button>

            </div>

        </form>

    </div>

</div>

<?php

$availableStudentsJson = json_encode(
    $availableStudents,
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
);

$extraScript = <<<'JS'

const selectedSubjectId = Number(
    document.getElementById('subjectSelect')?.value || 0
);

const selectedTermId = Number(
    document.getElementById('termSelect')?.value || 0
);

const gradeModal = document.getElementById('gradeModal');
const gradeForm = document.getElementById('gradeForm');
const gradeIdInput = document.getElementById('gradeId');
const studentSearch = document.getElementById('studentSearch');
const studentIdInput = document.getElementById('studentId');
const studentSuggestions = document.getElementById('studentSuggestions');
const gradeValueInput = document.getElementById('gradeValue');
const remarksValueInput = document.getElementById('remarksValue');

const gradeModalTitle = document.getElementById('gradeModalTitle');
const saveGradeText = document.getElementById('saveGradeText');
const saveGradeBtn = document.getElementById('saveGradeBtn');

const openAddGradeBtn = document.getElementById('openAddGradeBtn');
const closeGradeModal = document.getElementById('closeGradeModal');
const cancelGradeBtn = document.getElementById('cancelGradeBtn');


// ================================================================
// SEARCH
// ================================================================

const searchInput = document.getElementById('searchInput');
const rows = document.querySelectorAll('#studentsTable tbody tr');
const noResultsState = document.getElementById('noResultsState');

if (searchInput) {

    searchInput.addEventListener('input', () => {

        const search = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        rows.forEach((row) => {

            const show = row.dataset.name.includes(search);

            row.style.display = show ? '' : 'none';

            if (show) {
                visibleCount++;
            }

        });

        if (noResultsState) {
            noResultsState.style.display =
                visibleCount === 0 ? 'block' : 'none';
        }

    });

}

// ================================================================
// STUDENT SEARCH
// ================================================================

const availableStudents = __AVAILABLE_STUDENTS__;

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function selectStudent(student) {

    studentSearch.value =
        `${student.login_id} - ${student.first_name} ${student.last_name}`;

    studentIdInput.value = student.student_id;

    studentSuggestions.classList.remove('show');
}

function clearStudentSelection() {

    studentSearch.value = '';

    studentIdInput.value = '';

    studentSuggestions.classList.remove('show');
}

function showStudentSuggestions() {

    const search = studentSearch.value
        .trim()
        .toLowerCase();

    const matches = availableStudents
        .filter(student => {

            const studentId =
                String(student.login_id || '').toLowerCase();

            const firstName =
                String(student.first_name || '').toLowerCase();

            const lastName =
                String(student.last_name || '').toLowerCase();

            const fullName =
                `${firstName} ${lastName}`;

            return (
                studentId.includes(search) ||
                firstName.includes(search) ||
                lastName.includes(search) ||
                fullName.includes(search)
            );

        })
        .slice(0, 8);

    studentSuggestions.innerHTML = '';

    if (matches.length === 0) {

        studentSuggestions.innerHTML = `
            <div class="student-suggestion-empty">
                No students found.
            </div>
        `;

        studentSuggestions.classList.add('show');

        return;
    }

    matches.forEach(student => {

        const item = document.createElement('button');

        item.type = 'button';

        item.className =
            'student-suggestion-item';

        item.innerHTML = `
            <strong>
                ${escapeHtml(student.login_id)}
            </strong>

            <span>
                ${escapeHtml(
                    `${student.first_name} ${student.last_name}`
                )}
            </span>
        `;

        item.addEventListener(
            'click',
            () => selectStudent(student)
        );

        studentSuggestions.appendChild(item);

    });

    studentSuggestions.classList.add('show');
}


studentSearch.addEventListener(
    'input',
    function () {

        studentIdInput.value = '';

        showStudentSuggestions();

    }
);


studentSearch.addEventListener(
    'focus',
    function () {

        showStudentSuggestions();

    }
);


document.addEventListener(
    'click',
    function (event) {

        if (
            !studentSearch.contains(event.target) &&
            !studentSuggestions.contains(event.target)
        ) {

            studentSuggestions.classList.remove('show');

        }

    }
);


// ================================================================
// OPEN MODAL
// ================================================================

function openGradeModal(mode, data = {}) {

    if (!gradeModal) {
        return;
    }

    gradeModal.classList.add('show');
    gradeModal.setAttribute('aria-hidden', 'false');

    if (mode === 'add') {

        gradeModalTitle.textContent = ' Add Student Grade';
        saveGradeText.textContent = 'Save Grade';

       gradeIdInput.value = '';
clearStudentSelection();
gradeValueInput.value = '';
remarksValueInput.value = '';

studentSearch.disabled = false;

    } else {

       gradeIdInput.value = data.gradeId || '';

const editStudent = availableStudents.find(
    student => String(student.student_id) === String(data.studentId)
);

if (editStudent) {
    studentSearch.value =
        `${editStudent.login_id} - ${editStudent.first_name} ${editStudent.last_name}`;
} else {
    studentSearch.value = data.studentName || '';
}

studentIdInput.value = data.studentId || '';

gradeValueInput.value = data.grade || '';
remarksValueInput.value = data.remarks || '';

studentSearch.disabled = true;

    }

    setTimeout(() => {

    if (mode === 'add') {

        studentSearch.focus();
        showStudentSuggestions();

    } else {

        gradeValueInput.focus();

    }

}, 100);

}


// ================================================================
// CLOSE MODAL
// ================================================================

function closeModal() {

    if (!gradeModal) {
        return;
    }

    gradeModal.classList.remove('show');
    gradeModal.setAttribute('aria-hidden', 'true');

    if (gradeForm) {
        gradeForm.reset();
    }

    gradeIdInput.value = '';
    studentSelect.disabled = false;

}


openAddGradeBtn?.addEventListener('click', () => {

    const availableOptionCount = availableStudents.length;

    if (availableOptionCount === 0) {

        showToast(
            'All available students already have a grade for this subject and term.',
            'info'
        );

        return;
    }

    openGradeModal('add');

});


closeGradeModal?.addEventListener('click', closeModal);
cancelGradeBtn?.addEventListener('click', closeModal);


gradeModal?.addEventListener('click', (event) => {

    if (event.target === gradeModal) {
        closeModal();
    }

});


document.addEventListener('keydown', (event) => {

    if (event.key === 'Escape') {
        closeModal();
    }

});


// ================================================================
// EDIT
// ================================================================

document.querySelectorAll('.edit-grade-btn').forEach((button) => {

    button.addEventListener('click', () => {

        openGradeModal('edit', {
            gradeId: button.dataset.gradeId,
            studentId: button.dataset.studentId,
            grade: button.dataset.grade,
            remarks: button.dataset.remarks
        });

    });

});


// ================================================================
// ADD / UPDATE SUBMIT
// ================================================================

gradeForm?.addEventListener('submit', async (event) => {

    event.preventDefault();

   const gradeId = gradeIdInput.value.trim();
const studentId = studentIdInput.value.trim();
const grade = gradeValueInput.value.trim();
const remarks = remarksValueInput.value.trim();

if (!gradeId && !studentId) {

    showToast('Please select a student.', 'error');
    studentSearch.focus();

    return;
}

    if (grade === '') {

        showToast('Please enter a grade.', 'error');
        gradeValueInput.focus();

        return;
    }

    const numericGrade = Number(grade);

    if (
        Number.isNaN(numericGrade) ||
        numericGrade < 0 ||
        numericGrade > 100
    ) {

        showToast(
            'Grade must be a number between 0 and 100.',
            'error'
        );

        gradeValueInput.focus();

        return;
    }


    saveGradeBtn.disabled = true;
    saveGradeText.innerHTML = '<span class="spinner"></span> Saving...';


    const payload = {
        action: gradeId ? 'update' : 'add',
        grade_id: gradeId ? Number(gradeId) : null,
        student_id: studentId ? Number(studentId) : null,
        subject_id: selectedSubjectId,
        term_id: selectedTermId,
        grade: numericGrade,
        remarks: remarks
    };


    const result = await apiFetch('/api/grades.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });


    saveGradeBtn.disabled = false;

    saveGradeText.textContent =
        gradeId ? 'Update Grade' : 'Save Grade';


    if (!result.success) {

        showToast(
            result.message || 'Unable to save grade.',
            'error'
        );

        return;
    }


    showToast(
        result.message || 'Grade saved successfully.',
        'success'
    );


    setTimeout(() => {
        window.location.reload();
    }, 600);

});


// ================================================================
// DELETE
// ================================================================

document.querySelectorAll('.delete-grade-btn').forEach((button) => {

    button.addEventListener('click', async () => {

        const gradeId = Number(button.dataset.gradeId);
        const studentName = button.dataset.studentName || 'this student';

        const confirmed = await confirmAction(
            `Delete the grade record for ${studentName}?`,
            'Delete Grade'
        );

        if (!confirmed) {
            return;
        }


        button.disabled = true;

        const originalText = button.innerHTML;

        button.innerHTML =
            '<span class="spinner"></span>';


        const result = await apiFetch('/api/grades.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'delete',
                grade_id: gradeId
            })
        });


        if (!result.success) {

            button.disabled = false;
            button.innerHTML = originalText;

            showToast(
                result.message || 'Unable to delete grade.',
                'error'
            );

            return;
        }


        showToast(
            result.message || 'Grade deleted successfully.',
            'success'
        );


        setTimeout(() => {
            window.location.reload();
        }, 600);

    });

});

JS;

$extraScript = str_replace(
    '__AVAILABLE_STUDENTS__',
    $availableStudentsJson,
    $extraScript
);

include __DIR__ . '/../includes/footer.php';
?>