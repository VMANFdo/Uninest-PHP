<?php

/**
 * GPA Module — Controllers
 */

function gpa_role_label(): string
{
    return match ((string) user_role()) {
        'admin' => 'Admin',
        'moderator' => 'Moderator',
        'coordinator' => 'Coordinator',
        default => 'Student',
    };
}

function gpa_current_user_id(): int
{
    return (int) (auth_id() ?? 0);
}

function gpa_current_batch_id(): int
{
    return (int) (auth_user()['batch_id'] ?? 0);
}

function gpa_calculator_allowed_roles(): array
{
    return ['student', 'coordinator'];
}

function gpa_user_can_use_calculator(): bool
{
    return in_array((string) user_role(), gpa_calculator_allowed_roles(), true);
}

function gpa_user_can_read_batch(int $batchId): bool
{
    if ($batchId <= 0) {
        return false;
    }

    if ((string) user_role() === 'admin') {
        return true;
    }

    return gpa_current_batch_id() === $batchId;
}

function gpa_user_can_manage_grade_scale_for_batch(int $batchId): bool
{
    if ($batchId <= 0) {
        return false;
    }

    $role = (string) user_role();
    if ($role === 'admin') {
        return true;
    }

    if ($role !== 'moderator') {
        return false;
    }

    return gpa_current_batch_id() === $batchId;
}

function gpa_calculator_url(int $academicYear = 0, int $semester = 0): string
{
    $query = [];
    if ($academicYear > 0) {
        $query['year'] = $academicYear;
    }
    if ($semester > 0) {
        $query['semester'] = $semester;
    }

    return '/dashboard/gpa' . (!empty($query) ? ('?' . http_build_query($query)) : '');
}

function gpa_grade_scale_url(int $batchId = 0, array $extraQuery = []): string
{
    $query = [];
    if ((string) user_role() === 'admin' && $batchId > 0) {
        $query['batch_id'] = $batchId;
    }

    foreach ($extraQuery as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $query[$key] = $value;
    }

    return '/dashboard/gpa/grade-scale' . (!empty($query) ? ('?' . http_build_query($query)) : '');
}

function gpa_normalize_letter_grade(string $value): string
{
    return strtoupper(trim($value));
}

function gpa_validate_grade_scale_input(int $batchId, ?int $excludeScaleId = null, int $defaultSortOrder = 1): array
{
    $errors = [];

    if ($batchId <= 0) {
        $errors[] = 'Batch is required.';
    }

    $letterGrade = gpa_normalize_letter_grade((string) request_input('letter_grade', ''));
    if ($letterGrade === '') {
        $errors[] = 'Letter grade is required.';
    } elseif (!preg_match('/^[A-Z][A-Z0-9+\-]{0,5}$/', $letterGrade)) {
        $errors[] = 'Letter grade format is invalid.';
    }

    $description = trim((string) request_input('description', ''));
    if (strlen($description) > 120) {
        $errors[] = 'Description must be at most 120 characters.';
    }

    $gradePointRaw = trim((string) request_input('grade_point', ''));
    if ($gradePointRaw === '' || !is_numeric($gradePointRaw)) {
        $errors[] = 'Grade point is required.';
        $gradePoint = null;
    } else {
        $gradePoint = (float) $gradePointRaw;
        if ($gradePoint < 0 || $gradePoint > 4) {
            $errors[] = 'Grade point must be between 0.00 and 4.00.';
        }
    }

    $sortOrder = (int) request_input('sort_order', $defaultSortOrder);
    if ($sortOrder <= 0) {
        $sortOrder = $defaultSortOrder;
    }

    if (empty($errors) && gpa_grade_scale_letter_exists($batchId, $letterGrade, $excludeScaleId)) {
        $errors[] = 'This letter grade already exists for the selected batch.';
    }

    return [
        'errors' => $errors,
        'data' => [
            'batch_id' => $batchId,
            'letter_grade' => $letterGrade,
            'description' => $description,
            'grade_point' => $gradePoint !== null ? round($gradePoint, 2) : null,
            'sort_order' => $sortOrder,
        ],
    ];
}

