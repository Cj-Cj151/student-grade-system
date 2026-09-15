-- =====================================================================
-- Student Grade Viewing System - Sample / Test Data
-- =====================================================================
-- Run this AFTER schema.sql.
-- Example:  psql -U postgres -d grade_system -f seed.sql
--
-- All passwords below were hashed with PHP's password_hash() (bcrypt).
-- Plain-text test passwords are listed next to each user for convenience.
-- =====================================================================

-- ---------------------------------------------------------------------
-- USERS
-- Admin login:    admin    / admin123
-- Teacher logins: T-1001   / teacher123   (T-1002 uses the same password)
-- Student logins: S-2001.. / student123   (all sample students share it)
-- ---------------------------------------------------------------------
INSERT INTO users (login_id, password_hash, role, is_active) VALUES
('admin',  '$2b$10$xY.AvwIP6ZSaFt8h3qzwAOvbOa4DZ1gmo2mmd4xMVSqbL1CSgiTaO', 'admin',   TRUE),
('T-1001', '$2b$10$qwy5upz6WcbquhCd/IsLDOvCQEDQBY9.xQbhbZtTMnsEM8wBz07zG', 'teacher', TRUE),
('T-1002', '$2b$10$qwy5upz6WcbquhCd/IsLDOvCQEDQBY9.xQbhbZtTMnsEM8wBz07zG', 'teacher', TRUE),
('S-2001', '$2b$10$GemD88Xyc.q4Y6sGsNa6n.OgSrO/G6ZGMkt3X45p6Y22wBXCD6Hcu', 'student', TRUE),
('S-2002', '$2b$10$GemD88Xyc.q4Y6sGsNa6n.OgSrO/G6ZGMkt3X45p6Y22wBXCD6Hcu', 'student', TRUE),
('S-2003', '$2b$10$GemD88Xyc.q4Y6sGsNa6n.OgSrO/G6ZGMkt3X45p6Y22wBXCD6Hcu', 'student', TRUE),
('S-2004', '$2b$10$GemD88Xyc.q4Y6sGsNa6n.OgSrO/G6ZGMkt3X45p6Y22wBXCD6Hcu', 'student', FALSE); -- deactivated, for testing

-- ---------------------------------------------------------------------
-- TEACHERS  (user_id 2 and 3 above)
-- ---------------------------------------------------------------------
INSERT INTO teachers (user_id, first_name, last_name, email, department) VALUES
(2, 'Maria',  'Santos',   'maria.santos@rosemont.edu',   'Mathematics'),
(3, 'James',  'Reyes',    'james.reyes@rosemont.edu',    'Computer Science');

-- ---------------------------------------------------------------------
-- STUDENTS  (user_id 4-7 above)
-- ---------------------------------------------------------------------
INSERT INTO students (user_id, first_name, last_name, email, course, year_level) VALUES
(4, 'Ana',    'Dela Cruz', 'ana.delacruz@student.rosemont.edu',    'BS Computer Science', 2),
(5, 'Miguel', 'Torres',    'miguel.torres@student.rosemont.edu',   'BS Computer Science', 2),
(6, 'Sofia',  'Ramirez',   'sofia.ramirez@student.rosemont.edu',   'BS Information Technology', 1),
(7, 'Liam',   'Fernandez', 'liam.fernandez@student.rosemont.edu',  'BS Information Technology', 3);

-- ---------------------------------------------------------------------
-- SUBJECTS
-- ---------------------------------------------------------------------
INSERT INTO subjects (subject_code, subject_name, units) VALUES
('MATH101', 'College Algebra',            3.0),
('CS101',   'Introduction to Programming',3.0),
('CS102',   'Data Structures',            3.0),
('IT101',   'Web Development Fundamentals',3.0),
('GEC104',  'Purposive Communication',    3.0);

-- ---------------------------------------------------------------------
-- ACADEMIC TERMS
-- ---------------------------------------------------------------------
INSERT INTO academic_terms (school_year, semester, status) VALUES
('2024-2025', '2nd Semester', 'inactive'),
('2025-2026', '1st Semester', 'active');

-- ---------------------------------------------------------------------
-- GRADES
-- subject_id: MATH101=1, CS101=2, CS102=3, IT101=4, GEC104=5
-- term_id:    2024-2025 2nd Sem = 1, 2025-2026 1st Sem = 2
-- teacher_id: Maria Santos = 1 (Math), James Reyes = 2 (CS/IT)
-- ---------------------------------------------------------------------
INSERT INTO grades (student_id, subject_id, teacher_id, term_id, grade, remarks) VALUES
-- Ana Dela Cruz (student_id 1) - previous term, completed
(1, 1, 1, 1, 88.50, 'Good standing'),
(1, 2, 2, 1, 91.00, 'Excellent work'),
-- Ana Dela Cruz - current term, still being encoded
(1, 3, 2, 2, NULL,  NULL),

-- Miguel Torres (student_id 2)
(2, 1, 1, 1, 74.00, 'Passed'),
(2, 2, 2, 1, 65.00, 'Needs improvement'),
(2, 3, 2, 2, NULL,  NULL),

-- Sofia Ramirez (student_id 3) - Year 1, IT
(3, 4, 2, 2, NULL,  NULL),
(3, 5, 1, 2, 95.00, 'Outstanding'),

-- Liam Fernandez (student_id 4) - Year 3, IT
(4, 4, 2, 1, 58.00, 'Did not meet passing requirements'),
(4, 5, 1, 2, 82.00, 'Satisfactory');
