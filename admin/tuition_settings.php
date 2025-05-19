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
        'updated_at' => date('Y-m-d H:i:s')
    ];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    // Get form data
    $academic_year = (int)$_POST['academic_year'];
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $tuition_per_credit = (float)$_POST['tuition_per_credit'];
    $registration_fee = (float)$_POST['registration_fee'];
    $technology_fee = (float)$_POST['technology_fee'];
    $activity_fee = (float)$_POST['activity_fee'];
    $health_fee = (float)$_POST['health_fee'];
    $discount_full_time = (float)$_POST['discount_full_time'];
    $discount_early_payment = (float)$_POST['discount_early_payment'];
    $late_payment_penalty = (float)$_POST['late_payment_penalty'];
    
    // Check if settings already exist for this academic year and semester
    $query = "SELECT id FROM tuition_settings WHERE academic_year = $academic_year AND semester = '$semester'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        // Update existing settings
        $row = mysqli_fetch_assoc($result);
        $settings_id = $row['id'];
        
        $query = "UPDATE tuition_settings SET 
                  tuition_per_credit = $tuition_per_credit,
                  registration_fee = $registration_fee,
                  technology_fee = $technology_fee,
                  activity_fee = $activity_fee,
                  health_fee = $health_fee,
                  discount_full_time = $discount_full_time,
                  discount_early_payment = $discount_early_payment,
                  late_payment_penalty = $late_payment_penalty,
                  updated_at = NOW()
                  WHERE id = $settings_id";
    } else {
        // Insert new settings
        $query = "INSERT INTO tuition_settings 
                  (academic_year, semester, tuition_per_credit, registration_fee, technology_fee, 
                   activity_fee, health_fee, discount_full_time, discount_early_payment, late_payment_penalty, updated_at)
                  VALUES 
                  ($academic_year, '$semester', $tuition_per_credit, $registration_fee, $technology_fee,
                   $activity_fee, $health_fee, $discount_full_time, $discount_early_payment, $late_payment_penalty, NOW())";
    }
    
    if (mysqli_query($conn, $query)) {
        $message = "Tuition settings updated successfully.";
        $message_type = "success";
        
        // Refresh settings
        $query = "SELECT * FROM tuition_settings WHERE academic_year = $academic_year AND semester = '$semester'";
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $tuition_settings = mysqli_fetch_assoc($result);
        }
    } else {
        $message = "Error updating tuition settings: " . mysqli_error($conn);
        $message_type = "error";
    }
}

