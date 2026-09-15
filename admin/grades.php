<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

$pdo = getDbConnection();

$subjects = $pdo->query('SELECT subject_id, subject_code, subject_name FROM subjects ORDER BY subject_code')->fetchAll();
$terms = $pdo->query('SELECT term_id, school_year, semester FROM academic_terms ORDER BY school_year DESC, semester DESC')->fetchAll();

$grades = $pdo->query('
    SELECT g.grade_id, g.grade, g.remarks, g.updated_at,
           st.first_name AS student_first, st.last_name AS student_last, su.login_id AS student_login,
           tc.first_name AS teacher_first, tc.last_name AS teacher_last,
           sub.subject_id, sub.subject_code, sub.subject_name,
           t.term_id, t.school_year, t.semester
    FROM grades g
    JOIN students st ON st.student_id = g.student_id
    JOIN users su ON su.user_id = st.user_id
    JOIN teachers tc ON tc.teacher_id = g.teacher_id
    JOIN subjects sub ON sub.subject_id = g.subject_id
    JOIN academic_terms t ON t.term_id = g.term_id
    ORDER BY g.updated_at DESC
')->fetchAll();

$pageTitle = 'Grade Records';
$pageSubtitle = 'View, search, and edit all grade records';
$activeNav = 'grades';
include __DIR__ . '/../includes/header.php';
?>

<div class="glass-card panel">
    <div class="panel-header">
        <h2 class="panel-title">All Grade Records</h2>
        <div class="toolbar">
            <div class="search-input-wrap">
                <span class="search-icon">&#128269;</span>
                <input type="text" id="searchInput" class="form-control search-input" placeholder="Search student or subject...">
            </div>
            <select id="subjectFilter" class="form-control">
                <option value="all">All Subjects</option>
                <?php foreach ($subjects as $s): ?>
                    <option value="<?= (int) $s['subject_id'] ?>"><?= clean($s['subject_code']) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="termFilter" class="form-control">
                <option value="all">All Terms</option>
                <?php foreach ($terms as $t): ?>
                    <option value="<?= (int) $t['term_id'] ?>"><?= clean($t['school_year'] . ' - ' . $t['semester']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if (empty($grades)): ?>
        <div class="empty-state">
            <div class="empty-icon">&#128220;</div>
            <p>No grade records exist yet.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table" id="gradesTable">
                <thead>
                    <tr><th>Student</th><th>Subject</th><th>Teacher</th><th>Term</th><th>Grade</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($grades as $g): ?>
                        <tr data-subject="<?= (int) $g['subject_id'] ?>" data-term="<?= (int) $g['term_id'] ?>"
                            data-search="<?= clean(strtolower($g['student_first'] . ' ' . $g['student_last'] . ' ' . $g['student_login'] . ' ' . $g['subject_code'] . ' ' . $g['subject_name'])) ?>">
                            <td><?= clean($g['student_first'] . ' ' . $g['student_last']) ?><br><span class="cell-muted"><?= clean($g['student_login']) ?></span></td>
                            <td><?= clean($g['subject_code']) ?></td>
                            <td><?= clean($g['teacher_first'] . ' ' . $g['teacher_last']) ?></td>
                            <td class="cell-muted"><?= clean($g['school_year'] . ' - ' . $g['semester']) ?></td>
                            <td><?= $g['grade'] !== null ? number_format((float) $g['grade'], 2) : '&mdash;' ?></td>
                            <td><span class="badge <?= gradeStatusClass($g['grade']) ?>"><?= gradeStatus($g['grade']) ?></span></td>
                            <td>
                                <button type="button" class="btn btn-secondary btn-sm" onclick='openEditModal(<?= json_encode([
                                    "grade_id" => $g['grade_id'],
                                    "grade" => $g['grade'],
                                    "remarks" => $g['remarks'],
                                    "label" => $g['student_first'] . ' ' . $g['student_last'] . ' - ' . $g['subject_code'],
                                ]) ?>)'>Edit</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="empty-state" id="noResultsState" style="display:none;">No grade records match your search or filters.</p>
    <?php endif; ?>
</div>

<div class="modal-overlay" id="gradeModal">
    <div class="glass-card modal-box" style="max-width:420px;">
        <div class="modal-header">
            <h3 class="modal-title">Edit Grade</h3>
            <button type="button" class="modal-close" onclick="closeModal('gradeModal')">&times;</button>
        </div>
        <div id="gradeModalAlert" class="alert alert-error"></div>
        <form id="gradeForm">
            <input type="hidden" id="grade_id" value="">
            <div class="form-group">
                <label id="gradeModalLabel">Record</label>
            </div>
            <div class="form-group">
                <label for="grade_value">Grade (0-100, leave blank for pending)</label>
                <input type="number" id="grade_value" class="form-control" min="0" max="100" step="0.01">
            </div>
            <div class="form-group">
                <label for="remarks_value">Remarks</label>
                <input type="text" id="remarks_value" class="form-control" maxlength="255">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('gradeModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="gradeSaveBtn">Save Grade</button>
            </div>
        </form>
    </div>
</div>

<?php
$extraScript = <<<'JS'
const searchInput = document.getElementById('searchInput');
const subjectFilter = document.getElementById('subjectFilter');
const termFilter = document.getElementById('termFilter');
const rows = document.querySelectorAll('#gradesTable tbody tr');
const noResultsState = document.getElementById('noResultsState');

function applyFilters() {
    const search = searchInput.value.trim().toLowerCase();
    const subject = subjectFilter.value;
    const term = termFilter.value;
    let visible = 0;

    rows.forEach((row) => {
        const matchesSearch = row.dataset.search.includes(search);
        const matchesSubject = subject === 'all' || row.dataset.subject === subject;
        const matchesTerm = term === 'all' || row.dataset.term === term;
        const show = matchesSearch && matchesSubject && matchesTerm;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    if (noResultsState) noResultsState.style.display = visible === 0 ? 'block' : 'none';
}

if (searchInput) searchInput.addEventListener('input', applyFilters);
if (subjectFilter) subjectFilter.addEventListener('change', applyFilters);
if (termFilter) termFilter.addEventListener('change', applyFilters);

function openEditModal(record) {
    document.getElementById('gradeForm').reset();
    document.getElementById('grade_id').value = record.grade_id;
    document.getElementById('gradeModalLabel').textContent = record.label;
    document.getElementById('grade_value').value = record.grade !== null ? record.grade : '';
    document.getElementById('remarks_value').value = record.remarks || '';
    document.getElementById('gradeModalAlert').classList.remove('show');
    openModal('gradeModal');
}

document.getElementById('gradeForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const alertBox = document.getElementById('gradeModalAlert');
    alertBox.classList.remove('show');

    const gradeValue = document.getElementById('grade_value').value.trim();

    const payload = {
        grade_id: document.getElementById('grade_id').value,
        grade: gradeValue === '' ? null : Number(gradeValue),
        remarks: document.getElementById('remarks_value').value.trim(),
    };

    const saveBtn = document.getElementById('gradeSaveBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner"></span> Saving...';

    const result = await apiFetch('/api/admin_grades.php', {
        method: 'POST',
        body: JSON.stringify(payload),
    });

    saveBtn.disabled = false;
    saveBtn.textContent = 'Save Grade';

    if (result.success) {
        showToast(result.message || 'Grade updated.', 'success');
        setTimeout(() => window.location.reload(), 700);
    } else {
        alertBox.textContent = result.message || 'Unable to save grade.';
        alertBox.classList.add('show');
    }
});
JS;
include __DIR__ . '/../includes/footer.php';
?>
