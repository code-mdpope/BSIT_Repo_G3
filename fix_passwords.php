<?php
// This script is for fixing password issues in the database
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add some basic styling
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>IDSC Portal Password Fix Utility</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        h1 { color: #2E7D32; }
        h2 { color: #0D47A1; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
        button { padding: 10px 15px; background-color: #2e7d32; color: white; border: none; border-radius: 5px; margin-right: 10px; cursor: pointer; }
        button.blue { background-color: #0d47a1; }
        a.button { display: inline-block; padding: 10px 15px; background-color: #2e7d32; color: white; text-decoration: none; border-radius: 5px; }
        a.blue { background-color: #0d47a1; }
        .container { max-width: 800px; margin: 0 auto; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>IDSC Portal Password Fix Utility</h1>";

// Function to check user passwords
function check_user_passwords() {
    $conn = get_db_connection();
    $needs_fixing = 0;
    $already_correct = 0;
    
    // Get all users
    $query = "SELECT user_id, password, role FROM users";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        echo "<p class='error'>Error querying users: " . mysqli_error($conn) . "</p>";
        return;
    }
    
    echo "<h2>User Password Status:</h2>";
    echo "<ul>";
    
    while ($user = mysqli_fetch_assoc($result)) {
        $user_id = $user['user_id'];
        $current_password = $user['password'];
        $role = $user['role'];
        
        // Check if password is properly hashed
        $is_bcrypt = (substr($current_password, 0, 4) === '$2y$');
        
        if ($is_bcrypt && password_verify('password123', $current_password)) {
            echo "<li><span class='success'>✓</span> User {$user_id} ({$role}): Password is properly hashed and verified</li>";
            $already_correct++;
        } else {
            if ($is_bcrypt) {
                echo "<li><span class='warning'>⚠</span> User {$user_id} ({$role}): Password is hashed but 'password123' doesn't verify</li>";
            } else {
                echo "<li><span class='error'>✗</span> User {$user_id} ({$role}): Password is not properly hashed</li>";
            }
            $needs_fixing++;
        }
    }
    
    echo "</ul>";
    
    if ($needs_fixing > 0) {
        echo "<p><span class='error'>{$needs_fixing} accounts need password fixing</span> and {$already_correct} are already correct.</p>";
        echo "<p>Please use one of the fix options below to correct these issues.</p>";
    } else {
        echo "<p><span class='success'>Great! All {$already_correct} user accounts have properly hashed passwords.</span></p>";
    }
}

// Function to update user passwords
function update_user_passwords() {
    $conn = get_db_connection();
    $updated = 0;
    $failed = 0;
    $skipped = 0;
    
    // Get all users
    $query = "SELECT user_id, password, role FROM users";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        echo "<p class='error'>Error querying users: " . mysqli_error($conn) . "</p>";
        return;
    }
    
    echo "<h2>Updating All User Passwords:</h2>";
    echo "<ul>";
    
    while ($user = mysqli_fetch_assoc($result)) {
        $user_id = $user['user_id'];
        $current_password = $user['password'];
        $role = $user['role'];
        
        // Check if password is already properly hashed and verifies
        if (substr($current_password, 0, 4) === '$2y$' && password_verify('password123', $current_password)) {
            echo "<li><span class='success'>✓</span> User {$user_id} ({$role}): Password is already correct - SKIPPED</li>";
            $skipped++;
            continue;
        }
        
        // Update password with hashed version of 'password123'
        $new_password = password_hash('password123', PASSWORD_BCRYPT);
        $update_query = "UPDATE users SET password = '$new_password' WHERE user_id = '$user_id'";
        
        if (mysqli_query($conn, $update_query)) {
            echo "<li><span class='success'>✓</span> User {$user_id} ({$role}): Password updated successfully</li>";
            
            // Verify the update worked
            $verify_query = "SELECT password FROM users WHERE user_id = '$user_id'";
            $verify_result = mysqli_query($conn, $verify_query);
            if ($verify_result && mysqli_num_rows($verify_result) === 1) {
                $updated_user = mysqli_fetch_assoc($verify_result);
                if (password_verify('password123', $updated_user['password'])) {
                    echo "<li style='margin-left:20px;'><span class='success'>✓</span> Verified password works for {$user_id}</li>";
                } else {
                    echo "<li style='margin-left:20px;'><span class='error'>✗</span> Password verification failed for {$user_id}</li>";
                }
            }
            
            $updated++;
        } else {
            echo "<li><span class='error'>✗</span> User {$user_id} ({$role}): Failed to update password - " . mysqli_error($conn) . "</li>";
            $failed++;
        }
    }
    
    echo "</ul>";
    echo "<h2>Summary:</h2>";
    echo "<p>{$updated} passwords updated, {$skipped} already correct, {$failed} failed</p>";
}

// Function to directly set passwords for sample accounts
function fix_sample_accounts() {
    $conn = get_db_connection();
    $sample_accounts = [
        'STU-202587' => ['role' => 'student', 'password' => 'password123'],
        'INS-2025103' => ['role' => 'instructor', 'password' => 'password123'],
        'ADM-2025001' => ['role' => 'admin', 'password' => 'password123']
    ];
    
    echo "<h2>Fixing Sample Accounts:</h2>";
    echo "<ul>";
    
    foreach ($sample_accounts as $user_id => $details) {
        // Get current user data
        $check_query = "SELECT password FROM users WHERE user_id = '$user_id'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (!$check_result || mysqli_num_rows($check_result) === 0) {
            echo "<li><span class='error'>✗</span> Sample account {$user_id} not found in database</li>";
            continue;
        }
        
        $hashed_password = password_hash($details['password'], PASSWORD_BCRYPT);
        $update_query = "UPDATE users SET password = '$hashed_password' WHERE user_id = '$user_id'";
        
        if (mysqli_query($conn, $update_query)) {
            echo "<li><span class='success'>✓</span> Sample account {$user_id} ({$details['role']}): Password updated successfully</li>";
            
            // Verify password works
            $query = "SELECT * FROM users WHERE user_id = '$user_id'";
            $result = mysqli_query($conn, $query);
            if ($result && mysqli_num_rows($result) === 1) {
                $user = mysqli_fetch_assoc($result);
                if (password_verify($details['password'], $user['password'])) {
                    echo "<li style='margin-left:20px;'><span class='success'>✓</span> Verified password works for {$user_id}</li>";
                } else {
                    echo "<li style='margin-left:20px;'><span class='error'>✗</span> Password verification failed for {$user_id}</li>";
                }
            }
        } else {
            echo "<li><span class='error'>✗</span> Sample account {$user_id}: Failed to update password - " . mysqli_error($conn) . "</li>";
        }
    }
    
    echo "</ul>";
    echo "<p>Sample accounts have been updated with the password 'password123'.</p>";
    echo "<p>You should now be able to login with the following credentials:</p>";
    echo "<ul>";
    echo "<li>Student: STU-202587 / password123</li>";
    echo "<li>Instructor: INS-2025103 / password123</li>";
    echo "<li>Admin: ADM-2025001 / password123</li>";
    echo "</ul>";
}

// Check if the form was submitted to run the fixes
if (isset($_POST['check_passwords'])) {
    check_user_passwords();
    show_buttons();
} elseif (isset($_POST['fix_passwords'])) {
    update_user_passwords();
    show_buttons();
} elseif (isset($_POST['fix_sample_accounts'])) {
    fix_sample_accounts();
    show_buttons();
} else {
    // Display the options
    echo "<p>This utility helps identify and fix password issues in the database.</p>";
    echo "<p>If you are having trouble logging in with the sample accounts, this tool can help.</p>";
    
    show_buttons();
}

// Function to show action buttons
function show_buttons() {
    echo "<form method='POST'>";
    echo "<div style='margin: 20px 0;'>";
    echo "<button type='submit' name='check_passwords'>Check Password Status</button>";
    echo "<button type='submit' name='fix_passwords'>Fix All User Passwords</button>";
    echo "<button type='submit' name='fix_sample_accounts' class='blue'>Fix Only Sample Accounts</button>";
    echo "</div>";
    echo "</form>";
    
    // Back to login page and diagnostic links
    echo "<p>";
    echo "<a href='index.php' class='button'>Back to Login Page</a> ";
    echo "<a href='login_debug.php' class='button blue'>Run Diagnostics</a>";
    echo "</p>";
}

echo "</div></body></html>";
?> 