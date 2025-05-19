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

// Handle form submissions
$message = '';
$message_type = '';

// Delete student
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $student_id = $_GET['id'];
    $conn = get_db_connection();
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete from students table
        $query = "DELETE FROM students WHERE student_id = '$student_id'";
        mysqli_query($conn, $query);
        
        // Delete from users table
        $query = "DELETE FROM users WHERE user_id = '$student_id'";
        mysqli_query($conn, $query);
        
        // Commit transaction
        mysqli_commit($conn);
        
        $message = "Student deleted successfully.";
        $message_type = "success";
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        
        $message = "Error deleting student: " . $e->getMessage();
        $message_type = "error";
    }
}

// Add or edit student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $conn = get_db_connection();
    
    // Common fields
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $program = mysqli_real_escape_string($conn, $_POST['program']);
    $year_level = (int)$_POST['year_level'];
    $credits_earned = (int)$_POST['credits_earned'];
    $gpa = (float)$_POST['gpa'];
    
    // Add new student
    if ($_POST['action'] === 'add') {
        $password = password_hash('password123', PASSWORD_DEFAULT); // Default password
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert into users table
            $query = "INSERT INTO users (first_name, last_name, email, password, role, created_at)
                      VALUES ('$first_name', '$last_name', '$email', '$password', 'student', NOW())";
            mysqli_query($conn, $query);
            
            $user_id = mysqli_insert_id($conn);
            
            // Insert into students table
            $query = "INSERT INTO students (student_id, program, year_level, credits_earned, gpa, enrollment_date)
                      VALUES ('$user_id', '$program', $year_level, $credits_earned, $gpa, NOW())";
            mysqli_query($conn, $query);
            
            // Commit transaction
            mysqli_commit($conn);
            
            $message = "Student added successfully.";
            $message_type = "success";
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);
            
            $message = "Error adding student: " . $e->getMessage();
            $message_type = "error";
        }
    }
    
    // Edit existing student
    else if ($_POST['action'] === 'edit' && isset($_POST['student_id'])) {
        $student_id = $_POST['student_id'];
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Update users table
            $query = "UPDATE users SET 
                      first_name = '$first_name', 
                      last_name = '$last_name', 
                      email = '$email' 
                      WHERE user_id = '$student_id'";
            mysqli_query($conn, $query);
            
            // Update students table
            $query = "UPDATE students SET 
                      program = '$program', 
                      year_level = $year_level, 
                      credits_earned = $credits_earned,
                      gpa = $gpa
                      WHERE student_id = '$student_id'";
            mysqli_query($conn, $query);
            
            // Commit transaction
            mysqli_commit($conn);
            
            $message = "Student updated successfully.";
            $message_type = "success";
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);
            
            $message = "Error updating student: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Fetch students for display
$conn = get_db_connection();
$query = "SELECT 
            u.user_id, u.first_name, u.last_name, u.email, u.created_at,
            s.program, s.year_level, s.credits_earned, s.gpa, s.enrollment_date
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

