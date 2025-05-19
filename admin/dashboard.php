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

// Get system stats
function get_system_stats() {
    $conn = get_db_connection();
    $stats = [];
    
    // Total Students
    $query = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_students'] = $row['total'];
    } else {
        $stats['total_students'] = 0;
    }
    
    // Total Instructors
    $query = "SELECT COUNT(*) as total FROM users WHERE role = 'instructor'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_instructors'] = $row['total'];
    } else {
        $stats['total_instructors'] = 0;
    }
    
    // Total Courses
    $query = "SELECT COUNT(*) as total FROM courses";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_courses'] = $row['total'];
    } else {
        $stats['total_courses'] = 0;
    }
    
    // Total Classes (Active)
    $query = "SELECT COUNT(*) as total FROM classes WHERE status = 'active'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stats['active_classes'] = $row['total'];
    } else {
        $stats['active_classes'] = 0;
    }
    
    // Total Enrollments (Active)
    $query = "SELECT COUNT(*) as total FROM enrollments WHERE status = 'enrolled'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stats['active_enrollments'] = $row['total'];
    } else {
        $stats['active_enrollments'] = 0;
    }
    
    return $stats;
}

// Get recent activities (enrollments, new users, etc.)
function get_recent_activities($limit = 5) {
    $conn = get_db_connection();
    $limit = (int)$limit;
    $activities = [];
    
    // Recent enrollments
    $query = "SELECT e.enrollment_id, e.enrollment_date, e.status,
              s.student_id, u1.first_name as student_first_name, u1.last_name as student_last_name,
              c.course_id, c.course_name,
              u2.first_name as instructor_first_name, u2.last_name as instructor_last_name
              FROM enrollments e
              JOIN students s ON e.student_id = s.student_id
              JOIN users u1 ON s.student_id = u1.user_id
              JOIN classes cl ON e.class_id = cl.class_id
              JOIN courses c ON cl.course_id = c.course_id
              JOIN users u2 ON cl.instructor_id = u2.user_id
              ORDER BY e.enrollment_date DESC
              LIMIT $limit";
    
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $activities[] = [
                'type' => 'enrollment',
                'date' => $row['enrollment_date'],
                'details' => $row
            ];
        }
    }
    
    // Recent user registrations
    $query = "SELECT user_id, first_name, last_name, role, created_at
              FROM users
              ORDER BY created_at DESC
              LIMIT $limit";
    
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $activities[] = [
                'type' => 'user_registration',
                'date' => $row['created_at'],
                'details' => $row
            ];
        }
    }
    
    // Sort by date (newest first)
    usort($activities, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    // Return only the most recent ones
    return array_slice($activities, 0, $limit);
}

// Get departments with student counts
function get_departments_with_counts() {
    $conn = get_db_connection();
    $departments = [];
    
    $query = "SELECT s.program as department, COUNT(*) as student_count
              FROM students s
              GROUP BY s.program
              ORDER BY student_count DESC";
    
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $departments[] = $row;
        }
    }
    
    return $departments;
}