function gpa_sort_terms(array $terms): array
{
    usort($terms, static function (array $a, array $b): int {
        $yearA = (int) ($a['academic_year'] ?? 0);
        $yearB = (int) ($b['academic_year'] ?? 0);

        if ($yearA !== $yearB) {
            return $yearB <=> $yearA;
        }

        $semesterA = (int) ($a['semester'] ?? 0);
        $semesterB = (int) ($b['semester'] ?? 0);
        return $semesterB <=> $semesterA;
    });

    return $terms;
}

function gpa_resolve_term_selection(array $terms, int $requestedYear, int $requestedSemester): array
{
    $normalizedTerms = [];
    foreach ($terms as $term) {
        $year = (int) ($term['academic_year'] ?? 0);
        $semester = (int) ($term['semester'] ?? 0);
        if ($year <= 0 || $semester <= 0) {
            continue;
        }
        $normalizedTerms[] = [
            'academic_year' => $year,
            'semester' => $semester,
        ];
    }

    $normalizedTerms = gpa_sort_terms($normalizedTerms);

    if (empty($normalizedTerms)) {
        return [
            'terms' => [],
            'academic_year' => 0,
            'semester' => 0,
        ];
    }

    foreach ($normalizedTerms as $term) {
        if ((int) $term['academic_year'] === $requestedYear && (int) $term['semester'] === $requestedSemester) {
            return [
                'terms' => $normalizedTerms,
                'academic_year' => $requestedYear,
                'semester' => $requestedSemester,
            ];
        }
    }

    return [
        'terms' => $normalizedTerms,
        'academic_year' => (int) ($normalizedTerms[0]['academic_year'] ?? 0),
        'semester' => (int) ($normalizedTerms[0]['semester'] ?? 0),
    ];
}

function gpa_selected_grades_from_entries(array $entries): array
{
    $map = [];

    foreach ($entries as $entry) {
        $subjectId = (int) ($entry['subject_id'] ?? 0);
        if ($subjectId <= 0) {
            continue;
        }

        $letter = gpa_normalize_letter_grade((string) ($entry['letter_grade'] ?? ''));
        if ($letter === '') {
            continue;
        }

        $map[$subjectId] = $letter;
    }

    return $map;
}

function gpa_parse_grades_payload(mixed $payload): array
{
    if (!is_array($payload)) {
        return [];
    }

    $normalized = [];
    foreach ($payload as $subjectId => $letterGrade) {
        $subjectId = (int) $subjectId;
        if ($subjectId <= 0) {
            continue;
        }

        $normalized[$subjectId] = gpa_normalize_letter_grade((string) $letterGrade);
    }

    return $normalized;
}

