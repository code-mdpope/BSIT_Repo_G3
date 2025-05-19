<?php
require_once 'config.php';
require_once 'dashboard_functions.php';

// This script updates the student_tuition data in the payment pages
// It can be run manually or scheduled to run periodically

// Get database connection
$conn = get_db_connection();

// Function to log messages
function log_message($message) {
    echo date('[Y-m-d H:i:s]') . " $message\n";
}

// Start the update process
log_message("Starting update process");

// Get all student tuition records that need to be synced
$query = "SELECT 
            st.id, st.student_id, st.academic_year, st.semester, 
            st.total_amount, st.amount_paid, st.balance, st.status, st.due_date
          FROM student_tuition st 
          WHERE st.status IN ('pending', 'partial')";
$result = mysqli_query($conn, $query);

if (!$result) {
    log_message("Error fetching tuition records: " . mysqli_error($conn));
    exit;
}

$tuition_records = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tuition_records[] = $row;
}

log_message("Found " . count($tuition_records) . " tuition records to process");

// Process each tuition record
foreach ($tuition_records as $record) {
    $tuition_id = $record['id'];
    $student_id = $record['student_id'];
    
    // Get total payments for this tuition record
    $query = "SELECT COALESCE(SUM(amount), 0) as total_paid 
              FROM payments 
              WHERE student_id = '$student_id' AND tuition_id = '$tuition_id' AND status = 'completed'";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        log_message("Error calculating payments for tuition ID $tuition_id: " . mysqli_error($conn));
        continue;
    }
    
    $row = mysqli_fetch_assoc($result);
    $amount_paid = $row['total_paid'];
    $total_amount = $record['total_amount'];
    $balance = $total_amount - $amount_paid;
    
    // Determine the status
    $status = 'pending';
    if ($amount_paid >= $total_amount) {
        $status = 'paid';
    } else if ($amount_paid > 0) {
        $status = 'partial';
    }
    
    // Update the tuition record
    $query = "UPDATE student_tuition 
              SET amount_paid = $amount_paid, 
                  balance = $balance, 
                  status = '$status', 
                  updated_at = NOW() 
              WHERE id = '$tuition_id'";
    
    if (mysqli_query($conn, $query)) {
        log_message("Updated tuition ID $tuition_id for student $student_id: Status=$status, Paid=$amount_paid, Balance=$balance");
    } else {
        log_message("Error updating tuition ID $tuition_id: " . mysqli_error($conn));
    }
}

// Update payments with missing tuition_id
log_message("Checking for payments without tuition ID references");

$query = "SELECT p.payment_id, p.student_id, p.payment_date, p.amount
          FROM payments p
          WHERE p.tuition_id IS NULL AND p.status = 'completed'";
$result = mysqli_query($conn, $query);

if (!$result) {
    log_message("Error fetching payments: " . mysqli_error($conn));
    exit;
}

$payments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $payments[] = $row;
}

log_message("Found " . count($payments) . " payments without tuition ID references");

// Process each payment
foreach ($payments as $payment) {
    $payment_id = $payment['payment_id'];
    $student_id = $payment['student_id'];
    $payment_date = $payment['payment_date'];
    
    // Find the latest unpaid or partially paid tuition record before this payment
    $query = "SELECT id 
              FROM student_tuition 
              WHERE student_id = '$student_id' 
              AND status IN ('pending', 'partial')
              AND created_at <= '$payment_date'
              ORDER BY created_at DESC 
              LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        log_message("No matching tuition record found for payment ID $payment_id");
        continue;
    }
    
    $row = mysqli_fetch_assoc($result);
    $tuition_id = $row['id'];
    
    // Update the payment with the tuition ID
    $query = "UPDATE payments SET tuition_id = '$tuition_id' WHERE payment_id = '$payment_id'";
    
    if (mysqli_query($conn, $query)) {
        log_message("Updated payment ID $payment_id to reference tuition ID $tuition_id");
    } else {
        log_message("Error updating payment ID $payment_id: " . mysqli_error($conn));
    }
}

log_message("Update process completed");
?> 