// Get student data for editing
$edit_student = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $student_id = $_GET['id'];
    $query = "SELECT 
                u.user_id, u.first_name, u.last_name, u.email,
                s.program, s.year_level, s.credits_earned, s.gpa
              FROM users u
              JOIN students s ON u.user_id = s.student_id
              WHERE u.user_id = '$student_id'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $edit_student = mysqli_fetch_assoc($result);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - IDSC Portal</title>
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
                    <a href="students.php" class="flex items-center space-x-2 px-3 py-2 rounded-md bg-green-50 text-primary font-medium">
                        <i class="ri-user-line"></i>
                        <span>Students</span>
                    </a>
                    <a href="courses.php" class="flex items-center space-x-2 px-3 py-2 rounded-md text-gray-700 hover:bg-green-50 hover:text-primary transition-colors">
                        <i class="ri-book-line"></i>
                        <span>Courses</span>
                    </a>
                    <a href="tuition.php" class="flex items-center space-x-2 px-3 py-2 rounded-md text-gray-700 hover:bg-green-50 hover:text-primary transition-colors">
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

                <!-- Student Management Content -->
                <div class="flex-1 overflow-y-auto p-4 md:p-6">
                    <div class="mb-6 flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Student Management</h1>
                            <p class="text-gray-600">View, add, edit, and delete students</p>
                        </div>
                        <button id="openAddStudentModal" class="bg-primary text-white px-4 py-2 rounded-button flex items-center space-x-2 hover:bg-primary/90 transition">
                            <i class="ri-user-add-line"></i>
                            <span>Add Student</span>
                        </button>
                    </div>
                    
                    <?php if (!empty($message)): ?>
                        <div class="mb-6 p-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Students List -->
                    <div class="bg-white rounded-lg border shadow-sm mb-6">
                        <div class="p-5 border-b">
                            <h2 class="text-lg font-semibold text-gray-800">Students</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Credits</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">GPA</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($students)): ?>
                                        <tr>
                                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No students found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['first_name'] . ' ' . $student['last_name']); ?>&background=E9F5E9&color=2E7D32" class="w-8 h-8 rounded-full mr-3" alt="Profile">
                                                        <div>
                                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                                            <div class="text-xs text-gray-500">ID: <?php echo htmlspecialchars($student['user_id']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($student['program']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($student['year_level']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($student['credits_earned']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo number_format($student['gpa'], 2); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <div class="flex space-x-2">
                                                        <a href="?action=edit&id=<?php echo $student['user_id']; ?>" class="text-blue-600 hover:text-blue-900">
                                                            <i class="ri-edit-line"></i>
                                                        </a>
                                                        <a href="#" onclick="confirmDelete(<?php echo $student['user_id']; ?>)" class="text-red-600 hover:text-red-900">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Add Student Modal -->
    <div id="addStudentModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-5 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Add New Student</h3>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="p-5 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                <input type="text" id="first_name" name="first_name" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                <input type="text" id="last_name" name="last_name" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" id="email" name="email" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label for="program" class="block text-sm font-medium text-gray-700 mb-1">Program</label>
                            <input type="text" id="program" name="program" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="year_level" class="block text-sm font-medium text-gray-700 mb-1">Year Level</label>
                                <input type="number" id="year_level" name="year_level" min="1" max="6" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="credits_earned" class="block text-sm font-medium text-gray-700 mb-1">Credits</label>
                                <input type="number" id="credits_earned" name="credits_earned" min="0" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="gpa" class="block text-sm font-medium text-gray-700 mb-1">GPA</label>
                                <input type="number" id="gpa" name="gpa" min="0" max="4" step="0.01" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">Note: Initial password will be set to "password123"</p>
                    </div>
                    <div class="p-5 border-t bg-gray-50 flex justify-end space-x-3">
                        <button type="button" id="closeAddStudentModal" class="px-4 py-2 border border-gray-300 rounded-button text-gray-700 hover:bg-gray-100 transition">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-button hover:bg-primary/90 transition">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Student Modal -->
    <?php if ($edit_student): ?>
    <div id="editStudentModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black bg-opacity-50"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-5 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Edit Student</h3>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="student_id" value="<?php echo $edit_student['user_id']; ?>">
                    <div class="p-5 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="edit_first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                <input type="text" id="edit_first_name" name="first_name" value="<?php echo htmlspecialchars($edit_student['first_name']); ?>" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="edit_last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                <input type="text" id="edit_last_name" name="last_name" value="<?php echo htmlspecialchars($edit_student['last_name']); ?>" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <div>
                            <label for="edit_email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" id="edit_email" name="email" value="<?php echo htmlspecialchars($edit_student['email']); ?>" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label for="edit_program" class="block text-sm font-medium text-gray-700 mb-1">Program</label>
                            <input type="text" id="edit_program" name="program" value="<?php echo htmlspecialchars($edit_student['program']); ?>" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="edit_year_level" class="block text-sm font-medium text-gray-700 mb-1">Year Level</label>
                                <input type="number" id="edit_year_level" name="year_level" min="1" max="6" value="<?php echo htmlspecialchars($edit_student['year_level']); ?>" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="edit_credits_earned" class="block text-sm font-medium text-gray-700 mb-1">Credits</label>
                                <input type="number" id="edit_credits_earned" name="credits_earned" min="0" value="<?php echo htmlspecialchars($edit_student['credits_earned']); ?>" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="edit_gpa" class="block text-sm font-medium text-gray-700 mb-1">GPA</label>
                                <input type="number" id="edit_gpa" name="gpa" min="0" max="4" step="0.01" value="<?php echo htmlspecialchars($edit_student['gpa']); ?>" required class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                    </div>
                    <div class="p-5 border-t bg-gray-50 flex justify-end space-x-3">
                        <a href="students.php" class="px-4 py-2 border border-gray-300 rounded-button text-gray-700 hover:bg-gray-100 transition">Cancel</a>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-button hover:bg-primary/90 transition">Update Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        // Modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            const addStudentModal = document.getElementById('addStudentModal');
            const openAddStudentModal = document.getElementById('openAddStudentModal');
            const closeAddStudentModal = document.getElementById('closeAddStudentModal');
            
            openAddStudentModal.addEventListener('click', function() {
                addStudentModal.classList.remove('hidden');
            });
            
            closeAddStudentModal.addEventListener('click', function() {
                addStudentModal.classList.add('hidden');
            });
        });
        
        // Confirm delete
        function confirmDelete(studentId) {
            if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
                window.location.href = `?action=delete&id=${studentId}`;
            }
        }
    </script>
</body>
</html> 