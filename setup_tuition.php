<?php
require_once 'config.php';

// Initialize output
$output = [];

// Function to run SQL and report results
function run_sql($conn, $sql, $description) {
    global $output;
    $output[] = "Executing: $description... ";
    
    if (mysqli_query($conn, $sql)) {
        $output[] = "Success!";
        return true;
    } else {
        $output[] = "Failed: " . mysqli_error($conn);
        return false;
    }
}

// Process setup if requested
if (isset($_POST['setup'])) {
    // Get database connection
    $conn = get_db_connection();
    
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
            $output[] = "Column tuition_id already exists in payments table.";
        }
    } else {
        $output[] = "Payments table does not exist. Please create the payments table first.";
    }
    
    // Add sample tuition settings data if table is empty
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM tuition_settings");
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] == 0) {
        $output[] = "Adding sample tuition settings data...";
        
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
    
    $output[] = "Database setup completed!";
}

// Create test tuition record if requested
if (isset($_POST['create_test_record'])) {
    $conn = get_db_connection();
    
    // Get a student and tuition settings
    $student_query = "SELECT user_id FROM users WHERE role = 'student' LIMIT 1";
    $result = mysqli_query($conn, $student_query);
    if ($result && mysqli_num_rows($result) > 0) {
        $student = mysqli_fetch_assoc($result);
        $student_id = $student['user_id'];

        $settings_query = "SELECT * FROM tuition_settings ORDER BY academic_year DESC, semester DESC LIMIT 1";
        $result = mysqli_query($conn, $settings_query);
        if ($result && mysqli_num_rows($result) > 0) {
            $settings = mysqli_fetch_assoc($result);
            
            // Calculate amounts
            $credits = 15; // Assume 15 credits
            $tuition_amount = $credits * $settings['tuition_per_credit'];
            $fees_amount = $settings['registration_fee'] + $settings['technology_fee'] + 
                           $settings['activity_fee'] + $settings['health_fee'];
            $discounts_amount = ($tuition_amount * ($settings['discount_full_time'] / 100));
            $total_amount = $tuition_amount + $fees_amount - $discounts_amount;
            $due_date = date('Y-m-d', strtotime('+30 days'));
            
            // Create tuition record
            $sql = "INSERT INTO student_tuition
                    (student_id, academic_year, semester, tuition_amount, fees_amount, 
                     discounts_amount, penalties_amount, total_amount, amount_paid, 
                     balance, status, due_date, created_at, updated_at)
                    VALUES
                    ('$student_id', {$settings['academic_year']}, '{$settings['semester']}', $tuition_amount, $fees_amount,
                     $discounts_amount, 0, $total_amount, 0,
                     $total_amount, 'pending', '$due_date', NOW(), NOW())";
            
            if (mysqli_query($conn, $sql)) {
                $output[] = "Test tuition record created successfully for student ID: $student_id.";
                $output[] = "Total Amount: $" . number_format($total_amount, 2);
            } else {
                $output[] = "Failed to create test record: " . mysqli_error($conn);
            }
        } else {
            $output[] = "No tuition settings found. Please run setup first.";
        }
    } else {
        $output[] = "No students found in the database.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tuition System Setup</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:'#2E7D32',secondary:'#43A047'},borderRadius:{'none':'0px','sm':'4px',DEFAULT:'8px','md':'12px','lg':'16px','xl':'20px','2xl':'24px','3xl':'32px','full':'9999px','button':'8px'}}}}</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8faf8;
            min-height: 100vh;
        }
    </style>
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-lg border shadow-sm p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Tuition System Setup</h1>
            <p class="text-gray-600 mb-6">This utility will set up the necessary database tables for the tuition management system.</p>
            
            <div class="flex space-x-4">
                <form method="POST" action="">
                    <button type="submit" name="setup" class="px-4 py-2 bg-primary text-white rounded hover:bg-primary/90 transition">
                        Run Database Setup
                    </button>
                </form>
                
                <form method="POST" action="">
                    <button type="submit" name="create_test_record" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                        Create Test Tuition Record
                    </button>
                </form>
                
                <a href="admin/tuition_calculator.php" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition flex items-center">
                    Go to Tuition Calculator
                </a>
            </div>
        </div>
        
        <?php if (!empty($output)): ?>
            <div class="bg-white rounded-lg border shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Setup Output</h2>
                <div class="bg-gray-50 p-4 rounded border">
                    <?php foreach ($output as $line): ?>
                        <div class="py-1"><?php echo htmlspecialchars($line); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 