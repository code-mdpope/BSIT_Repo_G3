<?php
// This is a debug file to check database connection and login issues

// Include configuration
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Output header
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>IDSC Portal Login Debug</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        h1 { color: #2E7D32; }
        h2 { color: #0D47A1; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-top: 20px; }
        h3 { color: #5D4037; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .code { font-family: monospace; background: #f5f5f5; padding: 2px 4px; }
        ul { list-style-type: none; padding-left: 20px; }
        li { margin-bottom: 5px; }
        .container { max-width: 800px; margin: 0 auto; }
        .back-btn { display: inline-block; padding: 10px 15px; background-color: #2e7d32; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
<div class='container'>
    <h1>IDSC Portal Login Debug</h1>";

// Check database connection
echo "<h2>1. Database Connection Check</h2>";
try {
    $conn = get_db_connection();
    if ($conn) {
        echo "<p class='success'>✓ Database connection successful</p>";
        echo "<p>Connected to MySQL server: " . mysqli_get_host_info($conn) . "</p>";
        echo "<p>Server version: " . mysqli_get_server_info($conn) . "</p>";
    } else {
        echo "<p class='error'>✗ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Exception: " . $e->getMessage() . "</p>";
}

// Check if users table exists and has data
echo "<h2>2. Users Table Check</h2>";
try {
    $conn = get_db_connection();
    $query = "SELECT COUNT(*) as count FROM users";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<p class='success'>✓ Users table exists with {$row['count']} records</p>";
        
        // Check sample users
        $sample_users = [
            ['id' => 'STU-202587', 'role' => 'student'],
            ['id' => 'INS-2025103', 'role' => 'instructor'],
            ['id' => 'ADM-2025001', 'role' => 'admin']
        ];
        
        echo "<h3>Sample User Check:</h3>";
        echo "<ul>";
        
        foreach ($sample_users as $user) {
            $user_id = $user['id'];
            $role = $user['role'];
            
            $query = "SELECT * FROM users WHERE user_id = '$user_id' AND role = '$role'";
            $result = mysqli_query($conn, $query);
            
            if ($result && mysqli_num_rows($result) === 1) {
                $user_data = mysqli_fetch_assoc($result);
                echo "<li class='success'>✓ User {$user_id} ({$role}) exists</li>";
                
                // Analyze password hash
                $password_hash = $user_data['password'];
                $hash_info = password_get_info($password_hash);
                $is_bcrypt = (substr($password_hash, 0, 4) === '$2y$');
                
                echo "<li style='margin-left:20px;'>Password hash: <span class='code'>" . htmlspecialchars($password_hash) . "</span></li>";
                echo "<li style='margin-left:20px;'>Hash algorithm: " . ($is_bcrypt ? "<span class='success'>Bcrypt (correct)</span>" : "<span class='error'>Not Bcrypt (incorrect)</span>") . "</li>";
                
                // Test password verification
                if (password_verify('password123', $password_hash)) {
                    echo "<li style='margin-left:20px;' class='success'>✓ Password 'password123' is correct for {$user_id}</li>";
                } else {
                    echo "<li style='margin-left:20px;' class='error'>✗ Password 'password123' is NOT correct for {$user_id}</li>";
                    // Direct comparison for debugging
                    if ($password_hash === 'password123') {
                        echo "<li style='margin-left:40px;' class='warning'>⚠ The password is stored in plain text</li>";
                    }
                }
                
                // Show login URL for this user
                echo "<li style='margin-left:20px;'>Quick login: <a href='index.php?prefill={$user_id}&role={$role}'>Login as {$user_id}</a></li>";
            } else {
                echo "<li class='error'>✗ User {$user_id} ({$role}) NOT found</li>";
            }
        }
        
        echo "</ul>";
        
    } else {
        echo "<p class='error'>✗ Users table check failed: " . mysqli_error($conn) . "</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Exception: " . $e->getMessage() . "</p>";
}

// Test session functionality
echo "<h2>3. Session Functionality Check</h2>";
try {
    start_session_if_not_started();
    echo "<p class='success'>✓ Session started successfully</p>";
    echo "<p>Session ID: <span class='code'>" . session_id() . "</span></p>";
    echo "<p>Session save path: <span class='code'>" . session_save_path() . "</span></p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Session exception: " . $e->getMessage() . "</p>";
}

// Test password hashing
echo "<h2>4. Password Hashing Test</h2>";
try {
    $test_password = 'password123';
    $hashed = password_hash($test_password, PASSWORD_BCRYPT);
    
    echo "<p class='success'>✓ Password hashing is working</p>";
    echo "<p>Test password: <span class='code'>{$test_password}</span></p>";
    echo "<p>Generated hash: <span class='code'>{$hashed}</span></p>";
    
    if (password_verify($test_password, $hashed)) {
        echo "<p class='success'>✓ Password verification is working correctly</p>";
    } else {
        echo "<p class='error'>✗ Password verification failed</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Password hashing exception: " . $e->getMessage() . "</p>";
}

// Display system information
echo "<h2>5. System Information</h2>";
echo "<ul>";
echo "<li>PHP Version: <span class='code'>" . phpversion() . "</span></li>";
echo "<li>Server: <span class='code'>" . $_SERVER['SERVER_SOFTWARE'] . "</span></li>";
echo "<li>Document Root: <span class='code'>" . $_SERVER['DOCUMENT_ROOT'] . "</span></li>";
echo "<li>Script Path: <span class='code'>" . __FILE__ . "</span></li>";
echo "<li>Password Hashing Functions:</li>";
echo "<ul>";
echo "<li>password_hash: " . (function_exists('password_hash') ? "<span class='success'>Available</span>" : "<span class='error'>Not available</span>") . "</li>";
echo "<li>password_verify: " . (function_exists('password_verify') ? "<span class='success'>Available</span>" : "<span class='error'>Not available</span>") . "</li>";
echo "<li>password_get_info: " . (function_exists('password_get_info') ? "<span class='success'>Available</span>" : "<span class='error'>Not available</span>") . "</li>";
echo "</ul>";
echo "</ul>";

// Display Next Steps
echo "<h2>6. Next Steps</h2>";
echo "<p>Based on the results above:</p>";
echo "<ul>";
echo "<li>If password hashing is correct but verification fails, try running the <a href='fix_passwords.php'>Fix Passwords tool</a></li>";
echo "<li>If database connection fails, check your XAMPP configuration</li>";
echo "<li>If users are missing, run the <a href='setup.php'>Setup Database tool</a></li>";
echo "</ul>";

// Back to login page
echo "<p><a href='index.php' class='back-btn'>Back to Login Page</a> <a href='fix_passwords.php' class='back-btn' style='background-color: #0d47a1;'>Fix Password Issues</a></p>";
echo "</div></body></html>";
?>