function gpa_calculate_term_results(array $subjects, array $selectedGrades, array $gradeScaleMap): array
{
    $errors = [];

    $subjectMap = [];
    foreach ($subjects as $subject) {
        $subjectId = (int) ($subject['id'] ?? 0);
        if ($subjectId <= 0) {
            continue;
        }

        $subjectMap[$subjectId] = $subject;
    }

    $entries = [];
    $totalCredits = 0.0;
    $totalQualityPoints = 0.0;

    foreach ($selectedGrades as $subjectId => $letterGrade) {
        $letterGrade = gpa_normalize_letter_grade((string) $letterGrade);
        if ($letterGrade === '') {
            continue;
        }

        if (!isset($subjectMap[$subjectId])) {
            $errors[] = 'Submitted subject selection is invalid.';
            continue;
        }

        if (!isset($gradeScaleMap[$letterGrade])) {
            $errors[] = 'Selected grade scale is invalid.';
            continue;
        }

        $subject = $subjectMap[$subjectId];
        $credits = (float) ($subject['credits'] ?? 0);
        if ($credits <= 0) {
            $errors[] = 'Each subject must have credits greater than zero to calculate GPA.';
            continue;
        }

        $gradePoint = (float) ($gradeScaleMap[$letterGrade]['grade_point'] ?? 0);
        $qualityPoints = $credits * $gradePoint;

        $entries[] = [
            'subject_id' => (int) ($subject['id'] ?? 0),
            'subject_name_snapshot' => trim((string) ($subject['code'] ?? 'SUB') . ' - ' . (string) ($subject['name'] ?? 'Subject')),
            'credit_value' => $credits,
            'letter_grade' => $letterGrade,
            'grade_point_snapshot' => $gradePoint,
            'quality_points' => $qualityPoints,
        ];

        $totalCredits += $credits;
        $totalQualityPoints += $qualityPoints;
    }

    if (empty($entries)) {
        $errors[] = 'Select at least one subject grade to calculate GPA.';
    }

    if ($totalCredits <= 0) {
        $errors[] = 'Total credits must be greater than zero for GPA calculation.';
    }

    $semesterGpa = $totalCredits > 0
        ? round($totalQualityPoints / $totalCredits, 2)
        : 0.0;

    return [
        'errors' => $errors,
        'entries' => $entries,
        'total_credits' => $totalCredits,
        'total_quality_points' => $totalQualityPoints,
        'graded_subject_count' => count($entries),
        'semester_gpa' => $semesterGpa,
    ];
}

function gpa_calculator_index(): void
{
    if (!gpa_user_can_use_calculator()) {
        abort(403, 'Only students and coordinators can use the GPA calculator.');
    }

    $user = auth_user() ?? [];
    $userId = gpa_current_user_id();
    $batchId = gpa_current_batch_id();
    if ($batchId <= 0) {
        abort(403, 'You are not assigned to a batch.');
    }

    $activeBatch = gpa_find_batch_option_by_id($batchId);
    if (!$activeBatch) {
        abort(403, 'Your batch is not available for GPA calculations.');
    }

    $terms = gpa_subject_terms_for_batch($batchId);

    $selectedYear = (int) request_input('year', 0);
    $selectedSemester = (int) request_input('semester', 0);

    $oldInput = (array) ($_SESSION['_old_input'] ?? []);
    if (isset($oldInput['academic_year'])) {
        $selectedYear = (int) $oldInput['academic_year'];
    }
    if (isset($oldInput['semester'])) {
        $selectedSemester = (int) $oldInput['semester'];
    }

    $termSelection = gpa_resolve_term_selection($terms, $selectedYear, $selectedSemester);
    $selectedYear = (int) ($termSelection['academic_year'] ?? 0);
    $selectedSemester = (int) ($termSelection['semester'] ?? 0);

    $subjects = $selectedYear > 0 && $selectedSemester > 0
        ? gpa_subjects_for_term($batchId, $selectedYear, $selectedSemester)
        : [];

    $gradeScaleRows = gpa_grade_scales_for_batch($batchId);
    $gradeScaleMap = gpa_grade_scale_map_for_batch($batchId);
    $summary = gpa_summary_for_user($userId, $batchId);

    $record = null;
    $recordEntries = [];
    $selectedGrades = [];

    if ($selectedYear > 0 && $selectedSemester > 0) {
        $record = gpa_term_record_for_user($userId, $batchId, $selectedYear, $selectedSemester);
        if ($record) {
            $recordEntries = gpa_term_entries_for_record((int) ($record['id'] ?? 0));
            $selectedGrades = gpa_selected_grades_from_entries($recordEntries);
        }
    }

    if (isset($oldInput['grades']) && is_array($oldInput['grades'])) {
        $selectedGrades = gpa_parse_grades_payload($oldInput['grades']);
    }

    $preview = gpa_calculate_term_results($subjects, $selectedGrades, $gradeScaleMap);

    view('gpa::calculator', [
        'role_label' => gpa_role_label(),
        'user' => $user,
        'active_batch' => $activeBatch,
        'terms' => (array) ($termSelection['terms'] ?? []),
        'selected_year' => $selectedYear,
        'selected_semester' => $selectedSemester,
        'subjects' => $subjects,
        'grade_scale_rows' => $gradeScaleRows,
        'grade_scale_map' => $gradeScaleMap,
        'selected_grades' => $selectedGrades,
        'record' => $record,
        'record_entries' => $recordEntries,
        'preview' => $preview,
        'summary' => $summary,
    ], 'dashboard');
}

