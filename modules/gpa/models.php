<?php

/**
 * GPA Module — Models
 */

function gpa_batch_options_for_admin(): array
{
    return onboarding_approved_batches();
}

function gpa_find_batch_option_by_id(int $batchId): ?array
{
    if ($batchId <= 0) {
        return null;
    }

    return db_fetch(
        "SELECT b.id, b.batch_code, b.name, b.program, b.intake_year,
                u.name AS university_name
         FROM batches b
         LEFT JOIN universities u ON u.id = b.university_id
         WHERE b.id = ?
           AND b.status = 'approved'
         LIMIT 1",
        [$batchId]
    );
}

function gpa_grade_scales_for_batch(int $batchId): array
{
    if ($batchId <= 0) {
        return [];
    }

    return db_fetch_all(
        "SELECT s.*,
                creator.name AS created_by_name,
                updater.name AS updated_by_name
         FROM gpa_batch_grade_scales s
         LEFT JOIN users creator ON creator.id = s.created_by_user_id
         LEFT JOIN users updater ON updater.id = s.updated_by_user_id
         WHERE s.batch_id = ?
         ORDER BY s.sort_order ASC, s.grade_point DESC, s.letter_grade ASC, s.id ASC",
        [$batchId]
    );
}

function gpa_grade_scale_map_for_batch(int $batchId): array
{
    $rows = gpa_grade_scales_for_batch($batchId);
    $map = [];

    foreach ($rows as $row) {
        $letter = strtoupper(trim((string) ($row['letter_grade'] ?? '')));
        if ($letter === '') {
            continue;
        }

        $map[$letter] = [
            'id' => (int) ($row['id'] ?? 0),
            'letter_grade' => $letter,
            'description' => trim((string) ($row['description'] ?? '')),
            'grade_point' => (float) ($row['grade_point'] ?? 0),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
        ];
    }

    return $map;
}

function gpa_grade_scale_find_by_id(int $scaleId): ?array
{
    if ($scaleId <= 0) {
        return null;
    }

    return db_fetch(
        "SELECT s.*,
                creator.name AS created_by_name,
                updater.name AS updated_by_name
         FROM gpa_batch_grade_scales s
         LEFT JOIN users creator ON creator.id = s.created_by_user_id
         LEFT JOIN users updater ON updater.id = s.updated_by_user_id
         WHERE s.id = ?
         LIMIT 1",
        [$scaleId]
    );
}

function gpa_grade_scale_letter_exists(int $batchId, string $letterGrade, ?int $excludeId = null): bool
{
    if ($batchId <= 0 || $letterGrade === '') {
        return false;
    }

    if ($excludeId !== null && $excludeId > 0) {
        return (bool) db_fetch(
            'SELECT id FROM gpa_batch_grade_scales WHERE batch_id = ? AND letter_grade = ? AND id <> ? LIMIT 1',
            [$batchId, $letterGrade, $excludeId]
        );
    }

    return (bool) db_fetch(
        'SELECT id FROM gpa_batch_grade_scales WHERE batch_id = ? AND letter_grade = ? LIMIT 1',
        [$batchId, $letterGrade]
    );
}

function gpa_grade_scale_next_sort_order(int $batchId): int
{
    if ($batchId <= 0) {
        return 1;
    }

    $row = db_fetch(
        'SELECT COALESCE(MAX(sort_order), 0) AS max_sort FROM gpa_batch_grade_scales WHERE batch_id = ?',
        [$batchId]
    );

    return ((int) ($row['max_sort'] ?? 0)) + 1;
}

function gpa_grade_scale_create(array $data): int
{
    return (int) db_insert('gpa_batch_grade_scales', [
        'batch_id' => (int) $data['batch_id'],
        'letter_grade' => $data['letter_grade'],
        'description' => $data['description'] !== '' ? $data['description'] : null,
        'grade_point' => (float) $data['grade_point'],
        'sort_order' => (int) $data['sort_order'],
        'created_by_user_id' => (int) $data['created_by_user_id'],
        'updated_by_user_id' => (int) $data['updated_by_user_id'],
    ]);
}

