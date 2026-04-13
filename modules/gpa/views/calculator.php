<?php
$activeBatch = (array) ($active_batch ?? []);
$terms = (array) ($terms ?? []);
$subjects = (array) ($subjects ?? []);
$selectedYear = (int) ($selected_year ?? 0);
$selectedSemester = (int) ($selected_semester ?? 0);
$gradeScaleRows = (array) ($grade_scale_rows ?? []);
$selectedGrades = (array) ($selected_grades ?? []);
$preview = (array) ($preview ?? []);
$summary = (array) ($summary ?? []);
$record = (array) ($record ?? []);

$recordCount = (int) ($summary['record_count'] ?? 0);
$cumulativeAverage = $summary['average_gpa'] !== null ? (float) $summary['average_gpa'] : null;
$bestGpa = $summary['best_gpa'] !== null ? (float) $summary['best_gpa'] : null;

$termGpa = (!empty($preview['errors']) || (int) ($preview['graded_subject_count'] ?? 0) <= 0)
    ? null
    : (float) ($preview['semester_gpa'] ?? 0);
$totalCredits = (float) ($preview['total_credits'] ?? 0);
$gradedSubjectCount = (int) ($preview['graded_subject_count'] ?? 0);

$gradeScaleJsonRows = [];
foreach ($gradeScaleRows as $row) {
    $gradeScaleJsonRows[] = [
        'letter_grade' => (string) ($row['letter_grade'] ?? ''),
        'grade_point' => (float) ($row['grade_point'] ?? 0),
    ];
}
$gradeScaleJson = json_encode(
    $gradeScaleJsonRows,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?: '[]';
?>

<div class="page-header gpa-page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb"><?= e((string) ($role_label ?? 'Student')) ?> / GPA</p>
        <h1>GPA Calculator</h1>
        <p class="page-subtitle">
            Calculate your semester GPA using your batch's official letter-grade scale.
            <?php if (!empty($activeBatch['batch_code'])): ?>
                Batch: <strong><?= e((string) $activeBatch['batch_code']) ?></strong>.
            <?php endif; ?>
        </p>
    </div>
    <div class="page-header-actions">
        <a href="/dashboard/gpa/analytics" class="btn btn-outline"><?= ui_lucide_icon('line-chart') ?> GPA Analytics</a>
        <a href="/dashboard/gpa/grade-scale" class="btn btn-outline"><?= ui_lucide_icon('list-checks') ?> Grade Scale</a>
    </div>
</div>

<?php if (empty($terms)): ?>
    <section class="card gpa-empty-state">
        <div class="card-body">
            <h3><?= ui_lucide_icon('circle-alert') ?> No subjects found</h3>
            <p class="text-muted">No subjects are configured for your batch yet. Ask your moderator to add subjects by year and semester.</p>
        </div>
    </section>
<?php else: ?>
    <section class="card gpa-term-filter-card">
        <div class="card-body">
            <form method="GET" action="/dashboard/gpa" class="gpa-term-filter-form">
                <div class="form-group">
                    <label for="gpa-year">Academic Year</label>
                    <select id="gpa-year" name="year" required>
                        <?php
                        $yearOptions = [];
                        foreach ($terms as $term) {
                            $year = (int) ($term['academic_year'] ?? 0);
                            if ($year <= 0 || in_array($year, $yearOptions, true)) {
                                continue;
                            }
                            $yearOptions[] = $year;
                        }
                        rsort($yearOptions);
                        ?>
                        <?php foreach ($yearOptions as $year): ?>
                            <option value="<?= $year ?>" <?= $selectedYear === $year ? 'selected' : '' ?>>Year <?= $year ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="gpa-semester">Semester</label>
                    <select id="gpa-semester" name="semester" required>
                        <?php
                        $semesterOptions = [];
                        foreach ($terms as $term) {
                            $year = (int) ($term['academic_year'] ?? 0);
                            $semester = (int) ($term['semester'] ?? 0);
                            if ($year !== $selectedYear || $semester <= 0 || in_array($semester, $semesterOptions, true)) {
                                continue;
                            }
                            $semesterOptions[] = $semester;
                        }
                        sort($semesterOptions);
                        ?>
                        <?php foreach ($semesterOptions as $semester): ?>
                            <option value="<?= $semester ?>" <?= $selectedSemester === $semester ? 'selected' : '' ?>>Semester <?= $semester ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="gpa-term-filter-actions">
                    <button type="submit" class="btn btn-primary">Load Term</button>
                </div>
            </form>
        </div>
    </section>

    <?php if (empty($gradeScaleRows)): ?>
        <article class="gpa-warning-banner" role="alert">
            <span><?= ui_lucide_icon('triangle-alert') ?></span>
            <div>
                <strong>Grade scale is not configured yet.</strong>
                <p class="text-muted">Your moderator or admin must add letter-grade points before GPA can be calculated.</p>
            </div>
        </article>
    <?php endif; ?>

    <section class="gpa-calculator-layout">
        <article class="card gpa-calculator-card">
            <div class="card-body">
                <h2>Subject Grades</h2>
                <p class="gpa-card-subtitle">Select your official letter grades for Year <?= $selectedYear ?>, Semester <?= $selectedSemester ?>.</p>

                <?php if (empty($subjects)): ?>
                    <p class="text-muted">No subjects available for this term.</p>
                <?php else: ?>
                    <form method="POST" action="/dashboard/gpa" id="gpa-calculator-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="academic_year" value="<?= $selectedYear ?>">
                        <input type="hidden" name="semester" value="<?= $selectedSemester ?>">

                        <div class="gpa-table-wrap">
                            <table class="gpa-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Course</th>
                                        <th>Credits</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjects as $index => $subject): ?>
                                        <?php
                                        $subjectId = (int) ($subject['id'] ?? 0);
                                        $credits = (float) ($subject['credits'] ?? 0);
                                        $selectedGrade = gpa_normalize_letter_grade((string) ($selectedGrades[$subjectId] ?? ''));
                                        ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <strong><?= e((string) ($subject['code'] ?? 'SUB')) ?></strong>
                                                <p><?= e((string) ($subject['name'] ?? 'Subject')) ?></p>
                                            </td>
                                            <td><?= e(number_format($credits, 1)) ?></td>
                                            <td>
                                                <select
                                                    id="gpa-grade-<?= $subjectId ?>"
                                                    name="grades[<?= $subjectId ?>]"
                                                    class="gpa-grade-select"
                                                    data-gpa-grade-select
                                                    data-credits="<?= e(number_format($credits, 2, '.', '')) ?>"
                                                    <?= empty($gradeScaleRows) ? 'disabled' : '' ?>
                                                >
                                                    <option value="">-</option>
                                                    <?php foreach ($gradeScaleRows as $scale): ?>
                                                        <?php $letter = gpa_normalize_letter_grade((string) ($scale['letter_grade'] ?? '')); ?>
                                                        <option
                                                            value="<?= e($letter) ?>"
                                                            data-grade-point="<?= e(number_format((float) ($scale['grade_point'] ?? 0), 2, '.', '')) ?>"
                                                            <?= $selectedGrade === $letter ? 'selected' : '' ?>
                                                        >
                                                            <?= e($letter) ?> (<?= e(number_format((float) ($scale['grade_point'] ?? 0), 2)) ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="gpa-form-actions">
                            <button type="submit" class="btn btn-primary" <?= empty($gradeScaleRows) ? 'disabled' : '' ?>>
                                <?= ui_lucide_icon('save') ?> Save GPA Record
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </article>

        <aside class="card gpa-summary-card">
            <div class="card-body">
                <h2>Current Term Snapshot</h2>
                <ul class="gpa-summary-list">
                    <li>
                        <span>Term GPA</span>
                        <strong id="gpa-term-value"><?= $termGpa !== null ? e(number_format($termGpa, 2)) : '0.00' ?></strong>
                    </li>
                    <li>
                        <span>Graded Subjects</span>
                        <strong id="gpa-graded-count"><?= $gradedSubjectCount ?></strong>
                    </li>
                    <li>
                        <span>Total Credits</span>
                        <strong id="gpa-credit-total"><?= e(number_format($totalCredits, 2)) ?></strong>
                    </li>
                </ul>

                <div class="gpa-term-chart-wrap">
                    <canvas id="gpa-term-donut" aria-label="Current term GPA chart"></canvas>
                </div>

                <h3>Overall Progress</h3>
                <ul class="gpa-summary-list">
                    <li>
                        <span>Saved Terms</span>
                        <strong><?= $recordCount ?></strong>
                    </li>
                    <li>
                        <span>Cumulative Avg</span>
                        <strong><?= $cumulativeAverage !== null ? e(number_format($cumulativeAverage, 2)) : '0.00' ?></strong>
                    </li>
                    <li>
                        <span>Best GPA</span>
                        <strong><?= $bestGpa !== null ? e(number_format($bestGpa, 2)) : '0.00' ?></strong>
                    </li>
                </ul>

                <?php if (!empty($record)): ?>
                    <p class="gpa-saved-note text-muted">Last saved for this term: <?= e((string) ($record['updated_at'] ?? $record['created_at'] ?? '')) ?></p>
                <?php endif; ?>
            </div>
        </aside>
    </section>

    <section class="card gpa-guide-card">
        <div class="card-body">
            <h2>How to Use the GPA Calculator</h2>
            <ul class="gpa-guide-list">
                <li><?= ui_lucide_icon('circle-check') ?> Choose year and semester, then select your letter grade for each subject.</li>
                <li><?= ui_lucide_icon('circle-check') ?> GPA updates instantly as you change grades in the table.</li>
                <li><?= ui_lucide_icon('circle-check') ?> Save to keep one record per term and track trend analytics.</li>
            </ul>

            <div class="gpa-scale-table-wrap">
                <table class="gpa-scale-table">
                    <thead>
                        <tr>
                            <th>Letter Grade</th>
                            <th>Description</th>
                            <th>Grade Point</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($gradeScaleRows)): ?>
                            <tr>
                                <td colspan="3" class="text-muted">No grade scale rows configured yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($gradeScaleRows as $scale): ?>
                                <tr>
                                    <td><span class="gpa-grade-pill"><?= e((string) ($scale['letter_grade'] ?? '')) ?></span></td>
                                    <td><?= e((string) ($scale['description'] ?? '-')) ?></td>
                                    <td><?= e(number_format((float) ($scale['grade_point'] ?? 0), 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?php endif; ?>

<script id="gpa-grade-scale-data" type="application/json"><?= $gradeScaleJson ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>
<script>
(function () {
    const form = document.getElementById('gpa-calculator-form');
    const termValue = document.getElementById('gpa-term-value');
    const gradedCount = document.getElementById('gpa-graded-count');
    const creditTotal = document.getElementById('gpa-credit-total');
    const canvas = document.getElementById('gpa-term-donut');

    if (!form || !termValue || !gradedCount || !creditTotal || !canvas) {
        return;
    }

    let donutChart = null;

    function readNumber(value) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function calculateSnapshot() {
        const selects = form.querySelectorAll('[data-gpa-grade-select]');
        let totalCredits = 0;
        let totalQualityPoints = 0;
        let totalGraded = 0;

        selects.forEach((select) => {
            if (!(select instanceof HTMLSelectElement)) {
                return;
            }

            const selectedOption = select.options[select.selectedIndex] || null;
            if (!selectedOption || !selectedOption.value) {
                return;
            }

            const credits = readNumber(select.dataset.credits || '0');
            const gradePoint = readNumber(selectedOption.dataset.gradePoint || '0');
            totalCredits += credits;
            totalQualityPoints += (credits * gradePoint);
            totalGraded += 1;
        });

        const gpa = totalCredits > 0 ? (totalQualityPoints / totalCredits) : 0;
        return {
            gpa,
            graded: totalGraded,
            credits: totalCredits,
        };
    }

    function ensureChart(initialValue) {
        if (typeof window.Chart !== 'function') {
            return;
        }

        if (donutChart) {
            return;
        }

        donutChart = new window.Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ['GPA', 'Remaining to 4.00'],
                datasets: [{
                    data: [initialValue, Math.max(0, 4 - initialValue)],
                    backgroundColor: ['#4a33ef', '#e6e9f2'],
                    borderWidth: 0,
                    hoverOffset: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '78%',
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                const value = Number(ctx.raw || 0).toFixed(2);
                                return `${ctx.label}: ${value}`;
                            },
                        },
                    },
                },
            },
        });
    }

    function updateChart(gpa) {
        if (!donutChart) {
            return;
        }

        donutChart.data.datasets[0].data = [gpa, Math.max(0, 4 - gpa)];
        donutChart.update();
    }

    function render() {
        const snapshot = calculateSnapshot();
        const roundedGpa = Math.max(0, Math.min(4, snapshot.gpa));
        termValue.textContent = roundedGpa.toFixed(2);
        gradedCount.textContent = String(snapshot.graded);
        creditTotal.textContent = snapshot.credits.toFixed(2);

        ensureChart(roundedGpa);
        updateChart(roundedGpa);
    }

    function onReady() {
        render();
        form.addEventListener('change', render);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady);
    } else {
        onReady();
    }
})();
</script>
