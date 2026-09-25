<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('student');

$pdo = getDbConnection();
$studentId = $_SESSION['student_id'];

$stmt = $pdo->query('
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
');

$terms = $stmt->fetchAll();

$stmt = $pdo->prepare('
    SELECT
        s.subject_code,
        s.subject_name,
        s.units,
        g.grade,
        g.remarks,
        t.term_id,
        t.school_year,
        t.semester,
        t.grading_period,
        tc.first_name AS teacher_first,
        tc.last_name AS teacher_last
    FROM grades g
    JOIN subjects s
        ON s.subject_id = g.subject_id
    JOIN academic_terms t
        ON t.term_id = g.term_id
    JOIN teachers tc
        ON tc.teacher_id = g.teacher_id
    WHERE g.student_id = :sid
    ORDER BY
        t.school_year DESC,
        CASE t.semester
            WHEN \'1st Semester\' THEN 1
            WHEN \'2nd Semester\' THEN 2
            WHEN \'Summer\' THEN 3
            ELSE 4
        END,
        CASE t.grading_period
            WHEN \'Prelim\' THEN 1
            WHEN \'Midterm\' THEN 2
            WHEN \'Semi-Final\' THEN 3
            WHEN \'Final\' THEN 4
            ELSE 5
        END,
        s.subject_code ASC
');

$stmt->execute([
    'sid' => $studentId
]);

$grades = $stmt->fetchAll();

$pageTitle = 'My Grades';
$pageSubtitle = 'All grades recorded under your Student ID';
$activeNav = 'grades';

include __DIR__ . '/../includes/header.php';
?>

<div class="glass-card panel">

    <div class="panel-header">

        <div>
            <h2 class="panel-title">
                Grade Records
            </h2>

            <p class="panel-subtitle">
                View and review all your recorded grades.
            </p>
        </div>

        <div class="toolbar">

            <div class="search-input-wrap">

                <input
                    type="text"
                    id="searchInput"
                    class="form-control search-input"
                    placeholder="Search subject..."
                    autocomplete="off"
                >

            </div>

            <select
                id="termFilter"
                class="form-control"
            >

                <option value="all">
                    All Academic Terms
                </option>

                <?php
                $displayedTerms = [];

                foreach ($terms as $t):

                    $termKey =
                        $t['school_year']
                        . ' - '
                        . $t['semester'];

                    if (isset($displayedTerms[$termKey])) {
                        continue;
                    }

                    $displayedTerms[$termKey] = true;
                ?>

                    <option
                        value="<?= clean(
                            $t['school_year']
                            . '|'
                            . $t['semester']
                        ) ?>"
                    >
                        <?= clean($termKey) ?>
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
                No Grade Records Yet
            </h3>

            <p>
                Your grades will appear here once they have been recorded.
            </p>

        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table
                class="data-table student-grades-table"
                id="gradesTable"
            >

                <thead>

                    <tr>
                        <th>Subject</th>
                        <th>Subject Name</th>
                        <th>Units</th>
                        <th>Teacher</th>
                        <th>Academic Term</th>
                        <th>Grading Period</th>
                        <th>Grade</th>
                        <th>Remarks</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($grades as $g): ?>

                        <tr
                            data-term="<?= clean(
                                $g['school_year']
                                . '|'
                                . $g['semester']
                            ) ?>"
                            data-grading-period="<?= clean(
                                $g['grading_period']
                            ) ?>"
                            data-subject="<?= clean(
                                strtolower(
                                    $g['subject_code']
                                    . ' '
                                    . $g['subject_name']
                                )
                            ) ?>"
                        >

                            <td>

                                <strong class="subject-code">
                                    <?= clean($g['subject_code']) ?>
                                </strong>

                            </td>

                            <td class="subject-name">
                                <?= clean($g['subject_name']) ?>
                            </td>

                            <td class="units-value">

                                <?= rtrim(
                                    rtrim(
                                        number_format(
                                            (float) $g['units'],
                                            1
                                        ),
                                        '0'
                                    ),
                                    '.'
                                ) ?>

                            </td>

                            <td>

                                <?= clean(
                                    $g['teacher_first']
                                    . ' '
                                    . $g['teacher_last']
                                ) ?>

                            </td>

                            <td class="term-text">

                                <?= clean(
                                    $g['school_year']
                                    . ' - '
                                    . $g['semester']
                                ) ?>

                            </td>

                            <td>

                                <span class="badge badge-info">
                                    <?= clean(
                                        $g['grading_period']
                                    ) ?>
                                </span>

                            </td>

                            <td class="grade-value">

                                <?= $g['grade'] !== null
                                    ? number_format(
                                        (float) $g['grade'],
                                        2
                                    )
                                    : '&mdash;' ?>

                            </td>

                            <td class="remarks-text">

                                <?= $g['remarks']
                                    ? clean($g['remarks'])
                                    : '&mdash;' ?>

                            </td>

                            <td>

                                <span
                                    class="badge <?= gradeStatusClass(
                                        $g['grade']
                                    ) ?>"
                                >
                                    <?= gradeStatus($g['grade']) ?>
                                </span>

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

            <h3>
                No Matching Grades
            </h3>

            <p>
                No grade records match your current search or filters.
            </p>

        </div>

    <?php endif; ?>

</div>

<?php

$extraScript = <<<'JS'

const searchInput =
    document.getElementById('searchInput');

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

function applyFilters() {

    const search =
        searchInput
            ? searchInput.value.trim().toLowerCase()
            : '';

    const term =
        termFilter
            ? termFilter.value
            : 'all';

    const gradingPeriod =
        gradingPeriodFilter
            ? gradingPeriodFilter.value
            : 'all';

    let visibleCount = 0;

    rows.forEach((row) => {

        const matchesSearch =
            row.dataset.subject.includes(search);

        const matchesTerm =
            term === 'all' ||
            row.dataset.term === term;

        const matchesGradingPeriod =
            gradingPeriod === 'all' ||
            row.dataset.gradingPeriod === gradingPeriod;

        const show =
            matchesSearch &&
            matchesTerm &&
            matchesGradingPeriod;

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

searchInput?.addEventListener(
    'input',
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

JS;

include __DIR__ . '/../includes/footer.php';

?>