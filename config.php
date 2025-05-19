<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'idsc_portal');

// Establish database connection
function get_db_connection() {
    static $conn;
    if (!isset($conn)) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn) {
            die('Database Connection Failed: ' . mysqli_connect_error());
        }
    }
    return $conn;
}

// Start session if not already started
function start_session_if_not_started() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in
function is_user_logged_in() {
    start_session_if_not_started();
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Check if user has specific role
function user_has_role($role) {
    start_session_if_not_started();
    return is_user_logged_in() && $_SESSION['role'] === $role;
}

// Redirect to login if not logged in
function redirect_if_not_logged_in() {
    if (!is_user_logged_in()) {
        header('Location: index.php');
        exit();
    }
}

// Redirect based on role
function redirect_to_dashboard($role) {
    switch ($role) {
        case 'student':
            header('Location: student/dashboard.php');
            break;
        case 'instructor':
            header('Location: instructor/dashboard.php');
            break;
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        default:
            header('Location: index.php');
            break;
    }
    exit();
}

// Sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Display error message
function display_error($message) {
    return "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>
                <span class='block sm:inline'>$message</span>
            </div>";
}

// Display success message
function display_success($message) {
    return "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>
                <span class='block sm:inline'>$message</span>
            </div>";
}

// Get user information from database
function get_user_info($user_id) {
    $conn = get_db_connection();
    $user_id = mysqli_real_escape_string($conn, $user_id);
    
    $query = "SELECT * FROM users WHERE user_id = '$user_id'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

// Get role-specific information
function get_role_info($user_id, $role) {
    $conn = get_db_connection();
    $user_id = mysqli_real_escape_string($conn, $user_id);
    
    $table = '';
    $id_field = '';
    
    switch ($role) {
        case 'student':
            $table = 'students';
            $id_field = 'student_id';
            break;
        case 'instructor':
            $table = 'instructors';
            $id_field = 'instructor_id';
            break;
        case 'admin':
            $table = 'administrators';
            $id_field = 'admin_id';
            break;
        default:
            return null;
    }
    
    $query = "SELECT * FROM $table WHERE $id_field = '$user_id'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

// Update last login time
function update_last_login($user_id) {
    $conn = get_db_connection();
    $user_id = mysqli_real_escape_string($conn, $user_id);
    
    $query = "UPDATE users SET last_login = NOW() WHERE user_id = '$user_id'";
    mysqli_query($conn, $query);
}
?> 