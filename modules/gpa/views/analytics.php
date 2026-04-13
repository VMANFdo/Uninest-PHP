<?php
$summary = (array) ($summary ?? []);
$trend = (array) ($trend ?? []);
$activeBatch = (array) ($active_batch ?? []);

$recordCount = (int) ($summary['record_count'] ?? 0);
$latestGpa = $summary['latest_gpa'] !== null ? (float) $summary['latest_gpa'] : null;
$bestGpa = $summary['best_gpa'] !== null ? (float) $summary['best_gpa'] : null;
$averageGpa = $summary['average_gpa'] !== null ? (float) $summary['average_gpa'] : null;
$totalCredits = (float) ($summary['total_credits'] ?? 0);
$totalSubjects = (int) ($summary['total_subjects'] ?? 0);

$trendLabels = [];
$trendValues = [];
foreach ($trend as $row) {
    $year = (int) ($row['academic_year'] ?? 0);
    $semester = (int) ($row['semester'] ?? 0);
    $trendLabels[] = 'Y' . $year . ' · S' . $semester;
    $trendValues[] = round((float) ($row['semester_gpa'] ?? 0), 2);
}

$trendLabelsJson = json_encode(
    $trendLabels,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?: '[]';
$trendValuesJson = json_encode(
    $trendValues,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?: '[]';
?>

<div class="page-header gpa-page-header">
    <div class="page-header-content">
        <p class="page-breadcrumb"><?= e((string) ($role_label ?? 'Student')) ?> / GPA / Analytics</p>
        <h1>Performance Analytics</h1>
        <p class="page-subtitle">
            Track your GPA progression across saved terms.
            <?php if (!empty($activeBatch['batch_code'])): ?>
                Batch: <strong><?= e((string) $activeBatch['batch_code']) ?></strong>.
            <?php endif; ?>
        </p>
    </div>
    <div class="page-header-actions">
        <a href="/dashboard/gpa" class="btn btn-primary"><?= ui_lucide_icon('calculator') ?> Open GPA Calculator</a>
    </div>
</div>

<section class="gpa-analytics-kpis">
    <article class="card gpa-kpi-card">
        <div class="card-body">
            <span>Latest GPA</span>
            <strong><?= $latestGpa !== null ? e(number_format($latestGpa, 2)) : '0.00' ?></strong>
        </div>
    </article>
    <article class="card gpa-kpi-card">
        <div class="card-body">
            <span>Best GPA</span>
            <strong><?= $bestGpa !== null ? e(number_format($bestGpa, 2)) : '0.00' ?></strong>
        </div>
    </article>
    <article class="card gpa-kpi-card">
        <div class="card-body">
            <span>Cumulative Avg</span>
            <strong><?= $averageGpa !== null ? e(number_format($averageGpa, 2)) : '0.00' ?></strong>
        </div>
    </article>
    <article class="card gpa-kpi-card">
        <div class="card-body">
            <span>Saved Terms</span>
            <strong><?= $recordCount ?></strong>
        </div>
    </article>
</section>

<section class="gpa-analytics-layout">
    <article class="card gpa-analytics-trend-card">
        <div class="card-body">
            <div class="gpa-chart-header">
                <h2><?= ui_lucide_icon('line-chart') ?> GPA Progression</h2>
                <span class="text-muted"><?= $recordCount ?> term<?= $recordCount === 1 ? '' : 's' ?> tracked</span>
            </div>

            <?php if (empty($trend)): ?>
                <p class="text-muted">No saved GPA records yet. Save your first term from the GPA calculator.</p>
            <?php else: ?>
                <div class="gpa-line-chart-wrap">
                    <canvas id="gpa-trend-chart" aria-label="GPA progression chart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </article>

    <aside class="card gpa-analytics-side-card">
        <div class="card-body">
            <h2><?= ui_lucide_icon('target') ?> Snapshot</h2>
            <div class="gpa-term-chart-wrap gpa-term-chart-wrap--small">
                <canvas id="gpa-latest-chart" aria-label="Latest GPA chart"></canvas>
            </div>

            <ul class="gpa-summary-list">
                <li>
                    <span>Total Credits Graded</span>
                    <strong><?= e(number_format($totalCredits, 2)) ?></strong>
                </li>
                <li>
                    <span>Total Subject Entries</span>
                    <strong><?= $totalSubjects ?></strong>
                </li>
                <li>
                    <span>Last Saved Term</span>
                    <strong>
                        <?php if (!empty($summary['latest_academic_year']) && !empty($summary['latest_semester'])): ?>
                            Y<?= (int) $summary['latest_academic_year'] ?> / S<?= (int) $summary['latest_semester'] ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </strong>
                </li>
            </ul>
        </div>
    </aside>
</section>

<section class="card gpa-history-card">
    <div class="card-body">
        <h2>Saved Term History</h2>
        <?php if (empty($trend)): ?>
            <p class="text-muted">No records saved yet.</p>
        <?php else: ?>
            <div class="gpa-history-table-wrap">
                <table class="gpa-history-table">
                    <thead>
                        <tr>
                            <th>Academic Term</th>
                            <th>Semester GPA</th>
                            <th>Total Credits</th>
                            <th>Subjects</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($trend) as $row): ?>
                            <?php
                            $updatedAt = '-';
                            if (!empty($row['updated_at'])) {
                                $timestamp = strtotime((string) $row['updated_at']);
                                if ($timestamp !== false) {
                                    $updatedAt = date('Y-m-d H:i', $timestamp);
                                }
                            }
                            ?>
                            <tr>
                                <td>Year <?= (int) ($row['academic_year'] ?? 0) ?> / Semester <?= (int) ($row['semester'] ?? 0) ?></td>
                                <td><strong><?= e(number_format((float) ($row['semester_gpa'] ?? 0), 2)) ?></strong></td>
                                <td><?= e(number_format((float) ($row['total_credits'] ?? 0), 2)) ?></td>
                                <td><?= (int) ($row['graded_subject_count'] ?? 0) ?></td>
                                <td><?= e($updatedAt) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<script id="gpa-trend-labels" type="application/json"><?= $trendLabelsJson ?></script>
<script id="gpa-trend-values" type="application/json"><?= $trendValuesJson ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>
<script>
(function () {
    function readJson(id, fallback) {
        const el = document.getElementById(id);
        if (!el) {
            return fallback;
        }

        try {
            return JSON.parse(el.textContent || '');
        } catch (error) {
            return fallback;
        }
    }

    function createTrendChart() {
        const canvas = document.getElementById('gpa-trend-chart');
        if (!canvas || typeof window.Chart !== 'function') {
            return;
        }

        const labels = readJson('gpa-trend-labels', []);
        const values = readJson('gpa-trend-values', []);
        if (!Array.isArray(labels) || !Array.isArray(values) || labels.length === 0) {
            return;
        }

        new window.Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Semester GPA',
                    data: values,
                    borderColor: '#4a33ef',
                    backgroundColor: 'rgba(74, 51, 239, 0.12)',
                    fill: true,
                    borderWidth: 3,
                    tension: 0.35,
                    pointRadius: 4,
                    pointHoverRadius: 5,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                },
                scales: {
                    y: {
                        min: 0,
                        max: 4,
                        ticks: {
                            stepSize: 0.5,
                        },
                    },
                },
            },
        });
    }

    function createLatestChart() {
        const canvas = document.getElementById('gpa-latest-chart');
        if (!canvas || typeof window.Chart !== 'function') {
            return;
        }

        const latest = Number(<?= json_encode($latestGpa !== null ? round($latestGpa, 2) : 0.0) ?>);
        new window.Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ['Latest GPA', 'Remaining to 4.00'],
                datasets: [{
                    data: [latest, Math.max(0, 4 - latest)],
                    backgroundColor: ['#4a33ef', '#e6e9f2'],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '76%',
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

    function init() {
        createTrendChart();
        createLatestChart();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
