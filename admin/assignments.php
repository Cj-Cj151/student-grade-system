<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

$pdo = getDbConnection();

$teachers = $pdo->query("
    SELECT
        t.teacher_id,
        t.first_name,
        t.last_name
    FROM teachers t
    JOIN users u
        ON u.user_id = t.user_id
    WHERE u.is_active = TRUE
    ORDER BY t.last_name, t.first_name
")->fetchAll();

$subjects = $pdo->query("
    SELECT
        subject_id,
        subject_code,
        subject_name
    FROM subjects
    ORDER BY subject_code
")->fetchAll();

$courses = $pdo->query("
    SELECT DISTINCT course
    FROM sections
    WHERE is_active = TRUE
      AND TRIM(course) <> ''
    ORDER BY course
")->fetchAll();

$terms = $pdo->query("
    SELECT
        term_id,
        school_year,
        semester,
        grading_period,
        status
    FROM academic_terms
    ORDER BY
        school_year DESC,
        CASE semester
            WHEN '1st Semester' THEN 1
            WHEN '2nd Semester' THEN 2
            ELSE 3
        END,
        CASE grading_period
            WHEN 'Prelim' THEN 1
            WHEN 'Midterm' THEN 2
            WHEN 'Semi-Final' THEN 3
            WHEN 'Final' THEN 4
            ELSE 5
        END
")->fetchAll();

$assignments = $pdo->query("
    SELECT
        sa.assignment_id,
        sa.teacher_id,
        sa.subject_id,
        sa.section_id,
        sa.term_id,
        t.first_name AS teacher_first_name,
        t.last_name AS teacher_last_name,
        s.subject_code,
        s.subject_name,
        sec.course,
        sec.year_level,
        sec.section_name,
        at.school_year,
        at.semester,
        at.grading_period,
        sa.is_active
    FROM subject_assignments sa
    JOIN teachers t
        ON t.teacher_id = sa.teacher_id
    JOIN subjects s
        ON s.subject_id = sa.subject_id
    JOIN sections sec
        ON sec.section_id = sa.section_id
    JOIN academic_terms at
        ON at.term_id = sa.term_id
    ORDER BY
        at.school_year DESC,
        s.subject_code,
        sec.course,
        sec.year_level,
        sec.section_name
")->fetchAll();

$pageTitle = 'Assign Subject';
$pageSubtitle = 'Assign subjects to teachers and sections';
$activeNav = 'assignments';

include __DIR__ . '/../includes/header.php';
?>

<div class="glass-card panel">

    <div class="panel-header">

        <div>
            <h2 class="panel-title">
                Assign Subject
            </h2>

            <p class="panel-subtitle">
                Manage subject assignments for teachers and sections
            </p>
        </div>

        <button
            type="button"
            class="btn btn-primary"
            onclick="openAssignmentModal()"
        >
            Assign Subject
        </button>

    </div>

    <?php if (empty($assignments)): ?>

        <div class="empty-state">

            <h3>No Subject Assignments Yet</h3>

            <p>
                Assign a subject to a teacher and section to get started.
            </p>

        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table class="data-table" id="assignmentsTable">

                <thead>

                    <tr>
                        <th>Teacher</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Academic Term</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($assignments as $assignment): ?>

                        <tr>

                            <td>
                                <?= clean(
                                    $assignment['teacher_last_name'] . ', ' .
                                    $assignment['teacher_first_name']
                                ) ?>
                            </td>

                            <td>

                                <strong>
                                    <?= clean($assignment['subject_code']) ?>
                                </strong>

                                <div>
                                    <?= clean($assignment['subject_name']) ?>
                                </div>

                            </td>

                            <td>

                                <strong>
                                    <?= clean($assignment['section_name']) ?>
                                </strong>

                                <div>
                                    <?= clean($assignment['course']) ?>
                                </div>

                                <small>
                                    <?php
                                    $year = (int) $assignment['year_level'];

                                    if ($year === 1) {
                                        $suffix = 'st';
                                    } elseif ($year === 2) {
                                        $suffix = 'nd';
                                    } elseif ($year === 3) {
                                        $suffix = 'rd';
                                    } else {
                                        $suffix = 'th';
                                    }
                                    ?>

                                    <?= $year . $suffix ?> Year
                                </small>

                            </td>

                            <td>

                                <strong>
                                    <?= clean($assignment['school_year']) ?>
                                </strong>

                                <div>
                                    <?= clean($assignment['semester']) ?>
                                </div>

                                <small>
                                    <?= clean($assignment['grading_period']) ?>
                                </small>

                            </td>

                            <td>

                                <?php if ($assignment['is_active']): ?>

                                    <span class="badge badge-passed">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="badge badge-pending">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <button
                                    type="button"
                                    class="btn btn-secondary btn-small"
                                    onclick='openEditAssignmentModal(<?= json_encode($assignment) ?>)'
                                >
                                    Edit
                                </button>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<div class="modal-overlay" id="assignmentModal">

    <div class="glass-card modal-box">

        <div class="modal-header">

            <div>
                <h3 class="modal-title">
                    Assign Subject to Teacher
                </h3>

                <p class="panel-subtitle">
                    Select the teacher, subject, section, and academic term.
                </p>
            </div>

            <button
                type="button"
                class="modal-close"
                onclick="closeModal('assignmentModal')"
            >
                ×
            </button>

        </div>

        <div
            id="assignmentAlert"
            class="alert alert-error"
        ></div>

        <form id="assignmentForm">

            <div class="form-group">

                <label for="teacher_id">
                    Teacher
                </label>

                <select
                    id="teacher_id"
                    class="form-control"
                    required
                >

                    <option value="">
                        Select teacher
                    </option>

                    <?php foreach ($teachers as $teacher): ?>

                        <option value="<?= (int) $teacher['teacher_id'] ?>">
                            <?= clean(
                                $teacher['last_name'] . ', ' .
                                $teacher['first_name']
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label for="subject_id">
                    Subject
                </label>

                <select
                    id="subject_id"
                    class="form-control"
                    required
                >

                    <option value="">
                        Select subject
                    </option>

                    <?php foreach ($subjects as $subject): ?>

                        <option value="<?= (int) $subject['subject_id'] ?>">
                            <?= clean(
                                $subject['subject_code'] . ' - ' .
                                $subject['subject_name']
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label for="course">
                    Course
                </label>

                <select
                    id="course"
                    class="form-control"
                    required
                >

                    <option value="">
                        Select course
                    </option>

                    <?php foreach ($courses as $course): ?>

                        <option value="<?= clean($course['course']) ?>">
                            <?= clean($course['course']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label for="year_level">
                    Year Level
                </label>

                <select
                    id="year_level"
                    class="form-control"
                    required
                    disabled
                >

                    <option value="">
                        Select course first
                    </option>

                </select>

            </div>

            <div class="form-group">

                <label for="section_id">
                    Section
                </label>

                <select
                    id="section_id"
                    class="form-control"
                    required
                    disabled
                >

                    <option value="">
                        Select year level first
                    </option>

                </select>

            </div>

            <div class="form-group">

                <label for="term_id">
                    Academic Term
                </label>

                <select
                    id="term_id"
                    class="form-control"
                    required
                >

                    <option value="">
                        Select academic term
                    </option>

                    <?php foreach ($terms as $term): ?>

                        <option
                            value="<?= (int) $term['term_id'] ?>"
                            <?= $term['status'] === 'active' ? 'selected' : '' ?>
                        >
                            <?= clean(
                                $term['school_year'] . ' / ' .
                                $term['semester'] . ' / ' .
                                $term['grading_period']
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="closeModal('assignmentModal')"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                    id="assignmentSaveBtn"
                >
                    Assign Subject
                </button>

            </div>

        </form>

    </div>

</div>

<div class="modal-overlay" id="editAssignmentModal">

    <div class="glass-card modal-box">

        <div class="modal-header">

            <div>
                <h3 class="modal-title">
                    Edit Subject Assignment
                </h3>

                <p class="panel-subtitle">
                    Update the teacher, subject, section, or academic term.
                </p>
            </div>

            <button
                type="button"
                class="modal-close"
                onclick="closeModal('editAssignmentModal')"
            >
                ×
            </button>

        </div>

        <div
            id="editAssignmentAlert"
            class="alert alert-error"
        ></div>

        <form id="editAssignmentForm">

            <input
                type="hidden"
                id="edit_assignment_id"
            >

            <div class="form-group">

                <label for="edit_teacher_id">
                    Teacher
                </label>

                <select
                    id="edit_teacher_id"
                    class="form-control"
                    required
                >

                    <option value="">
                        Select teacher
                    </option>

                    <?php foreach ($teachers as $teacher): ?>

                        <option value="<?= (int) $teacher['teacher_id'] ?>">
                            <?= clean(
                                $teacher['last_name'] . ', ' .
                                $teacher['first_name']
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label for="edit_subject_id">
                    Subject
                </label>

                <select
                    id="edit_subject_id"
                    class="form-control"
                    required
                >

                    <option value="">
                        Select subject
                    </option>

                    <?php foreach ($subjects as $subject): ?>

                        <option value="<?= (int) $subject['subject_id'] ?>">
                            <?= clean(
                                $subject['subject_code'] . ' - ' .
                                $subject['subject_name']
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label for="edit_course">
                    Course
                </label>

                <select
                    id="edit_course"
                    class="form-control"
                    required
                >

                    <option value="">
                        Select course
                    </option>

                    <?php foreach ($courses as $course): ?>

                        <option value="<?= clean($course['course']) ?>">
                            <?= clean($course['course']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label for="edit_year_level">
                    Year Level
                </label>

                <select
                    id="edit_year_level"
                    class="form-control"
                    required
                    disabled
                >

                    <option value="">
                        Select course first
                    </option>

                </select>

            </div>

            <div class="form-group">

                <label for="edit_section_id">
                    Section
                </label>

                <select
                    id="edit_section_id"
                    class="form-control"
                    required
                    disabled
                >

                    <option value="">
                        Select year level first
                    </option>

                </select>

            </div>

            <div class="form-group">

                <label for="edit_term_id">
                    Academic Term
                </label>

                <select
                    id="edit_term_id"
                    class="form-control"
                    required
                >

                    <option value="">
                        Select academic term
                    </option>

                    <?php foreach ($terms as $term): ?>

                        <option value="<?= (int) $term['term_id'] ?>">
                            <?= clean(
                                $term['school_year'] . ' / ' .
                                $term['semester'] . ' / ' .
                                $term['grading_period']
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="closeModal('editAssignmentModal')"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                    id="editAssignmentSaveBtn"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>

<?php

$extraScript = <<<'JS'
const courseInput =
    document.getElementById('course');

const yearLevelInput =
    document.getElementById('year_level');

const sectionInput =
    document.getElementById('section_id');

const assignmentForm =
    document.getElementById('assignmentForm');

const editCourseInput =
    document.getElementById('edit_course');

const editYearLevelInput =
    document.getElementById('edit_year_level');

const editSectionInput =
    document.getElementById('edit_section_id');

const editAssignmentForm =
    document.getElementById('editAssignmentForm');

function openAssignmentModal() {

    assignmentForm.reset();

    yearLevelInput.innerHTML =
        '<option value="">Select course first</option>';

    yearLevelInput.disabled = true;

    sectionInput.innerHTML =
        '<option value="">Select year level first</option>';

    sectionInput.disabled = true;

    document
        .getElementById('assignmentAlert')
        .classList.remove('show');

    openModal('assignmentModal');
}

async function loadYearLevels(
    course,
    yearInput,
    sectionInputElement,
    selectedYearLevel = ''
) {

    yearInput.innerHTML =
        '<option value="">Loading year levels...</option>';

    yearInput.disabled = true;

    sectionInputElement.innerHTML =
        '<option value="">Select year level first</option>';

    sectionInputElement.disabled = true;

    if (!course) {

        yearInput.innerHTML =
            '<option value="">Select course first</option>';

        return;
    }

    try {

        const yearLevels = [];

        for (let year = 1; year <= 4; year++) {

            const response =
                await fetch(
                    '../api/sections.php?course=' +
                    encodeURIComponent(course) +
                    '&year_level=' +
                    year
                );

            const result =
                await response.json();

            if (
                response.ok &&
                result.success &&
                result.sections.length > 0
            ) {
                yearLevels.push(year);
            }
        }

        yearInput.innerHTML =
            '<option value="">Select year level</option>';

        yearLevels.forEach(year => {

            const option =
                document.createElement('option');

            option.value = year;

            if (year === 1) {
                option.textContent = '1st Year';
            } else if (year === 2) {
                option.textContent = '2nd Year';
            } else if (year === 3) {
                option.textContent = '3rd Year';
            } else {
                option.textContent = '4th Year';
            }

            if (
                String(year) ===
                String(selectedYearLevel)
            ) {
                option.selected = true;
            }

            yearInput.appendChild(option);
        });

        yearInput.disabled =
            yearLevels.length === 0;

        if (yearLevels.length === 0) {

            yearInput.innerHTML =
                '<option value="">No year levels available</option>';
        }

    } catch (error) {

        yearInput.innerHTML =
            '<option value="">Unable to load year levels</option>';
    }
}

async function loadSections(
    course,
    yearLevel,
    sectionInputElement,
    selectedSectionId = ''
) {

    sectionInputElement.innerHTML =
        '<option value="">Loading sections...</option>';

    sectionInputElement.disabled = true;

    if (!course || !yearLevel) {

        sectionInputElement.innerHTML =
            '<option value="">Select year level first</option>';

        return;
    }

    try {

        const response =
            await fetch(
                '../api/sections.php?course=' +
                encodeURIComponent(course) +
                '&year_level=' +
                encodeURIComponent(yearLevel)
            );

        const result =
            await response.json();

        if (!response.ok || !result.success) {
            throw new Error();
        }

        sectionInputElement.innerHTML =
            '<option value="">Select section</option>';

        result.sections.forEach(section => {

            const option =
                document.createElement('option');

            option.value =
                section.section_id;

            option.textContent =
                section.section_name;

            if (
                String(section.section_id) ===
                String(selectedSectionId)
            ) {
                option.selected = true;
            }

            sectionInputElement.appendChild(option);
        });

        sectionInputElement.disabled =
            result.sections.length === 0;

        if (result.sections.length === 0) {

            sectionInputElement.innerHTML =
                '<option value="">No sections available</option>';
        }

    } catch (error) {

        sectionInputElement.innerHTML =
            '<option value="">Unable to load sections</option>';
    }
}

courseInput.addEventListener('change', async () => {

    await loadYearLevels(
        courseInput.value,
        yearLevelInput,
        sectionInput
    );
});

yearLevelInput.addEventListener('change', async () => {

    await loadSections(
        courseInput.value,
        yearLevelInput.value,
        sectionInput
    );
});

editCourseInput.addEventListener('change', async () => {

    await loadYearLevels(
        editCourseInput.value,
        editYearLevelInput,
        editSectionInput
    );
});

editYearLevelInput.addEventListener('change', async () => {

    await loadSections(
        editCourseInput.value,
        editYearLevelInput.value,
        editSectionInput
    );
});

async function openEditAssignmentModal(assignment) {

    document.getElementById('edit_assignment_id').value =
        assignment.assignment_id;

    document.getElementById('edit_teacher_id').value =
        assignment.teacher_id;

    document.getElementById('edit_subject_id').value =
        assignment.subject_id;

    document.getElementById('edit_term_id').value =
        assignment.term_id;

    editCourseInput.value =
        assignment.course;

    document
        .getElementById('editAssignmentAlert')
        .classList.remove('show');

    await loadYearLevels(
        assignment.course,
        editYearLevelInput,
        editSectionInput,
        assignment.year_level
    );

    await loadSections(
        assignment.course,
        assignment.year_level,
        editSectionInput,
        assignment.section_id
    );

    openModal('editAssignmentModal');
}

assignmentForm.addEventListener('submit', async (event) => {

    event.preventDefault();

    const alertBox =
        document.getElementById('assignmentAlert');

    alertBox.classList.remove('show');

    const payload = {

        teacher_id:
            Number(
                document.getElementById('teacher_id').value
            ),

        subject_id:
            Number(
                document.getElementById('subject_id').value
            ),

        section_id:
            Number(
                document.getElementById('section_id').value
            ),

        term_id:
            Number(
                document.getElementById('term_id').value
            )
    };

    const saveButton =
        document.getElementById('assignmentSaveBtn');

    saveButton.disabled = true;

    saveButton.innerHTML =
        '<span class="spinner"></span> Assigning...';

    try {

        const result =
            await apiFetch('/api/assignments.php', {
                method: 'POST',
                body: JSON.stringify(payload)
            });

        if (result.success) {

            showToast(
                result.message ||
                'Subject assigned successfully.',
                'success'
            );

            setTimeout(() => {
                window.location.reload();
            }, 700);

        } else {

            alertBox.textContent =
                result.message ||
                'Unable to assign subject.';

            alertBox.classList.add('show');

            saveButton.disabled = false;

            saveButton.textContent =
                'Assign Subject';
        }

    } catch (error) {

        alertBox.textContent =
            'Unable to assign subject.';

        alertBox.classList.add('show');

        saveButton.disabled = false;

        saveButton.textContent =
            'Assign Subject';
    }
});

editAssignmentForm.addEventListener('submit', async (event) => {

    event.preventDefault();

    const alertBox =
        document.getElementById('editAssignmentAlert');

    alertBox.classList.remove('show');

    const payload = {

        assignment_id:
            Number(
                document.getElementById('edit_assignment_id').value
            ),

        teacher_id:
            Number(
                document.getElementById('edit_teacher_id').value
            ),

        subject_id:
            Number(
                document.getElementById('edit_subject_id').value
            ),

        section_id:
            Number(
                document.getElementById('edit_section_id').value
            ),

        term_id:
            Number(
                document.getElementById('edit_term_id').value
            )
    };

    const saveButton =
        document.getElementById('editAssignmentSaveBtn');

    saveButton.disabled = true;

    saveButton.innerHTML =
        '<span class="spinner"></span> Saving...';

    try {

        const response =
            await fetch(
                '../api/update_assignment.php',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                }
            );

        const result =
            await response.json();

        if (result.success) {

            showToast(
                result.message ||
                'Subject assignment updated successfully.',
                'success'
            );

            setTimeout(() => {
                window.location.reload();
            }, 700);

        } else {

            alertBox.textContent =
                result.message ||
                'Unable to update assignment.';

            alertBox.classList.add('show');

            saveButton.disabled = false;

            saveButton.textContent =
                'Save Changes';
        }

    } catch (error) {

        alertBox.textContent =
            'Unable to update subject assignment.';

        alertBox.classList.add('show');

        saveButton.disabled = false;

        saveButton.textContent =
            'Save Changes';
    }
});
JS;

include __DIR__ . '/../includes/footer.php';
?>