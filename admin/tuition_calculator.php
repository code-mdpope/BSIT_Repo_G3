<?php
require_once '../config.php';
require_once '../dashboard_functions.php';

// Check if user is logged in as admin
if (!user_has_role('admin')) {
    header('Location: ../index.php');
    exit();
}

// Get admin information
$admin_id = $_SESSION['user_id'];
$user_info = get_user_info($admin_id);

// Initialize variables
$message = '';
$message_type = '';
$calculation_result = null;

// Get current tuition settings
$conn = get_db_connection();
$query = "SELECT * FROM tuition_settings ORDER BY academic_year DESC, semester DESC LIMIT 1";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $tuition_settings = mysqli_fetch_assoc($result);
} else {
    // Default values if no settings exist
    $tuition_settings = [
        'academic_year' => date('Y'),
        'semester' => 'Fall',
        'tuition_per_credit' => 350,
        'registration_fee' => 150,
        'technology_fee' => 200,
        'activity_fee' => 100,
        'health_fee' => 75,
        'discount_full_time' => 5, // 5% discount
        'discount_early_payment' => 3, // 3% discount
        'late_payment_penalty' => 10, // $10 penalty
    ];
}

// Get students for dropdown
$query = "SELECT 
            u.user_id, u.first_name, u.last_name,
            s.program, s.year_level, s.credits_earned
          FROM users u
          JOIN students s ON u.user_id = s.student_id
          WHERE u.role = 'student'
          ORDER BY u.last_name, u.first_name";
$result = mysqli_query($conn, $query);
$students = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
}

// Get all available terms
$query = "SELECT DISTINCT academic_year, semester FROM tuition_settings ORDER BY academic_year DESC, FIELD(semester, 'Fall', 'Summer', 'Spring')";
$result = mysqli_query($conn, $query);
$terms = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $terms[] = $row;
    }
}

// Process calculation form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'calculate') {
    $student_id = $_POST['student_id'] ?? '';
    $academic_year = (int)$_POST['academic_year'];
    $semester = $_POST['semester'];
    $credits = (int)$_POST['credits'];
    $include_fees = isset($_POST['include_fees']);
    $early_payment = isset($_POST['early_payment']);
    
    // Get the appropriate tuition settings
    $query = "SELECT * FROM tuition_settings WHERE academic_year = $academic_year AND semester = '$semester'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $term_settings = mysqli_fetch_assoc($result);
        
        // Calculate tuition
        $tuition_amount = $credits * $term_settings['tuition_per_credit'];
        
        // Calculate fees
        $fees_amount = 0;
        if ($include_fees) {
            $fees_amount = $term_settings['registration_fee'] + 
                          $term_settings['technology_fee'] + 
                          $term_settings['activity_fee'] + 
                          $term_settings['health_fee'];
        }
        
        // Calculate discounts
        $discounts_amount = 0;
        // Full-time student discount (12+ credits)
        if ($credits >= 12) {
            $discounts_amount += ($tuition_amount * ($term_settings['discount_full_time'] / 100));
        }
        // Early payment discount
        if ($early_payment) {
            $discounts_amount += (($tuition_amount + $fees_amount) * ($term_settings['discount_early_payment'] / 100));
        }
        
        // Calculate total
        $total_amount = ($tuition_amount + $fees_amount) - $discounts_amount;
        
        // Get student info if selected
        $student_info = null;
        if (!empty($student_id)) {
            foreach ($students as $student) {
                if ($student['user_id'] == $student_id) {
                    $student_info = $student;
                    break;
                }
            }
        }
        
        // Store calculation result
        $calculation_result = [
            'student' => $student_info,
            'term' => $academic_year . ' ' . $semester,
            'credits' => $credits,
            'tuition_rate' => $term_settings['tuition_per_credit'],
            'tuition_amount' => $tuition_amount,
            'fees_amount' => $fees_amount,
            'discounts_amount' => $discounts_amount,
            'total_amount' => $total_amount
        ];
    } else {
        $message = "Error: Tuition settings not found for the selected term.";
        $message_type = "error";
    }
}

