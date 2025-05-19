<?php
require_once 'config.php';

// Process login form
function process_login($user_id, $password, $role) {
    $conn = get_db_connection();
    
    // Sanitize inputs
    $user_id = mysqli_real_escape_string($conn, sanitize_input($user_id));
    $role = mysqli_real_escape_string($conn, sanitize_input($role));
    
    // Check if user exists with the specified role
    $query = "SELECT * FROM users WHERE user_id = '$user_id' AND role = '$role'";
    $result = mysqli_query($conn, $query);
    
    // For development - log query
    error_log("Login attempt - Query: $query");
    
    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        // For development - log password verification attempt
        error_log("Login attempt - User found, verifying password for: $user_id");
        
        // Check if the password hash format is correct (should start with $2y$ for bcrypt)
        if (substr($user['password'], 0, 4) !== '$2y$') {
            error_log("Login failed - Password hash is not in bcrypt format for user: $user_id");
            return [
                'success' => false,
                'error' => 'password_format',
                'message' => 'The stored password is not in the correct format. Please use the password fix tool.'
            ];
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Start session
            start_session_if_not_started();
            
            // Store user information in session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            
            // Update last login time
            update_last_login($user['user_id']);
            
            error_log("Login success for user: $user_id with role: $role");
            
            return [
                'success' => true
            ];
        } else {
            // Password verification failed
            error_log("Login failed - Password verification failed for user: $user_id");
            
            // Special testing case for hashed vs non-hashed passwords
            if ($password == 'password123' && substr($user['password'], 0, 4) !== '$2y$') {
                error_log("Password issue detected - password may not be properly hashed");
                return [
                    'success' => false,
                    'error' => 'password_hash_issue',
                    'message' => 'Password issue detected. Please use the password fix tool.'
                ];
            }
            
            return [
                'success' => false,
                'error' => 'invalid_password',
                'message' => 'The password you entered is incorrect.'
            ];
        }
    } else {
        // User not found
        error_log("Login failed - User not found: $user_id with role: $role");
        return [
            'success' => false,
            'error' => 'user_not_found',
            'message' => 'User not found with the specified role.'
        ];
    }
}

// Alternative login method for testing - uses direct comparison (non-hashed password)
function process_login_direct($user_id, $password, $role) {
    $conn = get_db_connection();
    
    // Sanitize inputs
    $user_id = mysqli_real_escape_string($conn, sanitize_input($user_id));
    $role = mysqli_real_escape_string($conn, sanitize_input($role));
    $password = mysqli_real_escape_string($conn, sanitize_input($password));
    
    // Check if user exists with the specified role and password directly
    $query = "SELECT * FROM users WHERE user_id = '$user_id' AND role = '$role' AND password = '$password'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Start session
        start_session_if_not_started();
        
        // Store user information in session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        
        // Update last login time
        update_last_login($user['user_id']);
        
        return [
            'success' => true,
            'message' => 'Logged in using direct password comparison (not secure).'
        ];
    }
    
    return [
        'success' => false,
        'error' => 'direct_comparison_failed',
        'message' => 'Direct password comparison failed.'
    ];
}

// Process logout
function process_logout() {
    start_session_if_not_started();
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize_input($_POST['action']);
    
    if ($action === 'login') {
        $user_id = isset($_POST['userid']) ? $_POST['userid'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $role = isset($_POST['role']) ? $_POST['role'] : '';
        
        // Log login attempt
        error_log("Login attempt - User ID: $user_id, Role: $role");
        
        // Validate input
        $errors = [];
        
        if (empty($user_id)) {
            $errors[] = 'User ID is required';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        }
        
        if (empty($role) || !in_array($role, ['student', 'instructor', 'admin'])) {
            $errors[] = 'Please select a valid role';
        }
        
        // Process login if no errors
        if (empty($errors)) {
            // First try with standard password verification
            $login_result = process_login($user_id, $password, $role);
            
            if ($login_result['success']) {
                redirect_to_dashboard($role);
            } 
            // If that fails and we're using a test account with password123, try direct comparison as fallback
            else if ($password === 'password123' && in_array($user_id, ['STU-202587', 'INS-2025103', 'ADM-2025001'])) {
                error_log("Trying direct login comparison for test account: $user_id");
                $direct_result = process_login_direct($user_id, $password, $role);
                
                if ($direct_result['success']) {
                    redirect_to_dashboard($role);
                } else {
                    $error_message = 'Invalid credentials. Please try again. (Direct comparison also failed)';
                    
                    // Add diagnostic information
                    $error_message .= '<br><small class="text-amber-600">Please use the <a href="fix_passwords.php" class="underline">password fix tool</a></small>';
                }
            } else {
                // Use error message from the result if available
                $error_message = isset($login_result['message']) ? $login_result['message'] : 'Invalid credentials. Please try again.';
                
                // Add a hint for password tool if it's a password issue
                if (isset($login_result['error']) && ($login_result['error'] === 'password_format' || $login_result['error'] === 'password_hash_issue')) {
                    $error_message .= '<br><small class="text-amber-600">Please use the <a href="fix_passwords.php" class="underline">password fix tool</a></small>';
                }
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    } elseif ($action === 'logout') {
        process_logout();
        header('Location: index.php');
        exit();
    }
}
?> 