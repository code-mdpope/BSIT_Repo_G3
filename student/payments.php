<?php
require_once '../config.php';
require_once '../dashboard_functions.php';

// Check if user is logged in as student
if (!user_has_role('student')) {
    header('Location: ../index.php');
    exit();
}

// Set current page for navigation
$current_page = 'payments';

// Get student information
$student_id = $_SESSION['user_id'];
$user_info = get_user_info($student_id);
$student_info = get_role_info($student_id, 'student');

// Get payment data
$conn = get_db_connection();

// Get tuition data
$query = "SELECT 
            st.id, st.academic_year, st.semester, st.tuition_amount,
            st.fees_amount, st.discounts_amount, st.penalties_amount,
            st.total_amount, st.amount_paid, st.balance, st.status, st.due_date,
            st.created_at
          FROM student_tuition st
          WHERE st.student_id = '$student_id'
          ORDER BY st.academic_year DESC, FIELD(st.semester, 'Fall', 'Summer', 'Spring')";
$result = mysqli_query($conn, $query);
$tuition_records = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tuition_records[] = $row;
    }
}

// Get latest tuition record for the current balance
$current_tuition = null;
if (!empty($tuition_records)) {
    $current_tuition = $tuition_records[0];
    $current_balance = $current_tuition['balance'];
} else {
    // Calculate balance the old way if no tuition records exist
    $query = "SELECT SUM(amount) as total_paid FROM payments WHERE student_id = '$student_id' AND status = 'completed'";
    $result = mysqli_query($conn, $query);
    $total_paid = 0;

    if ($result && $row = mysqli_fetch_assoc($result)) {
        $total_paid = $row['total_paid'] ?: 0;
    }

    // Tuition costs - in a real app this would come from a database table
    $tuition_per_credit = 350; // $350 per credit
    $total_credits = $student_info['credits_earned'];
    $total_tuition = $tuition_per_credit * $total_credits;
    $current_balance = $total_tuition - $total_paid;
}

// Get payment history
$query = "SELECT 
            payment_id, amount, payment_date, description, payment_method, status, transaction_id,
            tuition_id
          FROM payments
          WHERE student_id = '$student_id'
          ORDER BY payment_date DESC";
$result = mysqli_query($conn, $query);
$payments = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $payments[] = $row;
    }
}

// Calculate total paid
$total_paid = 0;
foreach ($payments as $payment) {
    if ($payment['status'] === 'completed') {
        $total_paid += $payment['amount'];
    }
}

// Process new payment submission
$payment_success = false;
$payment_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'make_payment') {
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    $tuition_id = isset($_POST['tuition_id']) ? $_POST['tuition_id'] : null;
    
    if ($amount <= 0) {
        $payment_error = 'Please enter a valid payment amount.';
    } elseif (empty($payment_method)) {
        $payment_error = 'Please select a payment method.';
    } else {
        // Generate a random transaction ID - in a real app this would come from a payment processor
        $transaction_id = 'TXN-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        $description = "Tuition payment";
        $status = 'completed';  // In a real app, this might be 'pending' until confirmed
        
        $insert_query = "INSERT INTO payments (student_id, amount, payment_date, description, payment_method, status, transaction_id, tuition_id)
                          VALUES ('$student_id', $amount, NOW(), '$description', '$payment_method', '$status', '$transaction_id', " . ($tuition_id ? "'$tuition_id'" : "NULL") . ")";
        
        if (mysqli_query($conn, $insert_query)) {
            // If payment was successful and a tuition record was specified, update the tuition record
            if ($tuition_id) {
                // Get the current tuition record
                $query = "SELECT total_amount, amount_paid FROM student_tuition WHERE id = '$tuition_id'";
                $result = mysqli_query($conn, $query);
                
                if ($result && $row = mysqli_fetch_assoc($result)) {
                    $total_amount = $row['total_amount'];
                    $new_amount_paid = $row['amount_paid'] + $amount;
                    $new_balance = $total_amount - $new_amount_paid;
                    $new_status = ($new_balance <= 0) ? 'paid' : 'partial';
                    
                    // Update the tuition record
                    $update_query = "UPDATE student_tuition 
                                     SET amount_paid = $new_amount_paid, 
                                         balance = $new_balance, 
                                         status = '$new_status', 
                                         updated_at = NOW() 
                                     WHERE id = '$tuition_id'";
                    
                    mysqli_query($conn, $update_query);
                }
            }
            
            $payment_success = true;
            // Refresh the page to show the new payment
            header('Location: payments.php?payment_success=1');
            exit();
        } else {
            $payment_error = 'Payment processing failed. Please try again. Error: ' . mysqli_error($conn);
        }
    }
}

