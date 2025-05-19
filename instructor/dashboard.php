<?php
require_once '../config.php';
require_once '../dashboard_functions.php';

// Check if user is logged in as instructor
if (!user_has_role('instructor')) {
    header('Location: ../index.php');
    exit();
}

// Get instructor information
$instructor_id = $_SESSION['user_id'];
$user_info = get_user_info($instructor_id);

// Get instructor classes
function get_instructor_classes($instructor_id) {
    $conn = get_db_connection();
    $instructor_id = mysqli_real_escape_string($conn, $instructor_id);
    
    $query = "SELECT c.course_id, c.course_name, c.department, c.credits, 
              cl.class_id, cl.semester, cl.year, cl.status,
              (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = cl.class_id AND e.status = 'enrolled') as enrolled_students
              FROM classes cl
              JOIN courses c ON cl.course_id = c.course_id
              WHERE cl.instructor_id = '$instructor_id'
              ORDER BY cl.semester, c.course_name";
    
    $result = mysqli_query($conn, $query);
    $classes = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $classes[] = $row;
        }
    }
    
    return $classes;
}

// Get instructor today's schedule
function get_instructor_today_schedule($instructor_id) {
    $conn = get_db_connection();
    $instructor_id = mysqli_real_escape_string($conn, $instructor_id);
    
    // Get current day of week
    $day_of_week = date('l');
    
    $query = "SELECT c.course_id, c.course_name, c.department,
              cl.class_id, cs.day_of_week, cs.start_time, cs.end_time, 
              cs.room, cs.building,
              (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = cl.class_id AND e.status = 'enrolled') as enrolled_students
              FROM classes cl
              JOIN class_schedule cs ON cl.class_id = cs.class_id
              JOIN courses c ON cl.course_id = c.course_id
              WHERE cl.instructor_id = '$instructor_id' 
              AND cs.day_of_week = '$day_of_week'
              ORDER BY cs.start_time";
    
    $result = mysqli_query($conn, $query);
    $schedule = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $schedule[] = $row;
        }
    }
    
    return $schedule;
}

// Get instructor pending assignments to grade
function get_pending_assignments_to_grade($instructor_id) {
    $conn = get_db_connection();
    $instructor_id = mysqli_real_escape_string($conn, $instructor_id);
    
    $query = "SELECT a.assignment_id, a.title, a.due_date, c.course_id, c.course_name,
              COUNT(s.submission_id) as submission_count,
              SUM(CASE WHEN s.status = 'submitted' THEN 1 ELSE 0 END) as pending_count
              FROM assignments a
              JOIN classes cl ON a.class_id = cl.class_id
              JOIN courses c ON cl.course_id = c.course_id
              LEFT JOIN assignment_submissions s ON a.assignment_id = s.assignment_id
              WHERE cl.instructor_id = '$instructor_id'
              GROUP BY a.assignment_id
              HAVING pending_count > 0
              ORDER BY a.due_date DESC
              LIMIT 5";
    
    $result = mysqli_query($conn, $query);
    $assignments = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $assignments[] = $row;
        }
    }
    
    return $assignments;
}

// Get instructor stats
function get_instructor_stats($instructor_id) {
    $conn = get_db_connection();
    $instructor_id = mysqli_real_escape_string($conn, $instructor_id);
    
    // Get total classes
    $query = "SELECT COUNT(*) as total_classes FROM classes WHERE instructor_id = '$instructor_id'";
    $result = mysqli_query($conn, $query);
    $total_classes = 0;
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $total_classes = $row['total_classes'];
    }
    
    // Get total students
    $query = "SELECT COUNT(DISTINCT e.student_id) as total_students 
              FROM enrollments e
              JOIN classes cl ON e.class_id = cl.class_id
              WHERE cl.instructor_id = '$instructor_id' AND e.status = 'enrolled'";
    $result = mysqli_query($conn, $query);
    $total_students = 0;
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $total_students = $row['total_students'];
    }
    
    // Get total pending assignments to grade
    $query = "SELECT COUNT(*) as pending_submissions
              FROM assignment_submissions s
              JOIN assignments a ON s.assignment_id = a.assignment_id
              JOIN classes cl ON a.class_id = cl.class_id
              WHERE cl.instructor_id = '$instructor_id' AND s.status = 'submitted'";
    $result = mysqli_query($conn, $query);
    $pending_submissions = 0;
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $pending_submissions = $row['pending_submissions'];
    }
    
    return [
        'total_classes' => $total_classes,
        'total_students' => $total_students,
        'pending_submissions' => $pending_submissions
    ];
}

