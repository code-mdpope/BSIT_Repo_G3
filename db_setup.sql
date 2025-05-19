-- IDSC Portal Database Setup

-- Create the database
CREATE DATABASE IF NOT EXISTS idsc_portal;
USE idsc_portal;

-- Users table (common for all user types)
CREATE TABLE users (
    user_id VARCHAR(20) PRIMARY KEY,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'instructor', 'admin') NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME DEFAULT NULL
);

-- Students table
CREATE TABLE students (
    student_id VARCHAR(20) PRIMARY KEY,
    program VARCHAR(100) NOT NULL,
    year_level INT NOT NULL,
    gpa DECIMAL(3,2) DEFAULT 0.00,
    credits_earned INT DEFAULT 0,
    credits_required INT NOT NULL,
    advisor_id VARCHAR(20) DEFAULT NULL,
    enrollment_date DATE NOT NULL,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (advisor_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Instructors table
CREATE TABLE instructors (
    instructor_id VARCHAR(20) PRIMARY KEY,
    department VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    hire_date DATE NOT NULL,
    FOREIGN KEY (instructor_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Administrators table
CREATE TABLE administrators (
    admin_id VARCHAR(20) PRIMARY KEY,
    department VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    hire_date DATE NOT NULL,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Courses table
CREATE TABLE courses (
    course_id VARCHAR(20) PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    description TEXT,
    credits INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Classes table (instances of courses)
CREATE TABLE classes (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id VARCHAR(20) NOT NULL,
    instructor_id VARCHAR(20) NOT NULL,
    semester ENUM('Spring', 'Summer', 'Fall') NOT NULL,
    year INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES instructors(instructor_id) ON DELETE CASCADE
);

-- Class schedule
CREATE TABLE class_schedule (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(50) NOT NULL,
    building VARCHAR(100) NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE
);

-- Enrollments table
CREATE TABLE enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    class_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('enrolled', 'dropped', 'completed') NOT NULL DEFAULT 'enrolled',
    grade VARCHAR(2) DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    UNIQUE KEY (student_id, class_id)
);

-- Assignments table
CREATE TABLE assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    due_date DATETIME NOT NULL,
    total_points INT NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE
);

-- Assignment submissions
CREATE TABLE assignment_submissions (
    submission_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    submission_date DATETIME NOT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    comments TEXT,
    points_earned DECIMAL(7,2) DEFAULT NULL,
    status ENUM('submitted', 'graded', 'late', 'missing') NOT NULL,
    FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Announcements table
CREATE TABLE announcements (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    published_date DATETIME NOT NULL,
    expiry_date DATETIME DEFAULT NULL,
    target_type ENUM('all', 'class', 'student', 'instructor', 'admin') NOT NULL,
    target_id VARCHAR(20) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATETIME NOT NULL,
    description VARCHAR(255) NOT NULL,
    payment_method ENUM('credit_card', 'bank_transfer', 'cash', 'check', 'other') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL,
    transaction_id VARCHAR(100) DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Insert sample users
INSERT INTO users (user_id, password, role, email, first_name, last_name)
VALUES 
('STU-202587', '$2y$10$Jjk5.5s6kGC3AHDJvr6vEOFO71uMwkV1nymS1rcW5T7l0jwcQTJSS', 'student', 'emily.johnson@student.idsc.edu', 'Emily', 'Johnson'),
('INS-2025103', '$2y$10$Jjk5.5s6kGC3AHDJvr6vEOFO71uMwkV1nymS1rcW5T7l0jwcQTJSS', 'instructor', 'robert.chen@faculty.idsc.edu', 'Robert', 'Chen'),
('ADM-2025001', '$2y$10$Jjk5.5s6kGC3AHDJvr6vEOFO71uMwkV1nymS1rcW5T7l0jwcQTJSS', 'admin', 'admin@idsc.edu', 'System', 'Administrator');
-- Note: The password is 'password123' hashed with bcrypt

-- Insert sample student
INSERT INTO students (student_id, program, year_level, gpa, credits_earned, credits_required, enrollment_date)
VALUES ('STU-202587', 'Computer Science', 3, 3.75, 75, 120, '2022-09-01');

-- Insert sample instructor
INSERT INTO instructors (instructor_id, department, position, hire_date)
VALUES ('INS-2025103', 'Mathematics', 'Associate Professor', '2020-01-15');

-- Insert sample administrator
INSERT INTO administrators (admin_id, department, position, hire_date)
VALUES ('ADM-2025001', 'School Administration', 'System Administrator', '2019-06-01');

-- Insert sample courses
INSERT INTO courses (course_id, course_name, department, description, credits)
VALUES 
('MATH-201', 'Calculus I', 'Mathematics', 'Introduction to differential and integral calculus.', 4),
('MATH-350', 'Mathematical Modeling', 'Mathematics', 'Applications of mathematics to real-world problems.', 3),
('STAT-315', 'Statistics for Data Science', 'Statistics', 'Statistical methods for analyzing data.', 3),
('CS-101', 'Introduction to Programming', 'Computer Science', 'Fundamentals of programming with Python.', 3),
('ENG-205', 'Technical Writing', 'English', 'Writing for technical and scientific audiences.', 3);

-- Insert sample classes
INSERT INTO classes (course_id, instructor_id, semester, year, start_date, end_date, status)
VALUES 
('MATH-201', 'INS-2025103', 'Spring', 2025, '2025-01-15', '2025-05-15', 'active'),
('MATH-350', 'INS-2025103', 'Spring', 2025, '2025-01-15', '2025-05-15', 'active'),
('STAT-315', 'INS-2025103', 'Spring', 2025, '2025-01-15', '2025-05-15', 'active'),
('CS-101', 'INS-2025103', 'Spring', 2025, '2025-01-15', '2025-05-15', 'active'),
('ENG-205', 'INS-2025103', 'Spring', 2025, '2025-01-15', '2025-05-15', 'active');

-- Insert sample class schedule
INSERT INTO class_schedule (class_id, day_of_week, start_time, end_time, room, building)
VALUES 
(1, 'Monday', '11:00:00', '12:30:00', '305', 'Science Building'),
(1, 'Wednesday', '11:00:00', '12:30:00', '305', 'Science Building'),
(2, 'Tuesday', '09:00:00', '10:30:00', '301', 'Science Building'),
(2, 'Thursday', '09:00:00', '10:30:00', '301', 'Science Building'),
(3, 'Monday', '14:00:00', '15:30:00', '210', 'Computer Science Building'),
(3, 'Wednesday', '14:00:00', '15:30:00', '210', 'Computer Science Building');

-- Insert sample enrollments
INSERT INTO enrollments (student_id, class_id, enrollment_date, status)
VALUES 
('STU-202587', 1, '2025-01-05', 'enrolled'),
('STU-202587', 2, '2025-01-05', 'enrolled'),
('STU-202587', 3, '2025-01-05', 'enrolled'),
('STU-202587', 4, '2025-01-05', 'enrolled'),
('STU-202587', 5, '2025-01-05', 'enrolled');

-- Insert sample assignments
INSERT INTO assignments (class_id, title, description, due_date, total_points, weight)
VALUES 
(1, 'Calculus Problem Set #4', 'Complete problems 1-20 in Chapter 4', '2025-04-15 23:59:59', 25, 10.00),
(3, 'Statistical Analysis Report', 'Analyze the provided dataset using statistical methods', '2025-04-17 23:59:59', 50, 15.00),
(2, 'Mathematical Model Project', 'Create a mathematical model for a real-world problem', '2025-04-18 23:59:59', 75, 20.00);

-- Insert sample announcements
INSERT INTO announcements (user_id, title, content, published_date, target_type)
VALUES 
('INS-2025103', 'Midterm Exam Schedule Posted', 'All courses - Check your course pages for specific dates and times.', '2025-04-10 10:00:00', 'all'),
('ADM-2025001', 'Library Hours Extended', 'The main library will be open until midnight during midterm week.', '2025-04-09 14:30:00', 'all'),
('ADM-2025001', 'Summer Registration Opens Soon', 'Registration for Summer 2025 courses begins next Monday.', '2025-04-08 09:15:00', 'all'); 