// Function to get status badge color
function get_status_badge_color($status) {
    switch ($status) {
        case 'completed': return 'bg-green-100 text-green-800';
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'failed': return 'bg-red-100 text-red-800';
        case 'refunded': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

// Function to get payment method icon
function get_payment_method_icon($method) {
    switch ($method) {
        case 'credit_card': return 'ri-bank-card-line';
        case 'bank_transfer': return 'ri-bank-line';
        case 'cash': return 'ri-money-dollar-box-line';
        case 'check': return 'ri-file-list-3-line';
        default: return 'ri-secure-payment-line';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Payments - IDSC Portal</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:'#2E7D32',secondary:'#43A047'},borderRadius:{'none':'0px','sm':'4px',DEFAULT:'8px','md':'12px','lg':'16px','xl':'20px','2xl':'24px','3xl':'32px','full':'9999px','button':'8px'}}}}</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <style>
        :where([class^="ri-"])::before { content: "\f3c2"; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8faf8;
            min-height: 100vh;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <!-- Sidebar & Navigation -->
        <div class="flex flex-1">
            <?php include 'inc_nav.php'; ?>

            <!-- Main Content -->
            <main class="flex-1 flex flex-col">
                <!-- Payments Content -->
                <div class="flex-1 overflow-y-auto p-4 md:p-6">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">Payments & Billing</h1>
                        <p class="text-gray-600">View your payment history and make payments</p>
                    </div>
                    
                    <?php if (isset($_GET['payment_success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                        <span class="block sm:inline">Payment processed successfully!</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($payment_error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                        <span class="block sm:inline"><?php echo htmlspecialchars($payment_error); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Account Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-white rounded-lg border p-5 shadow-sm">
                            <h2 class="text-lg font-semibold text-gray-800 mb-3">Current Balance</h2>
                            <div class="flex items-center">
                                <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                    <i class="ri-money-dollar-circle-line text-2xl text-red-600"></i>
                                </div>
                                <div class="ml-4">
                                    <span class="text-2xl font-bold text-gray-800">$<?php echo number_format($current_balance, 2); ?></span>
                                    <p class="text-sm text-gray-600">Outstanding balance</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg border p-5 shadow-sm">
                            <h2 class="text-lg font-semibold text-gray-800 mb-3">Tuition Details</h2>
                            <p class="text-sm text-gray-600 mb-1">Credits: <?php echo $current_tuition['tuition_amount'] ?: 0; ?> x $<?php echo number_format($tuition_per_credit, 2); ?>/credit</p>
                            <p class="text-sm text-gray-600 mb-4">Program: <?php echo htmlspecialchars($student_info['program']); ?></p>
                            <div class="flex items-center justify-between border-t pt-3">
                                <span class="text-sm font-medium text-gray-600">Total Tuition:</span>
                                <span class="font-semibold text-gray-800">$<?php echo number_format($current_tuition['total_amount'], 2); ?></span>
                            </div>
                            <div class="flex items-center justify-between border-t pt-3">
                                <span class="text-sm font-medium text-gray-600">Total Paid:</span>
                                <span class="font-semibold text-gray-800">$<?php echo number_format($total_paid, 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg border p-5 shadow-sm">
                            <h2 class="text-lg font-semibold text-gray-800 mb-3">Quick Pay</h2>
                            <form method="POST" action="payments.php">
                                <input type="hidden" name="action" value="make_payment">
                                <input type="hidden" name="tuition_id" id="tuition_id" value="<?php echo $current_tuition ? $current_tuition['id'] : ''; ?>">
                                <div class="space-y-3">
                                    <div>
                                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount ($)</label>
                                        <input type="number" id="amount" name="amount" min="1" step="0.01" placeholder="Enter amount" class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                    </div>
                                    <div>
                                        <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                                        <select id="payment_method" name="payment_method" class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                            <option value="">-- Select payment method --</option>
                                            <option value="credit_card">Credit Card</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                            <option value="check">Check</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="w-full bg-primary text-white px-4 py-2 rounded hover:bg-primary/90 transition mt-2">
                                        Make Payment
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Tuition Records -->
                    <?php if (!empty($tuition_records)): ?>
                    <div class="bg-white rounded-lg border shadow-sm mb-6">
                        <div class="p-5 border-b">
                            <h2 class="text-lg font-semibold text-gray-800">Tuition Records</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Term</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tuition</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fees</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discounts</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($tuition_records as $tuition): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($tuition['semester'] . ' ' . $tuition['academic_year']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?php echo number_format($tuition['tuition_amount'], 2); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?php echo number_format($tuition['fees_amount'], 2); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?php echo number_format($tuition['discounts_amount'], 2); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">$<?php echo number_format($tuition['total_amount'], 2); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?php echo number_format($tuition['amount_paid'], 2); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $tuition['balance'] > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                                $<?php echo number_format($tuition['balance'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('M j, Y', strtotime($tuition['due_date'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php 
                                                    switch ($tuition['status']) {
                                                        case 'paid': echo 'bg-green-100 text-green-800'; break;
                                                        case 'partial': echo 'bg-yellow-100 text-yellow-800'; break;
                                                        case 'pending': echo 'bg-red-100 text-red-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800';
                                                    }
                                                ?> capitalize">
                                                    <?php echo htmlspecialchars($tuition['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <?php if ($tuition['balance'] > 0): ?>
                                                    <button type="button" class="text-primary hover:underline pay-now-btn" data-tuition-id="<?php echo $tuition['id']; ?>" data-balance="<?php echo $tuition['balance']; ?>">
                                                        Pay Now
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-gray-400">Paid</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Payment History -->
                    <div class="bg-white rounded-lg border shadow-sm">
                        <div class="p-5 border-b">
                            <h2 class="text-lg font-semibold text-gray-800">Payment History</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction ID</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($payments)): ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No payment records found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo format_date($payment['payment_date']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($payment['description']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">$<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <i class="<?php echo get_payment_method_icon($payment['payment_method']); ?> mr-1.5 text-gray-500"></i>
                                                        <span class="text-sm text-gray-600 capitalize"><?php echo str_replace('_', ' ', $payment['payment_method']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo get_status_badge_color($payment['status']); ?> capitalize">
                                                        <?php echo htmlspecialchars($payment['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($payment['transaction_id']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Payment Methods Info -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white rounded-lg border p-5 shadow-sm">
                            <h2 class="text-lg font-semibold text-gray-800 mb-3">Payment Methods</h2>
                            <div class="space-y-3">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 mt-1">
                                        <i class="ri-bank-card-line text-xl text-gray-700"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-800">Credit Card</h3>
                                        <p class="text-sm text-gray-600">Make secure payments using your credit or debit card.</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 mt-1">
                                        <i class="ri-bank-line text-xl text-gray-700"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-800">Bank Transfer</h3>
                                        <p class="text-sm text-gray-600">Transfer funds directly from your bank account.</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 mt-1">
                                        <i class="ri-file-list-3-line text-xl text-gray-700"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-800">Check</h3>
                                        <p class="text-sm text-gray-600">Mail a check to our finance office. Allow 5-7 business days for processing.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg border p-5 shadow-sm">
                            <h2 class="text-lg font-semibold text-gray-800 mb-3">Need Help?</h2>
                            <p class="text-gray-600 mb-4">If you have questions about your bill, payment options, or scholarships, our financial services team is here to help.</p>
                            <div class="space-y-3">
                                <div class="flex items-center space-x-2">
                                    <i class="ri-phone-line text-primary"></i>
                                    <span class="text-gray-800">+1 (555) 123-4567</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <i class="ri-mail-line text-primary"></i>
                                    <span class="text-gray-800">finance@idsc.edu</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <i class="ri-map-pin-line text-primary"></i>
                                    <span class="text-gray-800">Finance Office, Room 105, Administration Building</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Pay Now button functionality
        document.addEventListener('DOMContentLoaded', function() {
            const payButtons = document.querySelectorAll('.pay-now-btn');
            
            payButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tuitionId = this.getAttribute('data-tuition-id');
                    const balance = parseFloat(this.getAttribute('data-balance'));
                    
                    document.getElementById('tuition_id').value = tuitionId;
                    document.getElementById('amount').value = balance.toFixed(2);
                    
                    // Scroll to the payment form
                    document.getElementById('paymentForm').scrollIntoView({ behavior: 'smooth' });
                });
            });
        });
    </script>
</body>
</html> 