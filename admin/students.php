<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

$pdo = getDbConnection();

$students = $pdo->query('
    SELECT
        st.student_id,
        st.first_name,
        st.last_name,
        st.email,
        st.course,
        st.year_level,
        st.section_id,
        u.user_id,
        u.login_id,
        u.is_active
    FROM students st
    JOIN users u ON u.user_id = st.user_id
    ORDER BY st.last_name, st.first_name
')->fetchAll();

$pageTitle = 'Students';
$pageSubtitle = 'Manage registered student records and accounts';
$activeNav = 'students';

include __DIR__ . '/../includes/header.php';
?>

<div class="glass-card panel">

    <div class="panel-header">

        <div>
            <h2 class="panel-title">
                All Students
            </h2>

            <p class="panel-subtitle">
                View and manage registered student accounts
            </p>
        </div>

        <div class="toolbar">

            <div class="search-input-wrap">

                <input
                    type="text"
                    id="searchInput"
                    class="form-control search-input"
                    placeholder="Search students..."
                >

            </div>

        </div>

    </div>

    <?php if (empty($students)): ?>

        <div class="empty-state">

            <h3>No Registered Students</h3>

            <p>
                Students can create their own accounts through the registration page.
            </p>

        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table
                class="data-table"
                id="studentsTable"
            >

                <thead>

                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($students as $student): ?>

                        <tr
                            data-search="<?= clean(strtolower(
                                $student['login_id'] . ' ' .
                                $student['first_name'] . ' ' .
                                $student['last_name'] . ' ' .
                                $student['email'] . ' ' .
                                $student['course']
                            )) ?>"
                        >

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

                            <td class="cell-muted">
                                <?= clean($student['email']) ?>
                            </td>

                            <td>
                                <?= clean($student['course']) ?>
                            </td>

                            <td>
                                Year <?= (int) $student['year_level'] ?>
                            </td>

                            <td>

                                <span
                                    class="badge <?= $student['is_active']
                                        ? 'badge-active'
                                        : 'badge-inactive' ?>"
                                >
                                    <?= $student['is_active']
                                        ? 'Active'
                                        : 'Inactive' ?>
                                </span>

                            </td>

                            <td class="row-actions">

                                <button
                                    type="button"
                                    class="btn btn-secondary btn-sm"
                                    onclick='openEditModal(<?= json_encode($student) ?>)'
                                >
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm <?= $student['is_active']
                                        ? 'btn-danger'
                                        : 'btn-primary' ?>"
                                    onclick="toggleStatus(
                                        <?= (int) $student['user_id'] ?>,
                                        <?= $student['is_active']
                                            ? 'false'
                                            : 'true' ?>
                                    )"
                                >
                                    <?= $student['is_active']
                                        ? 'Deactivate'
                                        : 'Activate' ?>
                                </button>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <div
            class="empty-state"
            id="noResultsState"
            style="display: none;"
        >
            <p>No students match your search.</p>
        </div>

    <?php endif; ?>

</div>

<div
    class="modal-overlay"
    id="studentModal"
>

    <div class="glass-card modal-box">

        <div class="modal-header">

            <div>

                <h3 class="modal-title">
                    Edit Student
                </h3>

                <p class="modal-subtitle">
                    Update the student's information and account.
                </p>

            </div>

            <button
                type="button"
                class="modal-close"
                onclick="closeModal('studentModal')"
            >
                Close
            </button>

        </div>

        <div
            id="studentModalAlert"
            class="alert alert-error"
        ></div>

        <form id="studentForm">

            <input
                type="hidden"
                id="student_id"
                value=""
            >

            <input
                type="hidden"
                id="user_id"
                value=""
            >

            <div class="form-row">

                <div class="form-group">

                    <label for="first_name">
                        First Name
                    </label>

                    <input
                        type="text"
                        id="first_name"
                        class="form-control"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="last_name">
                        Last Name
                    </label>

                    <input
                        type="text"
                        id="last_name"
                        class="form-control"
                        required
                    >

                </div>

            </div>

            <div class="form-group">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    class="form-control"
                    required
                >

            </div>

            <div class="form-row">

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
                            Select Course
                        </option>

                        <option value="Bachelor of Science in Criminology">
                            Bachelor of Science in Criminology
                        </option>

                        <option value="Bachelor of Science in Information Technology">
                            Bachelor of Science in Information Technology
                        </option>

                        <option value="Bachelor of Elementary Education">
                            Bachelor of Elementary Education
                        </option>

                        <option value="Bachelor of Science in Office Administration">
                            Bachelor of Science in Office Administration
                        </option>

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
                    >

                        <option value="1">
                            Year 1
                        </option>

                        <option value="2">
                            Year 2
                        </option>

                        <option value="3">
                            Year 3
                        </option>

                        <option value="4">
                            Year 4
                        </option>

                    </select>

                </div>

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
                        Select Course and Year Level first
                    </option>

                </select>

            </div>

            <div class="form-group">

                <label for="login_id">
                    Student ID
                </label>

                <input
                    type="text"
                    id="login_id"
                    class="form-control"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">
                    New Password
                </label>

                <input
                    type="password"
                    id="password"
                    class="form-control"
                    placeholder="Leave blank to keep the current password"
                >

                <div class="form-hint">
                    Only enter a password if you want to change it.
                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="closeModal('studentModal')"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                    id="studentSaveBtn"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>

