-- =====================================================================
-- Student Grade Viewing System - Database Schema (PostgreSQL)
-- =====================================================================
-- Run this file first to create all tables, constraints, and indexes.
-- Example:  psql -U postgres -d grade_system -f schema.sql
-- =====================================================================

-- Drop tables if they already exist (useful when re-running during setup)
DROP TABLE IF EXISTS grades CASCADE;
DROP TABLE IF EXISTS academic_terms CASCADE;
DROP TABLE IF EXISTS subjects CASCADE;
DROP TABLE IF EXISTS teachers CASCADE;
DROP TABLE IF EXISTS students CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- =====================================================================
-- users
-- One row per login account (student, teacher, or admin).
-- =====================================================================
CREATE TABLE users (
    user_id        SERIAL PRIMARY KEY,
    login_id       VARCHAR(50)  NOT NULL UNIQUE,   -- Student ID / Teacher ID / Admin username
    password_hash  VARCHAR(255) NOT NULL,
    role           VARCHAR(20)  NOT NULL CHECK (role IN ('student', 'teacher', 'admin')),
    is_active      BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at     TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- =====================================================================
-- students
-- Extra profile info for users with role = 'student'.
-- =====================================================================
CREATE TABLE students (
    student_id   SERIAL PRIMARY KEY,
    user_id      INTEGER NOT NULL UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
    first_name   VARCHAR(100) NOT NULL,
    last_name    VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    course       VARCHAR(100) NOT NULL,
    year_level   INTEGER NOT NULL CHECK (year_level BETWEEN 1 AND 6)
);

-- =====================================================================
-- teachers
-- Extra profile info for users with role = 'teacher'.
-- =====================================================================
CREATE TABLE teachers (
    teacher_id   SERIAL PRIMARY KEY,
    user_id      INTEGER NOT NULL UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
    first_name   VARCHAR(100) NOT NULL,
    last_name    VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    department   VARCHAR(100) NOT NULL
);

-- =====================================================================
-- subjects
-- =====================================================================
CREATE TABLE subjects (
    subject_id     SERIAL PRIMARY KEY,
    subject_code   VARCHAR(20)  NOT NULL UNIQUE,
    subject_name   VARCHAR(150) NOT NULL,
    units          NUMERIC(3,1) NOT NULL CHECK (units > 0)
);

-- =====================================================================
-- academic_terms
-- =====================================================================
CREATE TABLE academic_terms (
    term_id      SERIAL PRIMARY KEY,
    school_year  VARCHAR(20) NOT NULL,         -- e.g. '2025-2026'
    semester     VARCHAR(30) NOT NULL,         -- e.g. '1st Semester'
    status       VARCHAR(20) NOT NULL DEFAULT 'inactive' CHECK (status IN ('active', 'inactive')),
    UNIQUE (school_year, semester)
);

-- =====================================================================
-- grades
-- Connects a student + subject + teacher + term to a single grade.
-- =====================================================================
CREATE TABLE grades (
    grade_id     SERIAL PRIMARY KEY,
    student_id   INTEGER NOT NULL REFERENCES students(student_id) ON DELETE CASCADE,
    subject_id   INTEGER NOT NULL REFERENCES subjects(subject_id) ON DELETE CASCADE,
    teacher_id   INTEGER NOT NULL REFERENCES teachers(teacher_id) ON DELETE CASCADE,
    term_id      INTEGER NOT NULL REFERENCES academic_terms(term_id) ON DELETE CASCADE,
    grade        NUMERIC(5,2) NULL CHECK (grade IS NULL OR (grade >= 0 AND grade <= 100)),
    remarks      VARCHAR(255) NULL,
    updated_at   TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (student_id, subject_id, term_id)   -- one grade per student/subject/term
);

-- =====================================================================
-- Indexes to speed up common lookups
-- =====================================================================
CREATE INDEX idx_students_user_id   ON students(user_id);
CREATE INDEX idx_teachers_user_id   ON teachers(user_id);
CREATE INDEX idx_grades_student_id  ON grades(student_id);
CREATE INDEX idx_grades_teacher_id  ON grades(teacher_id);
CREATE INDEX idx_grades_subject_id  ON grades(subject_id);
CREATE INDEX idx_grades_term_id     ON grades(term_id);
CREATE INDEX idx_users_login_id     ON users(login_id);
CREATE INDEX idx_users_role         ON users(role);