// Get all academic years and semesters for the dropdown
$query = "SELECT DISTINCT academic_year, semester FROM tuition_settings ORDER BY academic_year DESC, FIELD(semester, 'Fall', 'Summer', 'Spring')";
$result = mysqli_query($conn, $query);
$available_terms = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $available_terms[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tuition Settings - IDSC Portal</title>
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
                    <a href="tuition.php" class="flex items-center space-x-2 px-3 py-2 rounded-md bg-green-50 text-primary font-medium">
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

                <!-- Tuition Settings Content -->
                <div class="flex-1 overflow-y-auto p-4 md:p-6">
                    <div class="mb-6 flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Tuition Settings</h1>
                            <p class="text-gray-600">Manage tuition rates and fees for academic terms</p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="tuition.php" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-button flex items-center space-x-2 hover:bg-gray-50 transition">
                                <i class="ri-arrow-left-line"></i>
                                <span>Back to Tuition</span>
                            </a>
                        </div>
                    </div>
                    
                    <?php if (!empty($message)): ?>
                        <div class="mb-6 p-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tuition Settings Form -->
                    <div class="bg-white rounded-lg border shadow-sm mb-6">
                        <div class="p-5 border-b">
                            <h2 class="text-lg font-semibold text-gray-800">Tuition Rates & Fees</h2>
                        </div>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_settings">
                            <div class="p-5 space-y-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label for="academic_year" class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                                        <input type="number" id="academic_year" name="academic_year" min="2000" max="2100" value="<?php echo htmlspecialchars($tuition_settings['academic_year']); ?>" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                    </div>
                                    <div>
                                        <label for="semester" class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                                        <select id="semester" name="semester" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                            <option value="Fall" <?php echo $tuition_settings['semester'] === 'Fall' ? 'selected' : ''; ?>>Fall</option>
                                            <option value="Spring" <?php echo $tuition_settings['semester'] === 'Spring' ? 'selected' : ''; ?>>Spring</option>
                                            <option value="Summer" <?php echo $tuition_settings['semester'] === 'Summer' ? 'selected' : ''; ?>>Summer</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="border-t pt-5">
                                    <h3 class="text-md font-medium text-gray-800 mb-3">Tuition Rates</h3>
                                    <div>
                                        <label for="tuition_per_credit" class="block text-sm font-medium text-gray-700 mb-1">Tuition Per Credit Hour ($)</label>
                                        <input type="number" id="tuition_per_credit" name="tuition_per_credit" min="0" step="0.01" value="<?php echo htmlspecialchars($tuition_settings['tuition_per_credit']); ?>" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                    </div>
                                </div>
                                
                                <div class="border-t pt-5">
                                    <h3 class="text-md font-medium text-gray-800 mb-3">Mandatory Fees</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div>
                                            <label for="registration_fee" class="block text-sm font-medium text-gray-700 mb-1">Registration Fee ($)</label>
                                            <input type="number" id="registration_fee" name="registration_fee" min="0" step="0.01" value="<?php echo htmlspecialchars($tuition_settings['registration_fee']); ?>" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                        </div>
                                        <div>
                                            <label for="technology_fee" class="block text-sm font-medium text-gray-700 mb-1">Technology Fee ($)</label>
                                            <input type="number" id="technology_fee" name="technology_fee" min="0" step="0.01" value="<?php echo htmlspecialchars($tuition_settings['technology_fee']); ?>" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-3">
                                        <div>
                                            <label for="activity_fee" class="block text-sm font-medium text-gray-700 mb-1">Activity Fee ($)</label>
                                            <input type="number" id="activity_fee" name="activity_fee" min="0" step="0.01" value="<?php echo htmlspecialchars($tuition_settings['activity_fee']); ?>" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                        </div>
                                        <div>
                                            <label for="health_fee" class="block text-sm font-medium text-gray-700 mb-1">Health Fee ($)</label>
                                            <input type="number" id="health_fee" name="health_fee" min="0" step="0.01" value="<?php echo htmlspecialchars($tuition_settings['health_fee']); ?>" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="border-t pt-5">
                                    <h3 class="text-md font-medium text-gray-800 mb-3">Discounts & Penalties</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                        <div>
                                            <label for="discount_full_time" class="block text-sm font-medium text-gray-700 mb-1">Full-Time Discount (%)</label>
                                            <input type="number" id="discount_full_time" name="discount_full_time" min="0" max="100" step="0.1" value="<?php echo htmlspecialchars($tuition_settings['discount_full_time']); ?>" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                        </div>
                                        <div>
                                            <label for="discount_early_payment" class="block text-sm font-medium text-gray-700 mb-1">Early Payment Discount (%)</label>
                                            <input type="number" id="discount_early_payment" name="discount_early_payment" min="0" max="100" step="0.1" value="<?php echo htmlspecialchars($tuition_settings['discount_early_payment']); ?>" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                        </div>
                                        <div>
                                            <label for="late_payment_penalty" class="block text-sm font-medium text-gray-700 mb-1">Late Payment Penalty ($)</label>
                                            <input type="number" id="late_payment_penalty" name="late_payment_penalty" min="0" step="0.01" value="<?php echo htmlspecialchars($tuition_settings['late_payment_penalty']); ?>" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-5 border-t bg-gray-50 flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-button hover:bg-primary/90 transition">Save Settings</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Tuition History -->
                    <div class="bg-white rounded-lg border shadow-sm">
                        <div class="p-5 border-b">
                            <h2 class="text-lg font-semibold text-gray-800">Tuition Rate History</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tuition Per Credit</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Fees</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php
                                    $query = "SELECT * FROM tuition_settings ORDER BY academic_year DESC, FIELD(semester, 'Fall', 'Summer', 'Spring')";
                                    $result = mysqli_query($conn, $query);
                                    
                                    if ($result && mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $total_fees = $row['registration_fee'] + $row['technology_fee'] + $row['activity_fee'] + $row['health_fee'];
                                            ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['academic_year']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['semester']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?php echo number_format($row['tuition_per_credit'], 2); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?php echo number_format($total_fees, 2); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M j, Y', strtotime($row['updated_at'])); ?></td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No tuition history found.</td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 