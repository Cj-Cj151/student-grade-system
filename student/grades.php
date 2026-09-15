<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('student');

$pdo = getDbConnection();
$studentId = $_SESSION['student_id'];

// All terms the student has at least one grade in, for the filter dropdown.
$stmt = $pdo->prepare('
    SELECT DISTINCT t.term_id, t.school_year, t.semester
    FROM grades g
    JOIN academic_terms t ON t.term_id = g.term_id
    WHERE g.student_id = :sid
    ORDER BY t.school_year DESC, t.semester DESC
');
$stmt->execute(['sid' => $studentId]);
$terms = $stmt->fetchAll();

// All grades for this student. Filtering by term/search happens client-side
// via JavaScript over this same table, which keeps the page snappy.
$stmt = $pdo->prepare('
    SELECT s.subject_code, s.subject_name, s.units, g.grade, g.remarks,
           t.term_id, t.school_year, t.semester,
           tc.first_name AS teacher_first, tc.last_name AS teacher_last
    FROM grades g
    JOIN subjects s ON s.subject_id = g.subject_id
    JOIN academic_terms t ON t.term_id = g.term_id
    JOIN teachers tc ON tc.teacher_id = g.teacher_id
    WHERE g.student_id = :sid
    ORDER BY t.school_year DESC, t.semester DESC, s.subject_code ASC
');
$stmt->execute(['sid' => $studentId]);
$grades = $stmt->fetchAll();

$pageTitle = 'My Grades';
$pageSubtitle = 'All grades recorded under your Student ID';
$activeNav = 'grades';
include __DIR__ . '/../includes/header.php';
?>

<div class="glass-card panel">
    <div class="panel-header">
        <h2 class="panel-title">Grade Records</h2>
        <div class="toolbar">
            <div class="search-input-wrap">
                <span class="search-icon">&#128269;</span>
                <input type="text" id="searchInput" class="form-control search-input" placeholder="Search subject...">
            </div>
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
            <p>No grades have been recorded for your account yet.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table" id="gradesTable">
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Units</th>
                        <th>Teacher</th>
                        <th>Term</th>
                        <th>Grade</th>
                        <th>Remarks</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grades as $g): ?>
                        <tr data-term="<?= (int) $g['term_id'] ?>" data-subject="<?= clean(strtolower($g['subject_code'] . ' ' . $g['subject_name'])) ?>">
                            <td><strong><?= clean($g['subject_code']) ?></strong></td>
                            <td><?= clean($g['subject_name']) ?></td>
                            <td><?= rtrim(rtrim(number_format((float) $g['units'], 1), '0'), '.') ?></td>
                            <td><?= clean($g['teacher_first'] . ' ' . $g['teacher_last']) ?></td>
                            <td class="cell-muted"><?= clean($g['school_year'] . ' - ' . $g['semester']) ?></td>
                            <td><?= $g['grade'] !== null ? number_format((float) $g['grade'], 2) : '&mdash;' ?></td>
                            <td class="cell-muted"><?= $g['remarks'] ? clean($g['remarks']) : '&mdash;' ?></td>
                            <td><span class="badge <?= gradeStatusClass($g['grade']) ?>"><?= gradeStatus($g['grade']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="empty-state" id="noResultsState" style="display:none;">
            <span class="empty-icon">&#128269;</span><br>
            No grades match your search or filter.
        </p>
    <?php endif; ?>
</div>

<?php
$extraScript = <<<'JS'
const searchInput = document.getElementById('searchInput');
const termFilter = document.getElementById('termFilter');
const rows = document.querySelectorAll('#gradesTable tbody tr');
const noResultsState = document.getElementById('noResultsState');

function applyFilters() {
    const search = searchInput.value.trim().toLowerCase();
    const term = termFilter.value;
    let visibleCount = 0;

    rows.forEach((row) => {
        const matchesSearch = row.dataset.subject.includes(search);
        const matchesTerm = (term === 'all') || (row.dataset.term === term);
        const show = matchesSearch && matchesTerm;
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });

    if (noResultsState) {
        noResultsState.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

if (searchInput) searchInput.addEventListener('input', applyFilters);
if (termFilter) termFilter.addEventListener('change', applyFilters);
JS;
include __DIR__ . '/../includes/footer.php';
?>
