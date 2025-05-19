<?php
require_once 'config.php';

// Functions for getting dashboard data

// Get enrolled courses for a student
function get_student_courses($student_id) {
    $conn = get_db_connection();
    $student_id = mysqli_real_escape_string($conn, $student_id);
    
    $query = "SELECT c.course_id, c.course_name, c.department, c.credits, 
              cl.class_id, cl.semester, cl.year, cl.status,
              u.first_name, u.last_name, u.user_id as instructor_id
              FROM enrollments e
              JOIN classes cl ON e.class_id = cl.class_id
              JOIN courses c ON cl.course_id = c.course_id
              JOIN users u ON cl.instructor_id = u.user_id
              WHERE e.student_id = '$student_id' AND e.status = 'enrolled'
              ORDER BY cl.semester, c.course_name";
    
    $result = mysqli_query($conn, $query);
    $courses = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $courses[] = $row;
        }
    }
    
    return $courses;
}

// Get today's schedule for a student
function get_student_today_schedule($student_id) {
    $conn = get_db_connection();
    $student_id = mysqli_real_escape_string($conn, $student_id);
    
    // Get current day of week
    $day_of_week = date('l');
    
    $query = "SELECT c.course_id, c.course_name, c.department,
              cl.class_id, cs.day_of_week, cs.start_time, cs.end_time, 
              cs.room, cs.building,
              u.first_name, u.last_name, u.user_id as instructor_id
              FROM enrollments e
              JOIN classes cl ON e.class_id = cl.class_id
              JOIN class_schedule cs ON cl.class_id = cs.class_id
              JOIN courses c ON cl.course_id = c.course_id
              JOIN users u ON cl.instructor_id = u.user_id
              WHERE e.student_id = '$student_id' 
              AND e.status = 'enrolled'
              AND cs.day_of_week = '$day_of_week'
              ORDER BY cs.start_time";
    
    $result = mysqli_query($conn, $query);
    $schedule = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $schedule[] = $row;
        }
    }
    
    return $schedule;
}

// Get upcoming assignments for a student
function get_student_upcoming_assignments($student_id, $limit = 3) {
    $conn = get_db_connection();
    $student_id = mysqli_real_escape_string($conn, $student_id);
    $limit = (int)$limit;
    
    $query = "SELECT a.assignment_id, a.title, a.description, a.due_date, a.total_points,
              c.course_id, c.course_name,
              u.first_name, u.last_name, u.user_id as instructor_id,
              IFNULL(s.submission_id, 0) as submission_id,
              IFNULL(s.status, 'missing') as submission_status
              FROM assignments a
              JOIN classes cl ON a.class_id = cl.class_id
              JOIN courses c ON cl.course_id = c.course_id
              JOIN users u ON cl.instructor_id = u.user_id
              JOIN enrollments e ON cl.class_id = e.class_id AND e.student_id = '$student_id'
              LEFT JOIN assignment_submissions s ON a.assignment_id = s.assignment_id AND s.student_id = '$student_id'
              WHERE e.status = 'enrolled'
              AND a.due_date >= NOW()
              ORDER BY a.due_date ASC
              LIMIT $limit";
    
    $result = mysqli_query($conn, $query);
    $assignments = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $assignments[] = $row;
        }
    }
    
    return $assignments;
}

// Get recent announcements
function get_recent_announcements($limit = 3) {
    $conn = get_db_connection();
    $limit = (int)$limit;
    
    $query = "SELECT a.announcement_id, a.title, a.content, a.published_date,
              u.first_name, u.last_name, u.role
              FROM announcements a
              JOIN users u ON a.user_id = u.user_id
              WHERE (a.target_type = 'all' 
              OR (a.target_type = 'student' AND a.target_id IS NULL)
              OR (a.expiry_date IS NULL OR a.expiry_date >= NOW()))
              ORDER BY a.published_date DESC
              LIMIT $limit";
    
    $result = mysqli_query($conn, $query);
    $announcements = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $announcements[] = $row;
        }
    }
    
    return $announcements;
}

// Get student academic progress
function get_student_academic_progress($student_id) {
    $conn = get_db_connection();
    $student_id = mysqli_real_escape_string($conn, $student_id);
    
    $query = "SELECT s.gpa, s.credits_earned, s.credits_required, s.program, s.year_level
              FROM students s
              WHERE s.student_id = '$student_id'";
    
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

// Get student grades by course
function get_student_current_grades($student_id) {
    $conn = get_db_connection();
    $student_id = mysqli_real_escape_string($conn, $student_id);
    
    $query = "SELECT c.course_id, c.course_name, 
              IFNULL(e.grade, 'N/A') as grade
              FROM enrollments e
              JOIN classes cl ON e.class_id = cl.class_id
              JOIN courses c ON cl.course_id = c.course_id
              WHERE e.student_id = '$student_id' 
              AND e.status = 'enrolled'
              ORDER BY c.course_name";
    
    $result = mysqli_query($conn, $query);
    $grades = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $grades[] = $row;
        }
    }
    
    return $grades;
}

// Format date for display
function format_date($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

// Format time for display
function format_time($time, $format = 'g:i A') {
    return date($format, strtotime($time));
}

// Calculate days remaining until due date
function days_until($date) {
    $due_date = new DateTime($date);
    $now = new DateTime();
    $interval = $now->diff($due_date);
    return $interval->days;
}

// Helper function to determine due date label
function get_due_date_label($due_date) {
    $days = days_until($due_date);
    
    if ($days === 0) {
        return ['Due Today', 'bg-red-100 text-red-600'];
    } elseif ($days === 1) {
        return ['Due Tomorrow', 'bg-red-100 text-red-600'];
    } elseif ($days <= 3) {
        return ['Due in ' . $days . ' days', 'bg-amber-100 text-amber-600'];
    } else {
        return ['Due in ' . $days . ' days', 'bg-blue-100 text-blue-600'];
    }
} 