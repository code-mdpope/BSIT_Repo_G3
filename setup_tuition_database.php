<?php
require_once 'config.php';

// Get database connection
$conn = get_db_connection();

// Function to run SQL and report results
function run_sql($conn, $sql, $description) {
    echo "Executing: $description... ";
    
    if (mysqli_query($conn, $sql)) {
        echo "Success!\n";
        return true;
    } else {
        echo "Failed: " . mysqli_error($conn) . "\n";
        return false;
    }
}

// Create Tuition Settings table
$sql = "CREATE TABLE IF NOT EXISTS tuition_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year INT NOT NULL,
    semester VARCHAR(20) NOT NULL,
    tuition_per_credit DECIMAL(10, 2) NOT NULL,
    registration_fee DECIMAL(10, 2) NOT NULL,
    technology_fee DECIMAL(10, 2) NOT NULL,
    activity_fee DECIMAL(10, 2) NOT NULL,
    health_fee DECIMAL(10, 2) NOT NULL,
    discount_full_time DECIMAL(5, 2) NOT NULL,
    discount_early_payment DECIMAL(5, 2) NOT NULL,
    late_payment_penalty DECIMAL(10, 2) NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY unique_term (academic_year, semester)
)";
run_sql($conn, $sql, "Creating tuition_settings table");

// Create Student Tuition Records table
$sql = "CREATE TABLE IF NOT EXISTS student_tuition (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    academic_year INT NOT NULL,
    semester VARCHAR(20) NOT NULL,
    tuition_amount DECIMAL(10, 2) NOT NULL,
    fees_amount DECIMAL(10, 2) NOT NULL,
    discounts_amount DECIMAL(10, 2) NOT NULL,
    penalties_amount DECIMAL(10, 2) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL DEFAULT 0,
    balance DECIMAL(10, 2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    due_date DATE NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (student_id) REFERENCES users(user_id)
)";
run_sql($conn, $sql, "Creating student_tuition table");

// Check if payments table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
$tableExists = mysqli_num_rows($result) > 0;

// If payments table exists, add tuition_id column if it doesn't exist
if ($tableExists) {
    // Check if tuition_id column exists
    $result = mysqli_query($conn, "SHOW COLUMNS FROM payments LIKE 'tuition_id'");
    $columnExists = mysqli_num_rows($result) > 0;
    
    if (!$columnExists) {
        $sql = "ALTER TABLE payments ADD COLUMN tuition_id INT NULL";
        run_sql($conn, $sql, "Adding tuition_id column to payments table");
        
        $sql = "ALTER TABLE payments ADD FOREIGN KEY (tuition_id) REFERENCES student_tuition(id)";
        run_sql($conn, $sql, "Adding foreign key constraint");
    } else {
        echo "Column tuition_id already exists in payments table.\n";
    }
} else {
    echo "Payments table does not exist. Please create the payments table first.\n";
}

// Add sample tuition settings data if table is empty
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM tuition_settings");
$row = mysqli_fetch_assoc($result);

if ($row['count'] == 0) {
    echo "Adding sample tuition settings data...\n";
    
    $sample_data = [
        [2025, 'Spring', 350.00, 150.00, 200.00, 100.00, 75.00, 5.00, 3.00, 10.00],
        [2024, 'Fall', 335.00, 145.00, 190.00, 95.00, 70.00, 5.00, 3.00, 10.00],
        [2024, 'Summer', 335.00, 145.00, 190.00, 95.00, 70.00, 5.00, 3.00, 10.00],
        [2024, 'Spring', 335.00, 145.00, 190.00, 95.00, 70.00, 5.00, 3.00, 10.00]
    ];
    
    foreach ($sample_data as $data) {
        $sql = "INSERT INTO tuition_settings 
                (academic_year, semester, tuition_per_credit, registration_fee, technology_fee, 
                 activity_fee, health_fee, discount_full_time, discount_early_payment, late_payment_penalty, updated_at)
                VALUES
                ({$data[0]}, '{$data[1]}', {$data[2]}, {$data[3]}, {$data[4]}, 
                 {$data[5]}, {$data[6]}, {$data[7]}, {$data[8]}, {$data[9]}, NOW())";
        
        run_sql($conn, $sql, "Adding {$data[1]} {$data[0]} tuition settings");
    }
}

echo "\nDatabase setup completed!\n";
?> 