function gpa_calculator_store(): void
{
    csrf_check();

    if (!gpa_user_can_use_calculator()) {
        abort(403, 'Only students and coordinators can save GPA records.');
    }

    $userId = gpa_current_user_id();
    $batchId = gpa_current_batch_id();
    if ($batchId <= 0) {
        abort(403, 'You are not assigned to a batch.');
    }

    $academicYear = (int) request_input('academic_year', 0);
    $semester = (int) request_input('semester', 0);

    $terms = gpa_subject_terms_for_batch($batchId);
    $termSelection = gpa_resolve_term_selection($terms, $academicYear, $semester);

    if ($academicYear <= 0 || $semester <= 0 || $academicYear !== (int) ($termSelection['academic_year'] ?? 0) || $semester !== (int) ($termSelection['semester'] ?? 0)) {
        flash('error', 'Selected academic term is invalid for your batch.');
        flash_old_input();
        redirect(gpa_calculator_url((int) ($termSelection['academic_year'] ?? 0), (int) ($termSelection['semester'] ?? 0)));
    }

    $subjects = gpa_subjects_for_term($batchId, $academicYear, $semester);
    if (empty($subjects)) {
        flash('error', 'No subjects available for the selected term.');
        flash_old_input();
        redirect(gpa_calculator_url($academicYear, $semester));
    }

    $gradeScaleMap = gpa_grade_scale_map_for_batch($batchId);
    if (empty($gradeScaleMap)) {
        flash('error', 'Grade point configuration is not available for your batch yet.');
        flash_old_input();
        redirect(gpa_calculator_url($academicYear, $semester));
    }

    $selectedGrades = gpa_parse_grades_payload($_POST['grades'] ?? []);
    $calculated = gpa_calculate_term_results($subjects, $selectedGrades, $gradeScaleMap);

    if (!empty($calculated['errors'])) {
        flash('error', implode(' ', array_values(array_unique($calculated['errors']))));
        flash_old_input();
        redirect(gpa_calculator_url($academicYear, $semester));
    }

    try {
        gpa_term_record_upsert_with_entries(
            $userId,
            $batchId,
            $academicYear,
            $semester,
            (float) $calculated['semester_gpa'],
            (float) $calculated['total_credits'],
            (int) $calculated['graded_subject_count'],
            (array) $calculated['entries']
        );
    } catch (Throwable) {
        flash('error', 'Unable to save GPA record right now.');
        flash_old_input();
        redirect(gpa_calculator_url($academicYear, $semester));
    }

    clear_old_input();
    flash('success', 'GPA record saved. Semester GPA: ' . number_format((float) $calculated['semester_gpa'], 2));
    redirect(gpa_calculator_url($academicYear, $semester));
}

function gpa_analytics_index(): void
{
    if (!gpa_user_can_use_calculator()) {
        abort(403, 'Only students and coordinators can access GPA analytics.');
    }

    $batchId = gpa_current_batch_id();
    if ($batchId <= 0) {
        abort(403, 'You are not assigned to a batch.');
    }

    $activeBatch = gpa_find_batch_option_by_id($batchId);
    if (!$activeBatch) {
        abort(403, 'Your batch is not available for GPA analytics.');
    }

    $summary = gpa_summary_for_user(gpa_current_user_id(), $batchId);
    $trend = gpa_term_trend_for_user(gpa_current_user_id(), $batchId);

    view('gpa::analytics', [
        'role_label' => gpa_role_label(),
        'active_batch' => $activeBatch,
        'summary' => $summary,
        'trend' => $trend,
    ], 'dashboard');
}

