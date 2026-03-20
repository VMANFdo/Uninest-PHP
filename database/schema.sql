-- UniNest Kuppi Platform Database Schema
-- Run this file against your MySQL database to set up the tables.

CREATE DATABASE IF NOT EXISTS uninest
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE uninest;

-- ──────────────────────────────────────
-- Universities (Sri Lanka)
-- ──────────────────────────────────────

CREATE TABLE IF NOT EXISTS universities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    short_code VARCHAR(20) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ──────────────────────────────────────
-- Users
-- ──────────────────────────────────────

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'coordinator', 'moderator', 'admin') DEFAULT 'student',
    academic_year TINYINT UNSIGNED NULL,
    university_id INT NULL,
    batch_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_university (university_id),
    INDEX idx_users_batch (batch_id),
    CONSTRAINT fk_users_university FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ──────────────────────────────────────
-- Batches (moderator-owned)
-- ──────────────────────────────────────

CREATE TABLE IF NOT EXISTS batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_code VARCHAR(20) UNIQUE NULL,
    name VARCHAR(150) NOT NULL,
    program VARCHAR(150) NOT NULL,
    intake_year SMALLINT UNSIGNED NOT NULL,
    university_id INT NOT NULL,
    moderator_user_id INT NOT NULL UNIQUE,
    status ENUM('pending', 'approved', 'rejected', 'inactive') NOT NULL DEFAULT 'pending',
    rejection_reason TEXT NULL,
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_batches_status (status),
    INDEX idx_batches_university (university_id),
    CONSTRAINT fk_batches_university FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE RESTRICT,
    CONSTRAINT fk_batches_moderator FOREIGN KEY (moderator_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_batches_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Add users -> batches FK after batches exists (avoids circular create-order issues)
SET @has_fk_users_batch = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND CONSTRAINT_NAME = 'fk_users_batch'
);
SET @sql_users_batch_fk = IF(
    @has_fk_users_batch = 0,
    'ALTER TABLE users ADD CONSTRAINT fk_users_batch FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt_users_batch_fk FROM @sql_users_batch_fk;
EXECUTE stmt_users_batch_fk;
DEALLOCATE PREPARE stmt_users_batch_fk;

-- ──────────────────────────────────────
-- Student batch join requests
-- ──────────────────────────────────────

CREATE TABLE IF NOT EXISTS student_batch_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_user_id INT NOT NULL UNIQUE,
    requested_batch_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    rejection_reason TEXT NULL,
    reviewed_by INT NULL,
    reviewed_role ENUM('moderator', 'admin') NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_requests_status (status),
    INDEX idx_student_requests_batch (requested_batch_id),
    CONSTRAINT fk_student_requests_student FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_requests_batch FOREIGN KEY (requested_batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_requests_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ──────────────────────────────────────
-- Subjects (batch-scoped)
-- ──────────────────────────────────────

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    credits INT DEFAULT 3,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_subject_code_per_batch (batch_id, code),
    INDEX idx_subjects_batch (batch_id),
    CONSTRAINT fk_subjects_batch FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    CONSTRAINT fk_subjects_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Existing deployments that migrate from the previous schema should remove legacy global subjects
-- before adding the NOT NULL batch_id constraint to avoid orphaned content.
DELETE FROM subjects;

-- ──────────────────────────────────────
-- Seed data (optional, for testing)
-- ──────────────────────────────────────

INSERT INTO universities (name, short_code, is_active) VALUES
('University of Colombo', 'UOC', 1),
('University of Peradeniya', 'UOP', 1),
('University of Sri Jayewardenepura', 'USJ', 1),
('University of Kelaniya', 'UOK', 1),
('University of Moratuwa', 'UOM', 1),
('University of Jaffna', 'UOJ', 1),
('University of Ruhuna', 'UOR', 1),
('Rajarata University of Sri Lanka', 'RUSL', 1),
('Sabaragamuwa University of Sri Lanka', 'SUSL', 1),
('Wayamba University of Sri Lanka', 'WUSL', 1),
('Uva Wellassa University', 'UWU', 1),
('South Eastern University of Sri Lanka', 'SEUSL', 1),
('Eastern University, Sri Lanka', 'EUSL', 1),
('University of Vavuniya', 'UOV', 1),
('The Open University of Sri Lanka', 'OUSL', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    is_active = VALUES(is_active);

-- Admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@uninest.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    role = VALUES(role);