function gpa_grade_scale_update_row(int $scaleId, int $batchId, array $data): bool
{
    if ($scaleId <= 0 || $batchId <= 0) {
        return false;
    }

    $stmt = db_query(
        "UPDATE gpa_batch_grade_scales
         SET letter_grade = ?,
             description = ?,
             grade_point = ?,
             sort_order = ?,
             updated_by_user_id = ?,
             updated_at = NOW()
         WHERE id = ?
           AND batch_id = ?",
        [
            $data['letter_grade'],
            $data['description'] !== '' ? $data['description'] : null,
            (float) $data['grade_point'],
            (int) $data['sort_order'],
            (int) $data['updated_by_user_id'],
            $scaleId,
            $batchId,
        ]
    );

    if ($stmt->rowCount() > 0) {
        return true;
    }

    return (bool) db_fetch(
        'SELECT id FROM gpa_batch_grade_scales WHERE id = ? AND batch_id = ? LIMIT 1',
        [$scaleId, $batchId]
    );
}

function gpa_grade_scale_delete_row(int $scaleId, int $batchId): bool
{
    if ($scaleId <= 0 || $batchId <= 0) {
        return false;
    }

    $stmt = db_query(
        'DELETE FROM gpa_batch_grade_scales WHERE id = ? AND batch_id = ?',
        [$scaleId, $batchId]
    );

    return $stmt->rowCount() > 0;
}

function gpa_subject_terms_for_batch(int $batchId): array
{
    return subjects_terms_for_batch($batchId);
}

function gpa_subjects_for_term(int $batchId, int $academicYear, int $semester): array
{
    if ($batchId <= 0 || $academicYear <= 0 || $semester <= 0) {
        return [];
    }

    return db_fetch_all(
        "SELECT id, code, name, credits, academic_year, semester
         FROM subjects
         WHERE batch_id = ?
           AND academic_year = ?
           AND semester = ?
         ORDER BY code ASC, name ASC, id ASC",
        [$batchId, $academicYear, $semester]
    );
}

function gpa_term_record_for_user(int $userId, int $batchId, int $academicYear, int $semester): ?array
{
    if ($userId <= 0 || $batchId <= 0 || $academicYear <= 0 || $semester <= 0) {
        return null;
    }

    return db_fetch(
        "SELECT *
         FROM gpa_term_records
         WHERE user_id = ?
           AND batch_id = ?
           AND academic_year = ?
           AND semester = ?
         LIMIT 1",
        [$userId, $batchId, $academicYear, $semester]
    );
}

function gpa_term_entries_for_record(int $termRecordId): array
{
    if ($termRecordId <= 0) {
        return [];
    }

    return db_fetch_all(
        "SELECT e.*,
                s.code AS subject_code,
                s.name AS subject_name
         FROM gpa_term_subject_entries e
         LEFT JOIN subjects s ON s.id = e.subject_id
         WHERE e.term_record_id = ?
         ORDER BY COALESCE(s.code, e.subject_name_snapshot) ASC, e.id ASC",
        [$termRecordId]
    );
}