// Get recent announcements
$announcements = get_recent_announcements();
$stats = get_system_stats();
$recent_activities = get_recent_activities();
$departments = get_departments_with_counts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IDSC Portal</title>
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
                    <a href="dashboard.php" class="flex items-center space-x-2 px-3 py-2 rounded-md bg-green-50 text-primary font-medium">
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
                    <a href="tuition_calculator.php" class="flex items-center space-x-2 px-3 py-2 rounded-md text-gray-700 hover:bg-green-50 hover:text-primary transition-colors">
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

                <!-- Dashboard Content -->
                <div class="flex-1 overflow-y-auto p-4 md:p-6">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">Admin Dashboard</h1>
                        <p class="text-gray-600">System overview and management</p>
                    </div>
                    
                    <!-- Stats Overview -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                        <div class="bg-white rounded-lg border p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <h2 class="text-lg font-semibold text-gray-800">Students</h2>
                                <i class="ri-user-line text-xl text-primary/80"></i>
                            </div>
                            <div class="flex items-center">
                                <span class="text-3xl font-bold text-gray-800"><?php echo $stats['total_students']; ?></span>
                            </div>
                            <a href="users.php?role=student" class="mt-3 text-sm text-primary hover:underline block">View all students</a>
                        </div>
                        <div class="bg-white rounded-lg border p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <h2 class="text-lg font-semibold text-gray-800">Instructors</h2>
                                <i class="ri-user-star-line text-xl text-blue-600/80"></i>
                            </div>
                            <div class="flex items-center">
                                <span class="text-3xl font-bold text-gray-800"><?php echo $stats['total_instructors']; ?></span>
                            </div>
                            <a href="users.php?role=instructor" class="mt-3 text-sm text-primary hover:underline block">View all instructors</a>
                        </div>
                        <div class="bg-white rounded-lg border p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <h2 class="text-lg font-semibold text-gray-800">Courses</h2>
                                <i class="ri-book-line text-xl text-amber-600/80"></i>
                            </div>
                            <div class="flex items-center">
                                <span class="text-3xl font-bold text-gray-800"><?php echo $stats['total_courses']; ?></span>
                            </div>
                            <div class="flex justify-between items-center mt-3">
                                <a href="courses.php" class="text-sm text-primary hover:underline">Manage courses</a>
                                <span class="text-xs text-gray-500"><?php echo $stats['active_classes']; ?> active classes</span>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg border p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <h2 class="text-lg font-semibold text-gray-800">Enrollments</h2>
                                <i class="ri-user-add-line text-xl text-purple-600/80"></i>
                            </div>
                            <div class="flex items-center">
                                <span class="text-3xl font-bold text-gray-800"><?php echo $stats['active_enrollments']; ?></span>
                            </div>
                            <a href="enrollments.php" class="mt-3 text-sm text-primary hover:underline block">Manage enrollments</a>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg border shadow-sm mb-6">
                        <div class="p-5 border-b">
                            <h2 class="text-lg font-semibold text-gray-800">Quick Actions</h2>
                        </div>
                        <div class="p-5 grid grid-cols-1 md:grid-cols-4 gap-4">
                            <a href="add_user.php" class="flex flex-col items-center p-4 rounded-lg border hover:bg-green-50 hover:border-primary transition-colors">
                                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mb-3">
                                    <i class="ri-user-add-line text-xl text-primary"></i>
                                </div>
                                <span class="font-medium text-gray-800">Add User</span>
                            </a>
                            <a href="add_course.php" class="flex flex-col items-center p-4 rounded-lg border hover:bg-blue-50 hover:border-blue-600 transition-colors">
                                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mb-3">
                                    <i class="ri-book-line text-xl text-blue-600"></i>
                                </div>
                                <span class="font-medium text-gray-800">Add Course</span>
                            </a>
                            <a href="create_class.php" class="flex flex-col items-center p-4 rounded-lg border hover:bg-amber-50 hover:border-amber-600 transition-colors">
                                <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mb-3">
                                    <i class="ri-calendar-line text-xl text-amber-600"></i>
                                </div>
                                <span class="font-medium text-gray-800">Create Class</span>
                            </a>
                            <a href="manage_enrollment.php" class="flex flex-col items-center p-4 rounded-lg border hover:bg-purple-50 hover:border-purple-600 transition-colors">
                                <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center mb-3">
                                    <i class="ri-user-settings-line text-xl text-purple-600"></i>
                                </div>
                                <span class="font-medium text-gray-800">Manage Enrollment</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Programs and Activities -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Department Distribution -->
                        <div class="bg-white rounded-lg border shadow-sm">
                            <div class="p-5 border-b">
                                <h2 class="text-lg font-semibold text-gray-800">Programs</h2>
                            </div>
                            <div class="p-5">
                                <?php if (empty($departments)): ?>
                                    <div class="text-center py-6">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="ri-building-line text-2xl text-gray-400"></i>
                                        </div>
                                        <h3 class="text-gray-500 font-medium">No programs data available</h3>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($departments as $department): ?>
                                            <div class="flex items-center justify-between">
                                                <span class="text-gray-700"><?php echo htmlspecialchars($department['department']); ?></span>
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                        <?php
                                                        $max_students = max(array_column($departments, 'student_count'));
                                                        $percentage = ($department['student_count'] / $max_students) * 100;
                                                        ?>
                                                        <div class="h-full bg-primary" style="width: <?php echo $percentage; ?>%"></div>
                                                    </div>
                                                    <span class="text-sm text-gray-500"><?php echo $department['student_count']; ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Recent Activities -->
                        <div class="bg-white rounded-lg border shadow-sm">
                            <div class="p-5 border-b">
                                <h2 class="text-lg font-semibold text-gray-800">Recent Activities</h2>
                            </div>
                            <div class="p-5">
                                <?php if (empty($recent_activities)): ?>
                                    <div class="text-center py-6">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="ri-history-line text-2xl text-gray-400"></i>
                                        </div>
                                        <h3 class="text-gray-500 font-medium">No recent activities</h3>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-5">
                                        <?php foreach ($recent_activities as $activity): ?>
                                            <?php if ($activity['type'] === 'enrollment'): ?>
                                                <div class="flex">
                                                    <div class="flex-shrink-0 mr-4">
                                                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                            <i class="ri-user-add-line text-blue-600"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-1">
                                                        <p class="text-gray-800">
                                                            <span class="font-medium"><?php echo htmlspecialchars($activity['details']['student_first_name'] . ' ' . $activity['details']['student_last_name']); ?></span> 
                                                            enrolled in 
                                                            <span class="font-medium"><?php echo htmlspecialchars($activity['details']['course_id'] . ' - ' . $activity['details']['course_name']); ?></span>
                                                        </p>
                                                        <p class="text-xs text-gray-500 mt-1"><?php echo format_date($activity['date'], 'M j, Y'); ?></p>
                                                    </div>
                                                </div>
                                            <?php elseif ($activity['type'] === 'user_registration'): ?>
                                                <div class="flex">
                                                    <div class="flex-shrink-0 mr-4">
                                                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                                            <i class="ri-user-line text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-1">
                                                        <p class="text-gray-800">
                                                            New <?php echo htmlspecialchars($activity['details']['role']); ?>: 
                                                            <span class="font-medium"><?php echo htmlspecialchars($activity['details']['first_name'] . ' ' . $activity['details']['last_name']); ?></span> 
                                                            (<?php echo htmlspecialchars($activity['details']['user_id']); ?>)
                                                        </p>
                                                        <p class="text-xs text-gray-500 mt-1"><?php echo format_date($activity['date'], 'M j, Y'); ?></p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Announcements -->
                    <div class="bg-white rounded-lg border shadow-sm">
                        <div class="p-5 border-b flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-800">Announcements</h2>
                            <a href="create_announcement.php" class="px-3 py-1.5 bg-primary text-white text-sm rounded hover:bg-primary/90">
                                Create New
                            </a>
                        </div>
                        <div class="p-5">
                            <?php if (empty($announcements)): ?>
                                <div class="text-center py-6">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="ri-megaphone-line text-2xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-gray-500 font-medium">No announcements</h3>
                                    <p class="text-sm text-gray-400 mt-1">Create a new announcement to get started</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($announcements as $announcement): ?>
                                        <div class="border rounded-md overflow-hidden">
                                            <div class="p-4">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                                                        <?php echo format_date($announcement['published_date'], 'M j'); ?>
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars(substr($announcement['content'], 0, 150) . (strlen($announcement['content']) > 150 ? '...' : '')); ?></p>
                                                <div class="flex justify-between items-center mt-3">
                                                    <p class="text-xs text-gray-500">Posted by <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?></p>
                                                    <div class="flex space-x-2">
                                                        <a href="edit_announcement.php?id=<?php echo $announcement['announcement_id']; ?>" class="text-xs px-2 py-1 border rounded text-gray-600 hover:bg-gray-50">
                                                            Edit
                                                        </a>
                                                        <a href="delete_announcement.php?id=<?php echo $announcement['announcement_id']; ?>" class="text-xs px-2 py-1 border rounded text-red-600 hover:bg-red-50">
                                                            Delete
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 