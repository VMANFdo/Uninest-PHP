
-- Seed users (password for all seeded users: 123)
SET @seed_password_123 = '$2y$12$4sVZY2Cz8Lu71OKcmQa2lec45cNXKraA4OQryLa8.hNVQWDTBiOHu';

INSERT INTO users (name, email, password, role, academic_year, university_id, batch_id, first_approved_batch_id) VALUES
('Admin User', 'admin@uninest.com', @seed_password_123, 'admin', NULL, NULL, NULL, NULL),
('Moderator 1', 'm1@uninest.com', @seed_password_123, 'moderator', 3, (SELECT id FROM universities WHERE short_code = 'UOC' LIMIT 1), NULL, NULL)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password = VALUES(password),
    role = VALUES(role),
    academic_year = VALUES(academic_year),
    university_id = VALUES(university_id);

SET @seed_admin_id = (SELECT id FROM users WHERE email = 'admin@uninest.com' LIMIT 1);
SET @seed_moderator_id = (SELECT id FROM users WHERE email = 'm1@uninest.com' LIMIT 1);
SET @seed_uoc_id = (SELECT id FROM universities WHERE short_code = 'UOC' LIMIT 1);

-- Approved batch under moderator m1@uninest.com
INSERT INTO batches (
    batch_code,
    name,
    program,
    intake_year,
    university_id,
    moderator_user_id,
    status,
    rejection_reason,
    reviewed_by,
    reviewed_at
) VALUES (
    'BATCH-A1B2C3',
    'CS 24/25',
    'BSc Computer Science',
    2024,
    @seed_uoc_id,
    @seed_moderator_id,
    'approved',
    NULL,
    @seed_admin_id,
    NOW()
)
ON DUPLICATE KEY UPDATE
    batch_code = VALUES(batch_code),
    name = VALUES(name),
    program = VALUES(program),
    intake_year = VALUES(intake_year),
    university_id = VALUES(university_id),
    status = VALUES(status),
    rejection_reason = VALUES(rejection_reason),
    reviewed_by = VALUES(reviewed_by),
    reviewed_at = VALUES(reviewed_at);

SET @seed_batch_id = (SELECT id FROM batches WHERE moderator_user_id = @seed_moderator_id LIMIT 1);

-- Ensure moderator is assigned to his batch
UPDATE users
SET batch_id = @seed_batch_id
WHERE id = @seed_moderator_id;

-- Seed 10 students under moderator's batch
INSERT INTO users (name, email, password, role, academic_year, university_id, batch_id, first_approved_batch_id) VALUES
('Student 1',  'st1@uninest.com',  @seed_password_123, 'student', 1, @seed_uoc_id, @seed_batch_id, @seed_batch_id),
('Student 2',  'st2@uninest.com',  @seed_password_123, 'student', 1, @seed_uoc_id, @seed_batch_id, @seed_batch_id),
('Student 3',  'st3@uninest.com',  @seed_password_123, 'student', 2, @seed_uoc_id, @seed_batch_id, @seed_batch_id),
('Student 4',  'st4@uninest.com',  @seed_password_123, 'student', 2, @seed_uoc_id, @seed_batch_id, @seed_batch_id),
('Student 5',  'st5@uninest.com',  @seed_password_123, 'student', 2, @seed_uoc_id, @seed_batch_id, @seed_batch_id),
('Student 6',  'st6@uninest.com',  @seed_password_123, 'student', 3, @seed_uoc_id, @seed_batch_id, @seed_batch_id),
('Student 7',  'st7@uninest.com',  @seed_password_123, 'student', 3, @seed_uoc_id, @seed_batch_id, @seed_batch_id),
('Student 8',  'st8@uninest.com',  @seed_password_123, 'student', 3, @seed_uoc_id, @seed_batch_id, @seed_batch_id),
('Student 9',  'st9@uninest.com',  @seed_password_123, 'student', 4, @seed_uoc_id, @seed_batch_id, @seed_batch_id),
('Student 10', 'st10@uninest.com', @seed_password_123, 'student', 4, @seed_uoc_id, @seed_batch_id, @seed_batch_id)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password = VALUES(password),
    role = VALUES(role),
    academic_year = VALUES(academic_year),
    university_id = VALUES(university_id),
    batch_id = VALUES(batch_id),
    first_approved_batch_id = VALUES(first_approved_batch_id);

-- Ensure join-request rows exist/align for seeded students
INSERT INTO student_batch_requests (
    student_user_id,
    requested_batch_id,
    status,
    rejection_reason,
    reviewed_by,
    reviewed_role,
    reviewed_at
)
SELECT
    u.id AS student_user_id,
    @seed_batch_id AS requested_batch_id,
    'approved' AS status,
    NULL AS rejection_reason,
    @seed_admin_id AS reviewed_by,
    'admin' AS reviewed_role,
    NOW() AS reviewed_at
FROM users u
WHERE u.email IN (
    'st1@uninest.com',
    'st2@uninest.com',
    'st3@uninest.com',
    'st4@uninest.com',
    'st5@uninest.com',
    'st6@uninest.com',
    'st7@uninest.com',
    'st8@uninest.com',
    'st9@uninest.com',
    'st10@uninest.com'
)
ON DUPLICATE KEY UPDATE
    requested_batch_id = VALUES(requested_batch_id),
    status = VALUES(status),
    rejection_reason = VALUES(rejection_reason),
    reviewed_by = VALUES(reviewed_by),
    reviewed_role = VALUES(reviewed_role),
    reviewed_at = VALUES(reviewed_at);