function gpa_term_record_upsert_with_entries(
    int $userId,
    int $batchId,
    int $academicYear,
    int $semester,
    float $semesterGpa,
    float $totalCredits,
    int $gradedSubjectCount,
    array $entries
): int {
    $pdo = db_connect();
    $pdo->beginTransaction();

    try {
        $existing = db_fetch(
            "SELECT id
             FROM gpa_term_records
             WHERE user_id = ?
               AND batch_id = ?
               AND academic_year = ?
               AND semester = ?
             FOR UPDATE",
            [$userId, $batchId, $academicYear, $semester]
        );

        if ($existing) {
            $termRecordId = (int) ($existing['id'] ?? 0);

            db_query(
                "UPDATE gpa_term_records
                 SET semester_gpa = ?,
                     total_credits = ?,
                     graded_subject_count = ?,
                     updated_at = NOW()
                 WHERE id = ?",
                [$semesterGpa, $totalCredits, $gradedSubjectCount, $termRecordId]
            );
        } else {
            $termRecordId = (int) db_insert('gpa_term_records', [
                'user_id' => $userId,
                'batch_id' => $batchId,
                'academic_year' => $academicYear,
                'semester' => $semester,
                'semester_gpa' => $semesterGpa,
                'total_credits' => $totalCredits,
                'graded_subject_count' => $gradedSubjectCount,
            ]);
        }

        db_query('DELETE FROM gpa_term_subject_entries WHERE term_record_id = ?', [$termRecordId]);

        foreach ($entries as $entry) {
            db_insert('gpa_term_subject_entries', [
                'term_record_id' => $termRecordId,
                'subject_id' => isset($entry['subject_id']) && $entry['subject_id'] !== null
                    ? (int) $entry['subject_id']
                    : null,
                'subject_name_snapshot' => (string) ($entry['subject_name_snapshot'] ?? ''),
                'credit_value' => (float) ($entry['credit_value'] ?? 0),
                'letter_grade' => (string) ($entry['letter_grade'] ?? ''),
                'grade_point_snapshot' => (float) ($entry['grade_point_snapshot'] ?? 0),
                'quality_points' => (float) ($entry['quality_points'] ?? 0),
            ]);
        }

        $pdo->commit();
        return $termRecordId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function gpa_term_trend_for_user(int $userId, int $batchId): array
{
    if ($userId <= 0 || $batchId <= 0) {
        return [];
    }

    return db_fetch_all(
        "SELECT academic_year,
                semester,
                semester_gpa,
                total_credits,
                graded_subject_count,
                created_at,
                updated_at
         FROM gpa_term_records
         WHERE user_id = ?
           AND batch_id = ?
         ORDER BY academic_year ASC, semester ASC, id ASC",
        [$userId, $batchId]
    );
}

function gpa_summary_for_user(int $userId, int $batchId): array
{
    if ($userId <= 0 || $batchId <= 0) {
        return [
            'record_count' => 0,
            'best_gpa' => null,
            'average_gpa' => null,
            'total_credits' => 0.0,
            'total_subjects' => 0,
            'latest_gpa' => null,
        ];
    }

    $totals = db_fetch(
        "SELECT COUNT(*) AS record_count,
                MAX(semester_gpa) AS best_gpa,
                AVG(semester_gpa) AS average_gpa,
                COALESCE(SUM(total_credits), 0) AS total_credits,
                COALESCE(SUM(graded_subject_count), 0) AS total_subjects
         FROM gpa_term_records
         WHERE user_id = ?
           AND batch_id = ?",
        [$userId, $batchId]
    ) ?? [];

    $latest = db_fetch(
        "SELECT semester_gpa, academic_year, semester, updated_at
         FROM gpa_term_records
         WHERE user_id = ?
           AND batch_id = ?
         ORDER BY academic_year DESC, semester DESC, id DESC
         LIMIT 1",
        [$userId, $batchId]
    ) ?? [];

    return [
        'record_count' => (int) ($totals['record_count'] ?? 0),
        'best_gpa' => $totals['best_gpa'] !== null ? (float) $totals['best_gpa'] : null,
        'average_gpa' => $totals['average_gpa'] !== null ? (float) $totals['average_gpa'] : null,
        'total_credits' => (float) ($totals['total_credits'] ?? 0),
        'total_subjects' => (int) ($totals['total_subjects'] ?? 0),
        'latest_gpa' => $latest['semester_gpa'] !== null ? (float) $latest['semester_gpa'] : null,
        'latest_academic_year' => isset($latest['academic_year']) ? (int) $latest['academic_year'] : null,
        'latest_semester' => isset($latest['semester']) ? (int) $latest['semester'] : null,
        'latest_updated_at' => $latest['updated_at'] ?? null,
    ];
}