function gpa_grade_scale_index(): void
{
    $role = (string) user_role();
    if (!in_array($role, ['student', 'coordinator', 'moderator', 'admin'], true)) {
        abort(403, 'You do not have permission to view grade point configuration.');
    }

    $isAdmin = $role === 'admin';
    $batchOptions = $isAdmin ? gpa_batch_options_for_admin() : [];
    $selectedBatchId = 0;
    $activeBatch = null;

    if ($isAdmin) {
        $selectedBatchId = (int) request_input('batch_id', 0);
        if ($selectedBatchId > 0) {
            $activeBatch = gpa_find_batch_option_by_id($selectedBatchId);
            if (!$activeBatch) {
                $selectedBatchId = 0;
            }
        }
    } else {
        $selectedBatchId = gpa_current_batch_id();
        if ($selectedBatchId <= 0) {
            abort(403, 'You are not assigned to a batch.');
        }

        if (!gpa_user_can_read_batch($selectedBatchId)) {
            abort(403, 'You do not have permission to view this grade scale.');
        }

        $activeBatch = gpa_find_batch_option_by_id($selectedBatchId);
    }

    if ($selectedBatchId > 0 && !gpa_user_can_read_batch($selectedBatchId)) {
        abort(403, 'You do not have permission to view this grade scale.');
    }

    $canManage = $selectedBatchId > 0 && gpa_user_can_manage_grade_scale_for_batch($selectedBatchId);
    $scaleRows = $selectedBatchId > 0 ? gpa_grade_scales_for_batch($selectedBatchId) : [];

    $metrics = [
        'total_grades' => count($scaleRows),
        'highest_grade_point' => null,
        'lowest_grade_point' => null,
    ];

    foreach ($scaleRows as $row) {
        $gradePoint = (float) ($row['grade_point'] ?? 0);
        if ($metrics['highest_grade_point'] === null || $gradePoint > $metrics['highest_grade_point']) {
            $metrics['highest_grade_point'] = $gradePoint;
        }
        if ($metrics['lowest_grade_point'] === null || $gradePoint < $metrics['lowest_grade_point']) {
            $metrics['lowest_grade_point'] = $gradePoint;
        }
    }

    view('gpa::grade_scale', [
        'role_label' => gpa_role_label(),
        'is_admin' => $isAdmin,
        'batch_options' => $batchOptions,
        'selected_batch_id' => $selectedBatchId,
        'active_batch' => $activeBatch,
        'can_manage' => $canManage,
        'is_read_only' => !$canManage,
        'scale_rows' => $scaleRows,
        'metrics' => $metrics,
    ], 'dashboard');
}

function gpa_grade_scale_store(): void
{
    csrf_check();

    $role = (string) user_role();
    if (!in_array($role, ['moderator', 'admin'], true)) {
        abort(403, 'Only moderators and admins can manage grade scale configuration.');
    }

    $batchId = $role === 'admin'
        ? (int) request_input('batch_id', 0)
        : gpa_current_batch_id();

    if ($batchId <= 0) {
        flash('error', 'Select a batch before adding grade scale rows.');
        redirect(gpa_grade_scale_url());
    }

    if (!gpa_user_can_manage_grade_scale_for_batch($batchId)) {
        abort(403, 'You do not have permission to manage this grade scale.');
    }

    if ($role === 'admin' && !gpa_find_batch_option_by_id($batchId)) {
        flash('error', 'Selected batch is not available.');
        redirect(gpa_grade_scale_url());
    }

    $defaultSortOrder = gpa_grade_scale_next_sort_order($batchId);
    $validated = gpa_validate_grade_scale_input($batchId, null, $defaultSortOrder);
    if (!empty($validated['errors'])) {
        flash('error', implode(' ', $validated['errors']));
        flash_old_input();
        redirect(gpa_grade_scale_url($batchId));
    }

    try {
        gpa_grade_scale_create([
            'batch_id' => $batchId,
            'letter_grade' => (string) $validated['data']['letter_grade'],
            'description' => (string) $validated['data']['description'],
            'grade_point' => (float) ($validated['data']['grade_point'] ?? 0),
            'sort_order' => (int) ($validated['data']['sort_order'] ?? $defaultSortOrder),
            'created_by_user_id' => gpa_current_user_id(),
            'updated_by_user_id' => gpa_current_user_id(),
        ]);
    } catch (Throwable) {
        flash('error', 'Unable to add grade scale row right now.');
        redirect(gpa_grade_scale_url($batchId));
    }

    clear_old_input();
    flash('success', 'Grade scale row added.');
    redirect(gpa_grade_scale_url($batchId));
}

