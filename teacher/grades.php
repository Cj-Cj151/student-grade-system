<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('teacher');

$pdo = getDbConnection();
$teacherId = $_SESSION['teacher_id'];

// Subjects this teacher handles (based on existing grade records).
$stmt = $pdo->prepare('
    SELECT DISTINCT s.subject_id, s.subject_code, s.subject_name
    FROM grades g JOIN subjects s ON s.subject_id = g.subject_id
    WHERE g.teacher_id = :tid
    ORDER BY s.subject_code
');
$stmt->execute(['tid' => $teacherId]);
$subjects = $stmt->fetchAll();

// Terms this teacher has records in.
$stmt = $pdo->prepare('
    SELECT DISTINCT t.term_id, t.school_year, t.semester
    FROM grades g JOIN academic_terms t ON t.term_id = g.term_id
    WHERE g.teacher_id = :tid
    ORDER BY t.school_year DESC, t.semester DESC
');
$stmt->execute(['tid' => $teacherId]);
$terms = $stmt->fetchAll();

// Selected subject/term (from query string, or default to the first available).
$selectedSubject = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : ($subjects[0]['subject_id'] ?? 0);
$selectedTerm = isset($_GET['term_id']) ? (int) $_GET['term_id'] : ($terms[0]['term_id'] ?? 0);

$students = [];
if ($selectedSubject && $selectedTerm) {
    $stmt = $pdo->prepare('
        SELECT g.grade_id, g.grade, g.remarks, st.student_id, st.first_name, st.last_name, u.login_id
        FROM grades g
        JOIN students st ON st.student_id = g.student_id
        JOIN users u ON u.user_id = st.user_id
        WHERE g.teacher_id = :tid AND g.subject_id = :subid AND g.term_id = :termid
        ORDER BY st.last_name, st.first_name
    ');
    $stmt->execute(['tid' => $teacherId, 'subid' => $selectedSubject, 'termid' => $selectedTerm]);
    $students = $stmt->fetchAll();
}

$pageTitle = 'Grade Management';
$pageSubtitle = 'Encode and update grades for your students';
$activeNav = 'grades';
include __DIR__ . '/../includes/header.php';
?>

<div class="glass-card panel">
    <div class="panel-header">
        <h2 class="panel-title">Select Subject &amp; Term</h2>
    </div>

    <form method="GET" id="filterForm" class="toolbar">
        <select name="subject_id" id="subjectSelect" class="form-control" onchange="document.getElementById('filterForm').submit()">
            <option value="">-- Select Subject --</option>
            <?php foreach ($subjects as $s): ?>
                <option value="<?= (int) $s['subject_id'] ?>" <?= $selectedSubject == $s['subject_id'] ? 'selected' : '' ?>>
                    <?= clean($s['subject_code'] . ' - ' . $s['subject_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="term_id" id="termSelect" class="form-control" onchange="document.getElementById('filterForm').submit()">
            <option value="">-- Select Term --</option>
            <?php foreach ($terms as $t): ?>
                <option value="<?= (int) $t['term_id'] ?>" <?= $selectedTerm == $t['term_id'] ? 'selected' : '' ?>>
                    <?= clean($t['school_year'] . ' - ' . $t['semester']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="glass-card panel">
    <div class="panel-header">
        <h2 class="panel-title">Students</h2>
        <div class="toolbar">
            <div class="search-input-wrap">
                <span class="search-icon">&#128269;</span>
                <input type="text" id="searchInput" class="form-control search-input" placeholder="Search student...">
            </div>
        </div>
    </div>

    <?php if (empty($subjects) || empty($terms)): ?>
        <div class="empty-state">
            <div class="empty-icon">&#128218;</div>
            <p>You have no subjects or terms assigned yet.</p>
        </div>
    <?php elseif (empty($students)): ?>
        <div class="empty-state">
            <div class="empty-icon">&#128100;</div>
            <p>No students found for this subject and term.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table" id="studentsTable">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th style="width:110px;">Grade</th>
                        <th>Remarks</th>
                        <th>Status</th>
                        <th style="width:90px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr data-name="<?= clean(strtolower($s['first_name'] . ' ' . $s['last_name'] . ' ' . $s['login_id'])) ?>" data-grade-id="<?= (int) $s['grade_id'] ?>">
                            <td><?= clean($s['login_id']) ?></td>
                            <td><?= clean($s['first_name'] . ' ' . $s['last_name']) ?></td>
                            <td>
                                <input type="number" class="form-control grade-input" min="0" max="100" step="0.01"
                                       value="<?= $s['grade'] !== null ? htmlspecialchars((string) $s['grade']) : '' ?>" placeholder="--">
                            </td>
                            <td>
                                <input type="text" class="form-control remarks-input" maxlength="255"
                                       value="<?= clean($s['remarks'] ?? '') ?>" placeholder="Optional remarks">
                            </td>
                            <td><span class="badge status-badge <?= gradeStatusClass($s['grade']) ?>"><?= gradeStatus($s['grade']) ?></span></td>
                            <td><button type="button" class="btn btn-primary btn-sm save-grade-btn">Save</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="empty-state" id="noResultsState" style="display:none;">
            <span class="empty-icon">&#128269;</span><br>
            No students match your search.
        </p>
    <?php endif; ?>
</div>

<?php
$extraScript = <<<'JS'
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
            if (show) visibleCount++;
        });
        if (noResultsState) noResultsState.style.display = visibleCount === 0 ? 'block' : 'none';
    });
}

document.querySelectorAll('.save-grade-btn').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const row = btn.closest('tr');
        const gradeId = row.dataset.gradeId;
        const gradeInput = row.querySelector('.grade-input');
        const remarksInput = row.querySelector('.remarks-input');
        const statusBadge = row.querySelector('.status-badge');

        const gradeValue = gradeInput.value.trim();

        // Client-side validation (server re-validates everything again).
        if (gradeValue !== '' && (isNaN(gradeValue) || Number(gradeValue) < 0 || Number(gradeValue) > 100)) {
            showToast('Grade must be a number between 0 and 100.', 'error');
            gradeInput.focus();
            return;
        }

        btn.disabled = true;
        const originalText = btn.textContent;
        btn.innerHTML = '<span class="spinner"></span>';

        const result = await apiFetch('/api/grades.php', {
            method: 'POST',
            body: JSON.stringify({
                grade_id: gradeId,
                grade: gradeValue === '' ? null : Number(gradeValue),
                remarks: remarksInput.value.trim(),
            }),
        });

        btn.disabled = false;
        btn.textContent = originalText;

        if (result.success) {
            showToast(result.message || 'Grade saved successfully.', 'success');
            if (statusBadge && result.data && result.data.status) {
                statusBadge.textContent = result.data.status;
                statusBadge.className = 'badge status-badge ' + result.data.status_class;
            }
        } else {
            showToast(result.message || 'Unable to save grade.', 'error');
        }
    });
});
JS;
include __DIR__ . '/../includes/footer.php';
?>