<?php

$extraScript = <<<'JS'

const searchInput =
    document.getElementById('searchInput');

const rows =
    document.querySelectorAll('#studentsTable tbody tr');

const noResultsState =
    document.getElementById('noResultsState');

if (searchInput) {

    searchInput.addEventListener('input', () => {

        const search =
            searchInput.value.trim().toLowerCase();

        let visible = 0;

        rows.forEach((row) => {

            const show =
                row.dataset.search.includes(search);

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

    });

}


async function loadSections(course, yearLevel, selectedSectionId = '') {

    const sectionSelect = document.getElementById('section_id');

    sectionSelect.innerHTML = '<option value="">Loading sections...</option>';
    sectionSelect.disabled = true;

    if (!course || !yearLevel) {
        sectionSelect.innerHTML = '<option value="">Select Course and Year Level first</option>';
        return;
    }

    try {

        const response = await fetch(
            `/api/sections.php?course=${encodeURIComponent(course)}&year_level=${encodeURIComponent(yearLevel)}`
        );

        const result = await response.json();

        if (!result.success) {
            sectionSelect.innerHTML = '<option value="">Unable to load sections</option>';
            return;
        }

        sectionSelect.innerHTML = '<option value="">Select Section</option>';

        result.sections.forEach((section) => {
            const option = document.createElement('option');
            option.value = section.section_id;
            option.textContent = section.section_name;

            if (String(section.section_id) === String(selectedSectionId)) {
                option.selected = true;
            }

            sectionSelect.appendChild(option);
        });

        sectionSelect.disabled = result.sections.length === 0;

        if (result.sections.length === 0) {
            sectionSelect.innerHTML = '<option value="">No sections available</option>';
        }

    } catch (error) {
        sectionSelect.innerHTML = '<option value="">Unable to load sections</option>';
    }
}

document.getElementById('course').addEventListener('change', () => {
    loadSections(
        document.getElementById('course').value,
        document.getElementById('year_level').value
    );
});

document.getElementById('year_level').addEventListener('change', () => {
    loadSections(
        document.getElementById('course').value,
        document.getElementById('year_level').value
    );
});

function openEditModal(student) {

    document
        .getElementById('studentForm')
        .reset();

    document.getElementById('student_id').value =
        student.student_id;

    document.getElementById('user_id').value =
        student.user_id;

    document.getElementById('first_name').value =
        student.first_name;

    document.getElementById('last_name').value =
        student.last_name;

    document.getElementById('email').value =
        student.email;

    document.getElementById('course').value =
        student.course;

    document.getElementById('year_level').value =
        student.year_level;

    loadSections(student.course, student.year_level, student.section_id);

    document.getElementById('login_id').value =
        student.login_id;

    document.getElementById('password').value = '';

    document
        .getElementById('studentModalAlert')
        .classList.remove('show');

    openModal('studentModal');
}

function showStudentAlert(message) {

    const alertBox =
        document.getElementById('studentModalAlert');

    alertBox.textContent = message;
    alertBox.classList.add('show');
}

document
    .getElementById('studentForm')
    .addEventListener('submit', async (e) => {

        e.preventDefault();

        document
            .getElementById('studentModalAlert')
            .classList.remove('show');

        const payload = {

            student_id:
                document.getElementById('student_id').value,

            user_id:
                document.getElementById('user_id').value,

            first_name:
                document.getElementById('first_name')
                    .value.trim(),

            last_name:
                document.getElementById('last_name')
                    .value.trim(),

            email:
                document.getElementById('email')
                    .value.trim(),

            course:
                document.getElementById('course')
                    .value,

            year_level:
                document.getElementById('year_level')
                    .value,

            section_id:
                document.getElementById('section_id')
                    .value,

            login_id:
                document.getElementById('login_id')
                    .value.trim(),

            password:
                document.getElementById('password')
                    .value

        };

        const saveBtn =
            document.getElementById('studentSaveBtn');

        saveBtn.disabled = true;

        saveBtn.innerHTML =
            '<span class="spinner"></span> Saving...';

        const result = await apiFetch(
            '/api/students.php',
            {
                method: 'POST',
                body: JSON.stringify(payload)
            }
        );

        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Changes';

        if (result.success) {

            showToast(
                result.message || 'Student updated.',
                'success'
            );

            setTimeout(() => {
                window.location.reload();
            }, 700);

        } else {

            showStudentAlert(
                result.message ||
                'Unable to update student.'
            );

        }

    });

async function toggleStatus(userId, makeActive) {

    const ok = await confirmAction(
        makeActive
            ? 'Activate this student account?'
            : 'Deactivate this student account?',
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

JS;

include __DIR__ . '/../includes/footer.php';
?>