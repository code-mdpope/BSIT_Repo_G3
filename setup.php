<?php
// Database setup script for IDSC Portal

// Database configuration
$db_host = 'localhost';
$db_user = 'root'; 
$db_pass = '';
$db_name = 'idsc_portal';

// Function to output messages
function output_message($message, $is_error = false) {
    echo '<div style="padding: 10px; margin: 10px 0; border-radius: 5px; ' . 
         ($is_error ? 'background-color: #ffebee; color: #c62828;' : 'background-color: #e8f5e9; color: #2e7d32;') . 
         '">' . $message . '</div>';
}

// Connect to MySQL (without selecting a database)
$conn = mysqli_connect($db_host, $db_user, $db_pass);

// Check connection
if (!$conn) {
    output_message("Connection failed: " . mysqli_connect_error(), true);
    exit;
}

output_message("Connected to MySQL server successfully.");

// Read the SQL file
$sql_file = file_get_contents('db_setup.sql');

if (!$sql_file) {
    output_message("Error reading the SQL file.", true);
    mysqli_close($conn);
    exit;
}

// Split SQL by semicolon to get individual queries
$queries = explode(';', $sql_file);

// Execute each query
foreach ($queries as $query) {
    $query = trim($query);
    
    // Skip empty queries
    if (empty($query)) {
        continue;
    }
    
    if (mysqli_query($conn, $query)) {
        // If the query creates the database, select it for subsequent queries
        if (strpos($query, 'CREATE DATABASE') !== false) {
            mysqli_select_db($conn, $db_name);
            output_message("Database '$db_name' created and selected.");
        }
    } else {
        output_message("Error executing query: " . mysqli_error($conn), true);
    }
}

output_message("Database setup completed successfully!");

// Verify tables were created
mysqli_select_db($conn, $db_name);
$result = mysqli_query($conn, "SHOW TABLES");

if (!$result) {
    output_message("Error checking tables: " . mysqli_error($conn), true);
} else {
    echo "<h3>Tables created:</h3>";
    echo "<ul>";
    while ($row = mysqli_fetch_row($result)) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
}

// Verify sample data was inserted
$tables_to_check = [
    'users' => 'User accounts',
    'students' => 'Student records',
    'instructors' => 'Instructor records',
    'administrators' => 'Administrator records',
    'courses' => 'Course records',
    'classes' => 'Class instances',
    'enrollments' => 'Student enrollments',
    'assignments' => 'Course assignments'
];

echo "<h3>Sample data verification:</h3>";
echo "<ul>";

foreach ($tables_to_check as $table => $description) {
    $count_result = mysqli_query($conn, "SELECT COUNT(*) FROM $table");
    if ($count_result) {
        $count = mysqli_fetch_row($count_result)[0];
        echo "<li>$description: $count records</li>";
    } else {
        echo "<li>$description: Error checking records</li>";
    }
}

echo "</ul>";

// Output credentials for testing
echo "<div style='margin-top: 20px; padding: 15px; border: 1px solid #2e7d32; border-radius: 5px;'>";
echo "<h3 style='margin-top: 0; color: #2e7d32;'>Test Account Credentials:</h3>";
echo "<p><strong>All accounts use password:</strong> password123</p>";
echo "<ul>";
echo "<li><strong>Student:</strong> STU-202587</li>";
echo "<li><strong>Instructor:</strong> INS-2025103</li>";
echo "<li><strong>Admin:</strong> ADM-2025001</li>";
echo "</ul>";
echo "</div>";

echo "<p style='margin-top: 20px;'><a href='index.php' style='display: inline-block; padding: 10px 15px; background-color: #2e7d32; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";

// Close connection
mysqli_close($conn);
?> 