function gpa_grade_scale_update(string $id): void
{
    csrf_check();

    $role = (string) user_role();
    if (!in_array($role, ['moderator', 'admin'], true)) {
        abort(403, 'Only moderators and admins can manage grade scale configuration.');
    }

    $scaleId = (int) $id;
    $scale = gpa_grade_scale_find_by_id($scaleId);
    if (!$scale) {
        abort(404, 'Grade scale row not found.');
    }

    $batchId = (int) ($scale['batch_id'] ?? 0);
    if (!gpa_user_can_manage_grade_scale_for_batch($batchId)) {
        abort(403, 'You do not have permission to update this grade scale row.');
    }

    if ($role === 'admin') {
        $contextBatchId = (int) request_input('batch_id', 0);
        if ($contextBatchId <= 0 || $contextBatchId !== $batchId) {
            abort(403, 'Select the correct batch context before updating this row.');
        }
    }

    $validated = gpa_validate_grade_scale_input($batchId, $scaleId, (int) ($scale['sort_order'] ?? 1));
    if (!empty($validated['errors'])) {
        flash('error', implode(' ', $validated['errors']));
        flash_old_input();
        redirect(gpa_grade_scale_url($batchId));
    }

    try {
        $updated = gpa_grade_scale_update_row($scaleId, $batchId, [
            'letter_grade' => (string) $validated['data']['letter_grade'],
            'description' => (string) $validated['data']['description'],
            'grade_point' => (float) ($validated['data']['grade_point'] ?? 0),
            'sort_order' => (int) ($validated['data']['sort_order'] ?? 1),
            'updated_by_user_id' => gpa_current_user_id(),
        ]);
    } catch (Throwable) {
        flash('error', 'Unable to update grade scale row right now.');
        redirect(gpa_grade_scale_url($batchId));
    }

    if (!$updated) {
        flash('error', 'Unable to update this grade scale row.');
        redirect(gpa_grade_scale_url($batchId));
    }

    clear_old_input();
    flash('success', 'Grade scale row updated.');
    redirect(gpa_grade_scale_url($batchId));
}

function gpa_grade_scale_delete(string $id): void
{
    csrf_check();

    $role = (string) user_role();
    if (!in_array($role, ['moderator', 'admin'], true)) {
        abort(403, 'Only moderators and admins can manage grade scale configuration.');
    }

    $scaleId = (int) $id;
    $scale = gpa_grade_scale_find_by_id($scaleId);
    if (!$scale) {
        abort(404, 'Grade scale row not found.');
    }

    $batchId = (int) ($scale['batch_id'] ?? 0);
    if (!gpa_user_can_manage_grade_scale_for_batch($batchId)) {
        abort(403, 'You do not have permission to delete this grade scale row.');
    }

    if ($role === 'admin') {
        $contextBatchId = (int) request_input('batch_id', 0);
        if ($contextBatchId <= 0 || $contextBatchId !== $batchId) {
            abort(403, 'Select the correct batch context before deleting this row.');
        }
    }

    try {
        $deleted = gpa_grade_scale_delete_row($scaleId, $batchId);
    } catch (Throwable) {
        flash('error', 'Unable to delete grade scale row right now.');
        redirect(gpa_grade_scale_url($batchId));
    }

    if (!$deleted) {
        flash('error', 'Unable to delete this grade scale row.');
        redirect(gpa_grade_scale_url($batchId));
    }

    flash('success', 'Grade scale row deleted.');
    redirect(gpa_grade_scale_url($batchId));
}
