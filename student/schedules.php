<?php
require_once '../config.php';
require_once '../dashboard_functions.php';

// Check if user is logged in as student
if (!user_has_role('student')) {
    header('Location: ../index.php');
    exit();
}

// Set current page for navigation
$current_page = 'schedules';

// Get student information
$student_id = $_SESSION['user_id'];
$user_info = get_user_info($student_id);
$student_info = get_role_info($student_id, 'student');

// Get schedule data
$today_schedule = get_student_today_schedule($student_id);

// Get all classes for schedule
$conn = get_db_connection();
$query = "SELECT 
            cs.day_of_week, cs.start_time, cs.end_time, cs.room, cs.building,
            c.course_id, c.course_name, c.department,
            cl.semester, cl.year,
            u.first_name, u.last_name
          FROM enrollments e
          JOIN classes cl ON e.class_id = cl.class_id
          JOIN class_schedule cs ON cl.class_id = cs.class_id
          JOIN courses c ON cl.course_id = c.course_id
          JOIN users u ON cl.instructor_id = u.user_id
          WHERE e.student_id = '$student_id' AND e.status = 'enrolled'
          ORDER BY FIELD(cs.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                   cs.start_time";
$result = mysqli_query($conn, $query);
$weekly_schedule = [];

// Group by day
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
foreach ($days_of_week as $day) {
    $weekly_schedule[$day] = [];
}

// Populate schedule
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $weekly_schedule[$row['day_of_week']][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Schedules - IDSC Portal</title>
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
                <!-- Schedules Content -->
                <div class="flex-1 overflow-y-auto p-4 md:p-6">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">Class Schedule</h1>
                        <p class="text-gray-600">View your weekly class schedule</p>
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
                                                    <span><?php echo htmlspecialchars('Prof. ' . $class['first_name'] . ' ' . $class['last_name']); ?></span>
                                                    <span class="mx-2">·</span>
                                                    <span><?php echo htmlspecialchars($class['room'] . ', ' . $class['building']); ?></span>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Weekly Schedule -->
                    <div class="bg-white rounded-lg border shadow-sm">
                        <div class="p-5 border-b">
                            <h2 class="text-lg font-semibold text-gray-800">Weekly Schedule</h2>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7 gap-4">
                                <?php foreach ($weekly_schedule as $day => $classes): ?>
                                    <div class="border rounded-lg overflow-hidden">
                                        <div class="p-3 bg-gray-50 border-b">
                                            <h3 class="font-medium text-gray-800"><?php echo $day; ?></h3>
                                        </div>
                                        <div class="p-4">
                                            <?php if (empty($classes)): ?>
                                                <p class="text-center text-gray-500 py-2 text-sm">No classes</p>
                                            <?php else: ?>
                                                <div class="space-y-3">
                                                    <?php foreach ($classes as $class): ?>
                                                        <div class="p-3 border-l-2 border-primary bg-green-50 rounded-r">
                                                            <div class="text-xs font-medium text-gray-500 mb-1">
                                                                <?php echo format_time($class['start_time']); ?> - <?php echo format_time($class['end_time']); ?>
                                                            </div>
                                                            <div class="font-medium text-sm text-gray-800">
                                                                <?php echo htmlspecialchars($class['course_id']); ?>
                                                            </div>
                                                            <div class="text-xs text-gray-600">
                                                                <?php echo htmlspecialchars($class['room'] . ', ' . $class['building']); ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Export Schedule Link -->
                    <div class="mt-6 text-center">
                        <a href="#" class="inline-flex items-center space-x-2 text-primary hover:underline">
                            <i class="ri-download-line"></i>
                            <span>Export Schedule as PDF</span>
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 