// Process apply calculation action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_calculation') {
    $student_id = $_POST['student_id'];
    $academic_year = (int)$_POST['academic_year'];
    $semester = $_POST['semester'];
    $tuition_amount = (float)$_POST['tuition_amount'];
    $fees_amount = (float)$_POST['fees_amount'];
    $discounts_amount = (float)$_POST['discounts_amount'];
    $total_amount = (float)$_POST['total_amount'];
    
    // Set due date to 30 days from now
    $due_date = date('Y-m-d', strtotime('+30 days'));
    
    // Insert tuition record
    $query = "INSERT INTO student_tuition
              (student_id, academic_year, semester, tuition_amount, fees_amount, 
               discounts_amount, penalties_amount, total_amount, amount_paid, 
               balance, status, due_date, created_at, updated_at)
              VALUES
              ('$student_id', $academic_year, '$semester', $tuition_amount, $fees_amount,
               $discounts_amount, 0, $total_amount, 0,
               $total_amount, 'pending', '$due_date', NOW(), NOW())";
    
    if (mysqli_query($conn, $query)) {
        $message = "Tuition calculation applied successfully for the student.";
        $message_type = "success";
    } else {
        $message = "Error applying tuition calculation: " . mysqli_error($conn);
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tuition Calculator - IDSC Portal</title>
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
        <!-- Sidebar -->
        <div class="flex flex-1">
            <aside class="w-64 bg-white shadow-sm border-r hidden lg:block">
                <div class="p-5 border-b">
                    <div class="flex items-center space-x-2">
                        <h1 class="font-['Pacifico'] text-2xl text-primary">IDSC</h1>
                        <span class="text-gray-600 text-lg">Portal</span>
                    </div>
                </div>
                
                <nav class="p-4 space-y-1">
                    <a href="dashboard.php" class="flex items-center space-x-2 px-3 py-2 rounded-md text-gray-700 hover:bg-green-50 hover:text-primary transition-colors">
                        <i class="ri-dashboard-line"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="students.php" class="flex items-center space-x-2 px-3 py-2 rounded-md text-gray-700 hover:bg-green-50 hover:text-primary transition-colors">
                        <i class="ri-user-line"></i>
                        <span>Students</span>
                    </a>
                    <a href="courses.php" class="flex items-center space-x-2 px-3 py-2 rounded-md text-gray-700 hover:bg-green-50 hover:text-primary transition-colors">
                        <i class="ri-book-line"></i>
                        <span>Courses</span>
                    </a>
                    <a href="tuition_calculator.php" class="flex items-center space-x-2 px-3 py-2 rounded-md bg-green-50 text-primary font-medium">
                        <i class="ri-money-dollar-circle-line"></i>
                        <span>Tuition</span>
                    </a>
                    <a href="enrollments.php" class="flex items-center space-x-2 px-3 py-2 rounded-md text-gray-700 hover:bg-green-50 hover:text-primary transition-colors">
                        <i class="ri-user-add-line"></i>
                        <span>Enrollments</span>
                    </a>
                    <a href="reports.php" class="flex items-center space-x-2 px-3 py-2 rounded-md text-gray-700 hover:bg-green-50 hover:text-primary transition-colors">
                        <i class="ri-bar-chart-line"></i>
                        <span>Reports</span>
                    </a>
                    <a href="settings.php" class="flex items-center space-x-2 px-3 py-2 rounded-md text-gray-700 hover:bg-green-50 hover:text-primary transition-colors">
                        <i class="ri-settings-line"></i>
                        <span>Settings</span>
                    </a>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 flex flex-col">
                <!-- Header -->
                <header class="bg-white shadow-sm border-b sticky top-0 z-10">
                    <div class="flex items-center justify-between px-4 py-3">
                        <div class="lg:hidden">
                            <button type="button" class="text-gray-600 hover:text-primary">
                                <i class="ri-menu-line text-2xl"></i>
                            </button>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <div class="relative">
                                <button type="button" class="flex items-center space-x-1 text-gray-700">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_info['first_name'] . ' ' . $user_info['last_name']); ?>&background=E9F5E9&color=2E7D32" class="w-8 h-8 rounded-full" alt="Profile">
                                    <span class="hidden md:inline-block"><?php echo htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']); ?></span>
                                    <i class="ri-arrow-down-s-line"></i>
                                </button>
                            </div>
                            
                            <form method="POST" action="../auth.php">
                                <input type="hidden" name="action" value="logout">
                                <button type="submit" class="text-gray-600 hover:text-primary">
                                    <i class="ri-logout-box-line text-xl"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </header>

                <!-- Tuition Calculator Content -->
                <div class="flex-1 overflow-y-auto p-4 md:p-6">
                    <div class="mb-6 flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Tuition Calculator</h1>
                            <p class="text-gray-600">Calculate and apply tuition charges for students</p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="tuition_settings.php" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-button flex items-center space-x-2 hover:bg-gray-50 transition">
                                <i class="ri-settings-line"></i>
                                <span>Tuition Settings</span>
                            </a>
                        </div>
                    </div>
                    
                    <?php if (!empty($message)): ?>
                        <div class="mb-6 p-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tuition Calculator Form -->
                    <div class="bg-white rounded-lg border shadow-sm mb-6">
                        <div class="p-5 border-b">
                            <h2 class="text-lg font-semibold text-gray-800">Calculate Tuition</h2>
                        </div>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="calculate">
                            <div class="p-5 space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="md:col-span-3">
                                        <label for="student_id" class="block text-sm font-medium text-gray-700 mb-1">Student (Optional)</label>
                                        <select id="student_id" name="student_id" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                            <option value="">-- Select a student (optional) --</option>
                                            <?php foreach ($students as $student): ?>
                                                <option value="<?php echo $student['user_id']; ?>">
                                                    <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' (' . $student['program'] . ', Year ' . $student['year_level'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="academic_year" class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                                        <select id="academic_year" name="academic_year" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                            <?php
                                            // Get unique years from terms
                                            $years = [];
                                            foreach ($terms as $term) {
                                                if (!in_array($term['academic_year'], $years)) {
                                                    $years[] = $term['academic_year'];
                                                }
                                            }
                                            sort($years, SORT_NUMERIC);
                                            $years = array_reverse($years);
                                            
                                            foreach ($years as $year): 
                                            ?>
                                                <option value="<?php echo $year; ?>" <?php echo $year == $tuition_settings['academic_year'] ? 'selected' : ''; ?>>
                                                    <?php echo $year; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="semester" class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                                        <select id="semester" name="semester" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                            <option value="Fall" <?php echo $tuition_settings['semester'] === 'Fall' ? 'selected' : ''; ?>>Fall</option>
                                            <option value="Spring" <?php echo $tuition_settings['semester'] === 'Spring' ? 'selected' : ''; ?>>Spring</option>
                                            <option value="Summer" <?php echo $tuition_settings['semester'] === 'Summer' ? 'selected' : ''; ?>>Summer</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="credits" class="block text-sm font-medium text-gray-700 mb-1">Credit Hours</label>
                                        <input type="number" id="credits" name="credits" min="1" max="24" value="12" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                    </div>
                                </div>
                                
                                <div class="flex space-x-5">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="include_fees" name="include_fees" class="w-4 h-4 text-primary focus:ring-primary border-gray-300 rounded" checked>
                                        <label for="include_fees" class="ml-2 block text-sm text-gray-700">Include Fees</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="early_payment" name="early_payment" class="w-4 h-4 text-primary focus:ring-primary border-gray-300 rounded">
                                        <label for="early_payment" class="ml-2 block text-sm text-gray-700">Early Payment Discount</label>
                                    </div>
                                </div>
                            </div>
                            <div class="p-5 border-t bg-gray-50 flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-button hover:bg-primary/90 transition">Calculate Tuition</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Calculation Results -->
                    <?php if ($calculation_result): ?>
                        <div class="bg-white rounded-lg border shadow-sm mb-6">
                            <div class="p-5 border-b">
                                <h2 class="text-lg font-semibold text-gray-800">Calculation Results</h2>
                            </div>
                            <div class="p-5">
                                <?php if ($calculation_result['student']): ?>
                                    <div class="mb-4 p-4 bg-gray-50 rounded-md">
                                        <div class="flex items-start">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($calculation_result['student']['first_name'] . ' ' . $calculation_result['student']['last_name']); ?>&background=E9F5E9&color=2E7D32" class="w-12 h-12 rounded-full mr-4" alt="Profile">
                                            <div>
                                                <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($calculation_result['student']['first_name'] . ' ' . $calculation_result['student']['last_name']); ?></h3>
                                                <p class="text-sm text-gray-600">
                                                    <?php echo htmlspecialchars($calculation_result['student']['program'] . ', Year ' . $calculation_result['student']['year_level']); ?>
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    Credits Earned: <?php echo htmlspecialchars($calculation_result['student']['credits_earned']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="space-y-4">
                                    <div>
                                        <h3 class="font-medium text-gray-800 mb-2">Tuition Details</h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="bg-gray-50 p-3 rounded">
                                                <p class="text-sm text-gray-600">Term: <?php echo htmlspecialchars($calculation_result['term']); ?></p>
                                                <p class="text-sm text-gray-600">Credits: <?php echo htmlspecialchars($calculation_result['credits']); ?></p>
                                                <p class="text-sm text-gray-600">Rate per Credit: $<?php echo number_format($calculation_result['tuition_rate'], 2); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="border-t pt-4">
                                        <h3 class="font-medium text-gray-800 mb-2">Cost Breakdown</h3>
                                        <table class="min-w-full">
                                            <tbody>
                                                <tr>
                                                    <td class="py-2 text-sm text-gray-600">Tuition (<?php echo $calculation_result['credits']; ?> credits × $<?php echo number_format($calculation_result['tuition_rate'], 2); ?>)</td>
                                                    <td class="py-2 text-sm text-gray-800 text-right">$<?php echo number_format($calculation_result['tuition_amount'], 2); ?></td>
                                                </tr>
                                                <?php if ($calculation_result['fees_amount'] > 0): ?>
                                                    <tr>
                                                        <td class="py-2 text-sm text-gray-600">Fees</td>
                                                        <td class="py-2 text-sm text-gray-800 text-right">$<?php echo number_format($calculation_result['fees_amount'], 2); ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                <?php if ($calculation_result['discounts_amount'] > 0): ?>
                                                    <tr>
                                                        <td class="py-2 text-sm text-gray-600">Discounts</td>
                                                        <td class="py-2 text-sm text-green-600 text-right">-$<?php echo number_format($calculation_result['discounts_amount'], 2); ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                <tr class="border-t">
                                                    <td class="py-3 font-medium text-gray-800">Total</td>
                                                    <td class="py-3 font-medium text-gray-800 text-right">$<?php echo number_format($calculation_result['total_amount'], 2); ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <?php if ($calculation_result['student']): ?>
                                    <div class="mt-6 pt-4 border-t">
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="apply_calculation">
                                            <input type="hidden" name="student_id" value="<?php echo $calculation_result['student']['user_id']; ?>">
                                            <input type="hidden" name="academic_year" value="<?php echo explode(' ', $calculation_result['term'])[0]; ?>">
                                            <input type="hidden" name="semester" value="<?php echo explode(' ', $calculation_result['term'])[1]; ?>">
                                            <input type="hidden" name="tuition_amount" value="<?php echo $calculation_result['tuition_amount']; ?>">
                                            <input type="hidden" name="fees_amount" value="<?php echo $calculation_result['fees_amount']; ?>">
                                            <input type="hidden" name="discounts_amount" value="<?php echo $calculation_result['discounts_amount']; ?>">
                                            <input type="hidden" name="total_amount" value="<?php echo $calculation_result['total_amount']; ?>">
                                            
                                            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-button hover:bg-primary/90 transition w-full">
                                                Apply Tuition Charge to Student Account
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 