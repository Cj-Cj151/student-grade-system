<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('teacher');

$pdo = getDbConnection();
$teacherId = $_SESSION['teacher_id'];

$stmt = $pdo->prepare("
    SELECT
        sa.assignment_id,
        s.subject_id,
        s.subject_code,
        s.subject_name,
        sec.section_id,
        sec.section_name,
        sec.course,
        sec.year_level,
        t.term_id,
        t.school_year,
        t.semester,
        t.grading_period
    FROM subject_assignments sa
    JOIN subjects s
        ON s.subject_id = sa.subject_id
    JOIN sections sec
        ON sec.section_id = sa.section_id
    JOIN academic_terms t
        ON t.term_id = sa.term_id
    WHERE sa.teacher_id = :teacher_id
      AND sa.is_active = TRUE
      AND sec.is_active = TRUE
    ORDER BY
        t.school_year DESC,
        t.term_id DESC,
        s.subject_code,
        sec.course,
        sec.year_level,
        sec.section_name
");

$stmt->execute([
    'teacher_id' => $teacherId
]);

$assignments = $stmt->fetchAll();

$subjects = [];

foreach ($assignments as $assignment) {

    $subjectId = (int) $assignment['subject_id'];

    if (!isset($subjects[$subjectId])) {

        $subjects[$subjectId] = [
            'subject_id' => $subjectId,
            'subject_code' => $assignment['subject_code'],
            'subject_name' => $assignment['subject_name']
        ];
    }
}

$subjects = array_values($subjects);

$terms = [];

foreach ($assignments as $assignment) {

    $termId = (int) $assignment['term_id'];

    if (!isset($terms[$termId])) {

        $terms[$termId] = [
            'term_id' => $termId,
            'school_year' => $assignment['school_year'],
            'semester' => $assignment['semester'],
            'grading_period' => $assignment['grading_period']
        ];
    }
}

$terms = array_values($terms);

$selectedSubject = isset($_GET['subject_id'])
    ? (int) $_GET['subject_id']
    : ($subjects[0]['subject_id'] ?? 0);

$selectedTerm = isset($_GET['term_id'])
    ? (int) $_GET['term_id']
    : ($terms[0]['term_id'] ?? 0);

$selectedCourse = trim($_GET['course'] ?? '');

$selectedYear = isset($_GET['year_level'])
    ? (int) $_GET['year_level']
    : 0;

$selectedSection = isset($_GET['section_id'])
    ? (int) $_GET['section_id']
    : 0;

$subjectOptions = [];

foreach ($assignments as $assignment) {

    if (
        (int) $assignment['subject_id'] === $selectedSubject &&
        (int) $assignment['term_id'] === $selectedTerm
    ) {

        $course = $assignment['course'];

        if (!isset($subjectOptions[$course])) {

            $subjectOptions[$course] = [
                'course' => $course
            ];
        }
    }
}

$subjectOptions = array_values($subjectOptions);

if (
    $selectedCourse === '' ||
    !in_array(
        $selectedCourse,
        array_column($subjectOptions, 'course'),
        true
    )
) {

    $selectedCourse =
        $subjectOptions[0]['course'] ?? '';
}

$yearOptions = [];

if ($selectedCourse !== '') {

    $yearStmt = $pdo->prepare("
        SELECT DISTINCT
            year_level
        FROM sections
        WHERE course = :course
          AND is_active = TRUE
        ORDER BY year_level
    ");

    $yearStmt->execute([
        'course' => $selectedCourse
    ]);

    $yearOptions = $yearStmt->fetchAll();
}

$validYears = array_map(
    'intval',
    array_column($yearOptions, 'year_level')
);

if (
    $selectedYear < 1 ||
    !in_array($selectedYear, $validYears, true)
) {

    $selectedYear =
        (int) ($yearOptions[0]['year_level'] ?? 0);
}

$sectionOptions = [];

if (
    $selectedCourse !== '' &&
    $selectedYear > 0
) {

    $sectionStmt = $pdo->prepare("
        SELECT
            section_id,
            section_name,
            year_level
        FROM sections
        WHERE course = :course
          AND year_level = :year_level
          AND is_active = TRUE
        ORDER BY section_name
    ");

    $sectionStmt->execute([
        'course' => $selectedCourse,
        'year_level' => $selectedYear
    ]);

    $sectionOptions = $sectionStmt->fetchAll();
}

$validSectionIds = array_map(
    'intval',
    array_column($sectionOptions, 'section_id')
);

if (
    $selectedSection < 1 ||
    !in_array($selectedSection, $validSectionIds, true)
) {

    $selectedSection =
        (int) ($sectionOptions[0]['section_id'] ?? 0);
}

$selectedAssignment = null;

foreach ($assignments as $assignment) {

    if (
        (int) $assignment['subject_id'] === $selectedSubject &&
        (int) $assignment['term_id'] === $selectedTerm
    ) {

        $selectedAssignment = $assignment;

        break;
    }
}

$selectedSectionInfo = null;

foreach ($sectionOptions as $section) {

    if ((int) $section['section_id'] === $selectedSection) {

        $selectedSectionInfo = $section;

        break;
    }
}

$students = [];
$availableStudents = [];

if (
    $selectedAssignment &&
    $selectedCourse !== '' &&
    $selectedYear > 0 &&
    $selectedSection > 0
) {

    $stmt = $pdo->prepare("
        SELECT
            st.student_id,
            st.first_name,
            st.last_name,
            u.login_id,
            g.grade_id,
            g.grade,
            g.remarks
        FROM students st
        JOIN users u
            ON u.user_id = st.user_id
        LEFT JOIN grades g
            ON g.student_id = st.student_id
            AND g.subject_id = :subject_id
            AND g.term_id = :term_id
        WHERE u.is_active = TRUE
          AND st.section_id = :section_id
        ORDER BY
            st.last_name,
            st.first_name
    ");

    $stmt->execute([
        'subject_id' => $selectedSubject,
        'term_id' => $selectedTerm,
        'section_id' => $selectedSection
    ]);

    $students = $stmt->fetchAll();

    foreach ($students as $student) {

        if ($student['grade_id'] === null) {

            $availableStudents[] = [
                'student_id' =>
                    (int) $student['student_id'],

                'first_name' =>
                    $student['first_name'],

                'last_name' =>
                    $student['last_name'],

                'login_id' =>
                    $student['login_id']
            ];
        }
    }
}

$selectedTermInfo = null;

foreach ($terms as $term) {

    if ((int) $term['term_id'] === $selectedTerm) {

        $selectedTermInfo = $term;

        break;
    }
}

$pageTitle = 'Grade Management';
$pageSubtitle = 'Encode and update grades for your students';
$activeNav = 'grades';

include __DIR__ . '/../includes/header.php';
?>

<div class="glass-card panel">

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Grade Management
            </h2>

            <p class="panel-subtitle">
                Select a subject, course, year level, section, and academic term
            </p>

        </div>

    </div>

    <form
        method="GET"
        id="filterForm"
        class="grade-filter-grid"
    >

        <div class="grade-filter-field">

            <label for="subjectSelect">
                Subject
            </label>

            <select
                name="subject_id"
                id="subjectSelect"
                class="form-control"
                required
            >

                <?php if (empty($subjects)): ?>

                    <option value="">
                        No subjects assigned
                    </option>

                <?php else: ?>

                    <?php foreach ($subjects as $subject): ?>

                        <option
                            value="<?= (int) $subject['subject_id'] ?>"
                            <?= $selectedSubject == $subject['subject_id'] ? 'selected' : '' ?>
                        >
                            <?= clean(
                                $subject['subject_code']
                                . ' - '
                                . $subject['subject_name']
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                <?php endif; ?>

            </select>

        </div>

        <div class="grade-filter-field">

            <label for="courseSelect">
                Course
            </label>

            <select
                name="course"
                id="courseSelect"
                class="form-control"
                required
            >

                <?php if (empty($subjectOptions)): ?>

                    <option value="">
                        No courses available
                    </option>

                <?php else: ?>

                    <?php foreach ($subjectOptions as $course): ?>

                        <option
                            value="<?= clean($course['course']) ?>"
                            <?= $selectedCourse === $course['course'] ? 'selected' : '' ?>
                        >
                            <?= clean($course['course']) ?>
                        </option>

                    <?php endforeach; ?>

                <?php endif; ?>

            </select>

        </div>

        <div class="grade-filter-field">

            <label for="yearSelect">
                Year Level
            </label>

            <select
                name="year_level"
                id="yearSelect"
                class="form-control"
                required
            >

                <?php if (empty($yearOptions)): ?>

                    <option value="">
                        No year levels available
                    </option>

                <?php else: ?>

                    <?php foreach ($yearOptions as $year): ?>

                        <option
                            value="<?= (int) $year['year_level'] ?>"
                            <?= $selectedYear == $year['year_level'] ? 'selected' : '' ?>
                        >
                            <?= (int) $year['year_level'] === 1
                                ? '1st Year'
                                : (
                                    (int) $year['year_level'] === 2
                                        ? '2nd Year'
                                        : (
                                            (int) $year['year_level'] === 3
                                                ? '3rd Year'
                                                : (
                                                    (int) $year['year_level'] . 'th Year'
                                                )
                                        )
                                )
                            ?>
                        </option>

                    <?php endforeach; ?>

                <?php endif; ?>

            </select>

        </div>

        <div class="grade-filter-field">

            <label for="sectionSelect">
                Section
            </label>

            <select
                name="section_id"
                id="sectionSelect"
                class="form-control"
                required
            >

                <?php if (empty($sectionOptions)): ?>

                    <option value="">
                        No sections available
                    </option>

                <?php else: ?>

                    <?php foreach ($sectionOptions as $section): ?>

                        <option
                            value="<?= (int) $section['section_id'] ?>"
                            <?= $selectedSection == $section['section_id'] ? 'selected' : '' ?>
                        >
                            <?= clean($section['section_name']) ?>
                        </option>

                    <?php endforeach; ?>

                <?php endif; ?>

            </select>

        </div>

        <div class="grade-filter-field">

            <label for="termSelect">
                Academic Term
            </label>

            <select
                name="term_id"
                id="termSelect"
                class="form-control"
                required
            >

                <?php if (empty($terms)): ?>

                    <option value="">
                        No academic terms available
                    </option>

                <?php else: ?>

                    <?php foreach ($terms as $term): ?>

                        <option
                            value="<?= (int) $term['term_id'] ?>"
                            <?= $selectedTerm == $term['term_id'] ? 'selected' : '' ?>
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

                <?php endif; ?>

            </select>

        </div>

    </form>

</div>

<div class="glass-card panel">

    <div class="grade-table-header">

        <div>

            <h2 class="panel-title">
                Student Grades
            </h2>

            <p class="grade-table-subtitle">

                <?php if ($selectedAssignment): ?>

                    <?= clean(
                        $selectedAssignment['subject_code']
                        . ' - '
                        . $selectedAssignment['subject_name']
                    ) ?>

                    <br>

                    <?= clean($selectedCourse) ?>

                    <?php if ($selectedSectionInfo): ?>

                        <br>

                        <?= clean(
                            $selectedSectionInfo['section_name']
                            . ' - '
                            . $selectedSectionInfo['year_level']
                            . ' Year'
                        ) ?>

                    <?php endif; ?>

                    <br>

                    <?php if ($selectedTermInfo): ?>

                        <?= clean(
                            $selectedTermInfo['school_year']
                            . ' - '
                            . $selectedTermInfo['semester']
                            . ' - '
                            . $selectedTermInfo['grading_period']
                        ) ?>

                    <?php endif; ?>

                <?php else: ?>

                    Select a valid subject, course, year level, section, and academic term.

                <?php endif; ?>

            </p>

        </div>

        <?php if ($selectedAssignment && $selectedSection > 0): ?>

            <div class="grade-table-actions">

                <div class="search-input-wrap">

                    <input
                        type="text"
                        id="searchInput"
                        class="form-control search-input"
                        placeholder="Search student..."
                        autocomplete="off"
                    >

                </div>

                <button
                    type="button"
                    class="btn btn-primary"
                    id="openAddGradeBtn"
                >
                    Add Grade
                </button>

            </div>

        <?php endif; ?>

    </div>

    <?php if (empty($assignments)): ?>

        <div class="empty-state">

            <h3>No Subject Assignments</h3>

            <p>
                You currently have no assigned subjects.
            </p>

        </div>

    <?php elseif (!$selectedAssignment): ?>

        <div class="empty-state">

            <h3>No Assignment Found</h3>

            <p>
                The selected subject and academic term are not assigned to you.
            </p>

        </div>

    <?php elseif ($selectedSection < 1): ?>

        <div class="empty-state">

            <h3>No Section Selected</h3>

            <p>
                Select a section to view the students.
            </p>

        </div>

    <?php elseif (empty($students)): ?>

        <div class="empty-state">

            <h3>No Students Found</h3>

            <p>
                There are no active students registered in this section.
            </p>

        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table
                class="data-table grade-data-table"
                id="studentsTable"
            >

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
                            data-name="<?= clean(
                                strtolower(
                                    $student['first_name']
                                    . ' '
                                    . $student['last_name']
                                    . ' '
                                    . $student['login_id']
                                )
                            ) ?>"
                        >

                            <td>
                                <?= $index + 1 ?>
                            </td>

                            <td>
                                <strong>
                                    <?= clean($student['login_id']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= clean(
                                    $student['first_name']
                                    . ' '
                                    . $student['last_name']
                                ) ?>
                            </td>

                            <td>

                                <?php if ($student['grade'] !== null): ?>

                                    <strong>
                                        <?= number_format(
                                            (float) $student['grade'],
                                            2
                                        ) ?>
                                    </strong>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <td>

                                <?= $student['remarks']
                                    ? clean($student['remarks'])
                                    : '—'
                                ?>

                            </td>

                            <td>

                                <span
                                    class="badge status-badge <?= gradeStatusClass($student['grade']) ?>"
                                >
                                    <?= gradeStatus($student['grade']) ?>
                                </span>

                            </td>

                            <td>

                                <div class="row-actions">

                                    <?php if ($student['grade_id'] !== null): ?>

                                        <button
                                            type="button"
                                            class="btn btn-primary btn-sm edit-grade-btn"
                                            data-grade-id="<?= (int) $student['grade_id'] ?>"
                                            data-student-id="<?= (int) $student['student_id'] ?>"
                                            data-student-name="<?= clean(
                                                $student['first_name']
                                                . ' '
                                                . $student['last_name']
                                            ) ?>"
                                            data-grade="<?= htmlspecialchars(
                                                (string) $student['grade'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            data-remarks="<?= clean(
                                                $student['remarks'] ?? ''
                                            ) ?>"
                                        >
                                            Edit
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-danger btn-sm delete-grade-btn"
                                            data-grade-id="<?= (int) $student['grade_id'] ?>"
                                            data-student-name="<?= clean(
                                                $student['first_name']
                                                . ' '
                                                . $student['last_name']
                                            ) ?>"
                                        >
                                            Delete
                                        </button>

                                    <?php else: ?>

                                        <button
                                            type="button"
                                            class="btn btn-primary btn-sm add-student-grade-btn"
                                            data-student-id="<?= (int) $student['student_id'] ?>"
                                            data-student-name="<?= clean(
                                                $student['first_name']
                                                . ' '
                                                . $student['last_name']
                                            ) ?>"
                                        >
                                            Add Grade
                                        </button>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <div
            class="empty-state"
            id="noResultsState"
            style="display:none;"
        >
            <p>
                No students match your search.
            </p>
        </div>

    <?php endif; ?>

</div>

<div
    class="modal-overlay"
    id="gradeModal"
    aria-hidden="true"
>

    <div class="glass-card modal-box grade-modal-box">

        <div class="modal-header">

            <div>

                <h3
                    class="modal-title"
                    id="gradeModalTitle"
                >
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
            >
                ×
            </button>

        </div>

        <form id="gradeForm">

            <input
                type="hidden"
                id="gradeId"
            >

            <div class="form-group">

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

                <input
                    type="hidden"
                    id="studentId"
                >

                <div
                    id="studentSuggestions"
                    class="student-suggestions"
                ></div>

            </div>

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
                    placeholder="Enter grade"
                    required
                >

            </div>

            <div class="form-group">

                <label for="remarksValue">
                    Remarks
                </label>

                <input
                    type="text"
                    id="remarksValue"
                    class="form-control"
                    maxlength="255"
                    placeholder="Enter remarks"
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

const availableStudents = __AVAILABLE_STUDENTS__;

const filterForm =
    document.getElementById('filterForm');

const subjectSelect =
    document.getElementById('subjectSelect');

const courseSelect =
    document.getElementById('courseSelect');

const yearSelect =
    document.getElementById('yearSelect');

const sectionSelect =
    document.getElementById('sectionSelect');

const termSelect =
    document.getElementById('termSelect');

const gradeModal =
    document.getElementById('gradeModal');

const gradeForm =
    document.getElementById('gradeForm');

const gradeIdInput =
    document.getElementById('gradeId');

const studentSearch =
    document.getElementById('studentSearch');

const studentIdInput =
    document.getElementById('studentId');

const studentSuggestions =
    document.getElementById('studentSuggestions');

const gradeValueInput =
    document.getElementById('gradeValue');

const remarksValueInput =
    document.getElementById('remarksValue');

const gradeModalTitle =
    document.getElementById('gradeModalTitle');

const saveGradeText =
    document.getElementById('saveGradeText');

const saveGradeBtn =
    document.getElementById('saveGradeBtn');

const openAddGradeBtn =
    document.getElementById('openAddGradeBtn');

const closeGradeModal =
    document.getElementById('closeGradeModal');

const cancelGradeBtn =
    document.getElementById('cancelGradeBtn');

const searchInput =
    document.getElementById('searchInput');

const rows =
    document.querySelectorAll(
        '#studentsTable tbody tr'
    );

const noResultsState =
    document.getElementById('noResultsState');


function submitFilters() {

    if (!filterForm) {
        return;
    }

    filterForm.submit();

}


subjectSelect?.addEventListener(
    'change',
    () => {

        if (courseSelect) {
            courseSelect.value = '';
        }

        if (yearSelect) {
            yearSelect.value = '';
        }

        if (sectionSelect) {
            sectionSelect.value = '';
        }

        submitFilters();

    }
);


termSelect?.addEventListener(
    'change',
    () => {

        if (courseSelect) {
            courseSelect.value = '';
        }

        if (yearSelect) {
            yearSelect.value = '';
        }

        if (sectionSelect) {
            sectionSelect.value = '';
        }

        submitFilters();

    }
);


courseSelect?.addEventListener(
    'change',
    () => {

        if (yearSelect) {
            yearSelect.value = '';
        }

        if (sectionSelect) {
            sectionSelect.value = '';
        }

        submitFilters();

    }
);


yearSelect?.addEventListener(
    'change',
    () => {

        if (sectionSelect) {
            sectionSelect.value = '';
        }

        submitFilters();

    }
);


sectionSelect?.addEventListener(
    'change',
    () => {

        submitFilters();

    }
);


if (searchInput) {

    searchInput.addEventListener(
        'input',
        () => {

            const search =
                searchInput.value
                    .trim()
                    .toLowerCase();

            let visibleCount = 0;

            rows.forEach(row => {

                const show =
                    row.dataset.name
                        .includes(search);

                row.style.display =
                    show ? '' : 'none';

                if (show) {
                    visibleCount++;
                }

            });

            if (noResultsState) {

                noResultsState.style.display =
                    visibleCount === 0
                        ? 'block'
                        : 'none';

            }

        }
    );

}


function clearStudentSelection() {

    studentSearch.value = '';

    studentIdInput.value = '';

    studentSuggestions.innerHTML = '';

    studentSuggestions.classList.remove(
        'show'
    );

}


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
        student.login_id +
        ' - ' +
        student.first_name +
        ' ' +
        student.last_name;

    studentIdInput.value =
        student.student_id;

    studentSuggestions.classList.remove(
        'show'
    );

}


function showStudentSuggestions() {

    const search =
        studentSearch.value
            .trim()
            .toLowerCase();

    const matches =
        availableStudents
            .filter(student => {

                const id =
                    String(
                        student.login_id || ''
                    ).toLowerCase();

                const first =
                    String(
                        student.first_name || ''
                    ).toLowerCase();

                const last =
                    String(
                        student.last_name || ''
                    ).toLowerCase();

                const full =
                    first + ' ' + last;

                return (
                    search === '' ||
                    id.includes(search) ||
                    first.includes(search) ||
                    last.includes(search) ||
                    full.includes(search)
                );

            })
            .slice(0, 8);

    studentSuggestions.innerHTML = '';

    if (matches.length === 0) {

        studentSuggestions.innerHTML =
            '<div class="student-suggestion-empty">No students found.</div>';

        studentSuggestions.classList.add(
            'show'
        );

        return;

    }

    matches.forEach(student => {

        const button =
            document.createElement('button');

        button.type =
            'button';

        button.className =
            'student-suggestion-item';

        button.innerHTML =
            '<strong>' +
            escapeHtml(student.login_id) +
            '</strong>' +
            '<span>' +
            escapeHtml(
                student.first_name +
                ' ' +
                student.last_name
            ) +
            '</span>';

        button.addEventListener(
            'click',
            () => selectStudent(student)
        );

        studentSuggestions.appendChild(
            button
        );

    });

    studentSuggestions.classList.add(
        'show'
    );

}


function openGradeModal(
    mode,
    data = {}
) {

    if (!gradeModal) {
        return;
    }

    gradeModal.classList.add(
        'show'
    );

    gradeModal.setAttribute(
        'aria-hidden',
        'false'
    );

    if (mode === 'add') {

        gradeModalTitle.textContent =
            'Add Student Grade';

        saveGradeText.textContent =
            'Save Grade';

        gradeIdInput.value =
            '';

        gradeValueInput.value =
            '';

        remarksValueInput.value =
            '';

        studentSearch.disabled =
            false;

        clearStudentSelection();

        if (data.studentId) {

            studentSearch.value =
                data.studentName || '';

            studentIdInput.value =
                data.studentId;

            studentSearch.disabled =
                true;

        }

    } else {

        gradeModalTitle.textContent =
            'Edit Student Grade';

        saveGradeText.textContent =
            'Update Grade';

        gradeIdInput.value =
            data.gradeId || '';

        studentSearch.value =
            data.studentName || '';

        studentIdInput.value =
            data.studentId || '';

        gradeValueInput.value =
            data.grade || '';

        remarksValueInput.value =
            data.remarks || '';

        studentSearch.disabled =
            true;

    }

    setTimeout(
        () => {

            if (
                mode === 'add' &&
                !data.studentId
            ) {

                studentSearch.focus();

                showStudentSuggestions();

            } else {

                gradeValueInput.focus();

            }

        },
        100
    );

}


function closeGradeModalWindow() {

    if (!gradeModal) {
        return;
    }

    gradeModal.classList.remove(
        'show'
    );

    gradeModal.setAttribute(
        'aria-hidden',
        'true'
    );

    gradeForm.reset();

    gradeIdInput.value =
        '';

    studentIdInput.value =
        '';

    studentSearch.disabled =
        false;

    studentSuggestions.classList.remove(
        'show'
    );

}


openAddGradeBtn?.addEventListener(
    'click',
    () => {

        if (availableStudents.length === 0) {

            showToast(
                'All students in this section already have a grade for this subject and term.',
                'info'
            );

            return;

        }

        openGradeModal(
            'add'
        );

    }
);


document
    .querySelectorAll(
        '.add-student-grade-btn'
    )
    .forEach(button => {

        button.addEventListener(
            'click',
            () => {

                openGradeModal(
                    'add',
                    {
                        studentId:
                            button.dataset.studentId,

                        studentName:
                            button.dataset.studentName
                    }
                );

            }
        );

    });


document
    .querySelectorAll(
        '.edit-grade-btn'
    )
    .forEach(button => {

        button.addEventListener(
            'click',
            () => {

                openGradeModal(
                    'edit',
                    {
                        gradeId:
                            button.dataset.gradeId,

                        studentId:
                            button.dataset.studentId,

                        studentName:
                            button.dataset.studentName,

                        grade:
                            button.dataset.grade,

                        remarks:
                            button.dataset.remarks
                    }
                );

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
    event => {

        if (
            event.target ===
            gradeModal
        ) {

            closeGradeModalWindow();

        }

    }
);


document.addEventListener(
    'keydown',
    event => {

        if (
            event.key ===
            'Escape'
        ) {

            closeGradeModalWindow();

        }

    }
);


studentSearch?.addEventListener(
    'input',
    () => {

        if (!studentSearch.disabled) {

            studentIdInput.value =
                '';

            showStudentSuggestions();

        }

    }
);


studentSearch?.addEventListener(
    'focus',
    () => {

        if (!studentSearch.disabled) {

            showStudentSuggestions();

        }

    }
);


document.addEventListener(
    'click',
    event => {

        if (
            studentSearch &&
            studentSuggestions &&
            !studentSearch.contains(
                event.target
            ) &&
            !studentSuggestions.contains(
                event.target
            )
        ) {

            studentSuggestions.classList.remove(
                'show'
            );

        }

    }
);


gradeForm?.addEventListener(
    'submit',
    async event => {

        event.preventDefault();

        const gradeId =
            gradeIdInput.value.trim();

        const studentId =
            studentIdInput.value.trim();

        const grade =
            gradeValueInput.value.trim();

        const remarks =
            remarksValueInput.value.trim();

        if (
            !gradeId &&
            !studentId
        ) {

            showToast(
                'Please select a student.',
                'error'
            );

            studentSearch.focus();

            return;

        }

        if (grade === '') {

            showToast(
                'Please enter a grade.',
                'error'
            );

            gradeValueInput.focus();

            return;

        }

        const numericGrade =
            Number(grade);

        if (
            Number.isNaN(
                numericGrade
            ) ||
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

        saveGradeBtn.disabled =
            true;

        saveGradeText.innerHTML =
            '<span class="spinner"></span> Saving...';

        try {

            const result =
                await apiFetch(
                    '/api/grades.php',
                    {
                        method: 'POST',

                        body:
                            JSON.stringify(
                                {
                                    action:
                                        gradeId
                                            ? 'update'
                                            : 'add',

                                    grade_id:
                                        gradeId
                                            ? Number(gradeId)
                                            : null,

                                    student_id:
                                        studentId
                                            ? Number(studentId)
                                            : null,

                                    subject_id:
                                        Number(
                                            subjectSelect.value
                                        ),

                                    term_id:
                                        Number(
                                            termSelect.value
                                        ),

                                    grade:
                                        numericGrade,

                                    remarks:
                                        remarks
                                }
                            )
                    }
                );

            if (!result.success) {

                showToast(
                    result.message ||
                    'Unable to save grade.',
                    'error'
                );

                return;

            }

            showToast(
                result.message ||
                'Grade saved successfully.',
                'success'
            );

            setTimeout(
                () => {
                    window.location.reload();
                },
                600
            );

        } catch (error) {

            showToast(
                'Unable to save grade.',
                'error'
            );

        } finally {

            saveGradeBtn.disabled =
                false;

            saveGradeText.textContent =
                gradeId
                    ? 'Update Grade'
                    : 'Save Grade';

        }

    }
);


document
    .querySelectorAll(
        '.delete-grade-btn'
    )
    .forEach(button => {

        button.addEventListener(
            'click',
            async () => {

                const gradeId =
                    Number(
                        button.dataset.gradeId
                    );

                const studentName =
                    button.dataset.studentName ||
                    'this student';

                const confirmed =
                    await confirmAction(
                        'Delete the grade record for ' +
                        studentName +
                        '?',
                        'Delete Grade'
                    );

                if (!confirmed) {
                    return;
                }

                button.disabled =
                    true;

                const originalText =
                    button.textContent;

                button.innerHTML =
                    '<span class="spinner"></span>';

                try {

                    const result =
                        await apiFetch(
                            '/api/grades.php',
                            {
                                method: 'POST',

                                body:
                                    JSON.stringify(
                                        {
                                            action:
                                                'delete',

                                            grade_id:
                                                gradeId
                                        }
                                    )
                            }
                        );

                    if (!result.success) {

                        button.disabled =
                            false;

                        button.textContent =
                            originalText;

                        showToast(
                            result.message ||
                            'Unable to delete grade.',
                            'error'
                        );

                        return;

                    }

                    showToast(
                        result.message ||
                        'Grade deleted successfully.',
                        'success'
                    );

                    setTimeout(
                        () => {
                            window.location.reload();
                        },
                        600
                    );

                } catch (error) {

                    button.disabled =
                        false;

                    button.textContent =
                        originalText;

                    showToast(
                        'Unable to delete grade.',
                        'error'
                    );

                }

            }
        );

    });

JS;

$extraScript = str_replace(
    '__AVAILABLE_STUDENTS__',
    $availableStudentsJson,
    $extraScript
);

include __DIR__ . '/../includes/footer.php';
?>