// Get recent announcements
$announcements = get_recent_announcements();
$classes = get_instructor_classes($instructor_id);
$today_schedule = get_instructor_today_schedule($instructor_id);
$pending_assignments = get_pending_assignments_to_grade($instructor_id);
$stats = get_instructor_stats($instructor_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - IDSC Portal</title>
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
                    <a href="classes.php" class="flex items-center space-x-2 px-3 py-2 rounded-md text-gray-700 hover:bg-green-50 hover:text-primary transition-colors">
                        <i class="ri-book-line"></i>
                        <span>My Classes</span>
                    </a>
                    <a href="grades.php" class="flex items-center space-x-2 px-3 py-2 rounded-md text-gray-700 hover:bg-green-50 hover:text-primary transition-colors">
                        <i class="ri-file-list-line"></i>
                        <span>Grades</span>
                    </a>
                    <a href="schedule.php" class="flex items-center space-x-2 px-3 py-2 rounded-md text-gray-700 hover:bg-green-50 hover:text-primary transition-colors">
                        <i class="ri-calendar-line"></i>
                        <span>Schedule</span>
                    </a>
                    <a href="announcements.php" class="flex items-center space-x-2 px-3 py-2 rounded-md text-gray-700 hover:bg-green-50 hover:text-primary transition-colors">
                        <i class="ri-megaphone-line"></i>
                        <span>Announcements</span>
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
                        <h1 class="text-2xl font-bold text-gray-800">Instructor Dashboard</h1>
                        <p class="text-gray-600">Welcome back, Prof. <?php echo htmlspecialchars($user_info['last_name']); ?>!</p>
                    </div>
                    
                    <!-- Stats Overview -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-white rounded-lg border p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-800">Classes</h2>
                                <span class="text-sm font-medium px-2 py-1 rounded-full bg-primary/10 text-primary">Spring 2025</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                                    <span class="text-xl font-bold text-primary"><?php echo $stats['total_classes']; ?></span>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Active Classes</p>
                                    <a href="classes.php" class="text-sm text-primary hover:underline">View all</a>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg border p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-800">Students</h2>
                                <span class="text-sm font-medium px-2 py-1 rounded-full bg-blue-100 text-blue-600">Current</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-xl font-bold text-blue-600"><?php echo $stats['total_students']; ?></span>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm text-gray-600">Enrolled Students</p>
                                    <a href="students.php" class="text-sm text-primary hover:underline">View all</a>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg border p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-800">Assignments</h2>
                                <span class="text-sm font-medium px-2 py-1 rounded-full bg-amber-100 text-amber-600"><?php echo $stats['pending_submissions']; ?> Pending</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center">
                                    <span class="text-xl font-bold text-amber-600"><?php echo $stats['pending_submissions']; ?></span>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm text-gray-600">Need Grading</p>
                                    <a href="grades.php" class="text-sm text-primary hover:underline">Grade now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Today's Schedule -->
                    <div class="bg-white rounded-lg border shadow-sm mb-6">
                        <div class="p-5 border-b">
                            <h2 class="text-lg font-semibold text-gray-800">Today's Schedule (<?php echo date('l, F j'); ?>)</h2>
                        </div>
                        <div class="p-5">
                            <?php if (empty($today_schedule)): ?>
                                <div class="text-center py-6">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="ri-calendar-line text-2xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-gray-500 font-medium">No classes scheduled for today</h3>
                                    <p class="text-sm text-gray-400 mt-1">You have a free day!</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($today_schedule as $class): ?>
                                        <div class="flex border-l-4 border-primary rounded-r-md bg-green-50 p-4">
                                            <div class="flex-shrink-0 mr-4 flex flex-col items-center justify-center">
                                                <span class="text-sm font-medium text-gray-500"><?php echo format_time($class['start_time']); ?></span>
                                                <span class="text-xs text-gray-400">to</span>
                                                <span class="text-sm font-medium text-gray-500"><?php echo format_time($class['end_time']); ?></span>
                                            </div>
                                            <div class="flex-1">
                                                <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($class['course_id'] . ' - ' . $class['course_name']); ?></h3>
                                                <p class="text-sm text-gray-600">
                                                    <span><?php echo htmlspecialchars($class['room'] . ', ' . $class['building']); ?></span>
                                                    <span class="mx-2">·</span>
                                                    <span><?php echo htmlspecialchars($class['enrolled_students']); ?> Students</span>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Pending Assignments and Announcements -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Pending Assignments -->
                        <div class="bg-white rounded-lg border shadow-sm">
                            <div class="p-5 border-b">
                                <h2 class="text-lg font-semibold text-gray-800">Assignments to Grade</h2>
                            </div>
                            <div class="p-5">
                                <?php if (empty($pending_assignments)): ?>
                                    <div class="text-center py-6">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="ri-file-list-line text-2xl text-gray-400"></i>
                                        </div>
                                        <h3 class="text-gray-500 font-medium">No pending assignments</h3>
                                        <p class="text-sm text-gray-400 mt-1">You're all caught up!</p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($pending_assignments as $assignment): ?>
                                            <div class="border rounded-md overflow-hidden">
                                                <div class="flex items-center justify-between px-4 py-2 bg-gray-50 border-b">
                                                    <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($assignment['course_id']); ?></span>
                                                    <span class="text-xs px-2 py-1 rounded-full bg-amber-100 text-amber-600">
                                                        <?php echo $assignment['pending_count']; ?> Pending
                                                    </span>
                                                </div>
                                                <div class="p-4">
                                                    <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                                    <div class="mt-3 flex justify-between items-center">
                                                        <span class="text-xs text-gray-500">Due: <?php echo format_date($assignment['due_date'], 'M j, Y'); ?></span>
                                                        <a href="grade_assignment.php?id=<?php echo $assignment['assignment_id']; ?>" class="text-xs font-medium px-3 py-1 bg-primary text-white rounded-full hover:bg-primary/90">
                                                            Grade Now
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Course List -->
                        <div class="bg-white rounded-lg border shadow-sm">
                            <div class="p-5 border-b">
                                <h2 class="text-lg font-semibold text-gray-800">My Courses</h2>
                            </div>
                            <div class="p-5">
                                <?php if (empty($classes)): ?>
                                    <div class="text-center py-6">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="ri-book-open-line text-2xl text-gray-400"></i>
                                        </div>
                                        <h3 class="text-gray-500 font-medium">No courses assigned</h3>
                                        <p class="text-sm text-gray-400 mt-1">Contact administration to assign courses</p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach (array_slice($classes, 0, 5) as $class): ?>
                                            <div class="flex items-center justify-between p-3 border rounded-md hover:bg-gray-50">
                                                <div>
                                                    <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($class['course_id'] . ' - ' . $class['course_name']); ?></h3>
                                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($class['enrolled_students']); ?> students enrolled</p>
                                                </div>
                                                <a href="class_details.php?id=<?php echo $class['class_id']; ?>" class="text-primary hover:underline">
                                                    <i class="ri-arrow-right-line"></i>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <?php if (count($classes) > 5): ?>
                                            <div class="text-center mt-3">
                                                <a href="classes.php" class="text-sm text-primary hover:underline">View all courses</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 