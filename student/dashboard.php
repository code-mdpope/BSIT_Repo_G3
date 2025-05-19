<?php
require_once '../config.php';
require_once '../dashboard_functions.php';

// Check if user is logged in as student
if (!user_has_role('student')) {
    header('Location: ../index.php');
    exit();
}

// Set current page for navigation
$current_page = 'dashboard';

// Get student information
$student_id = $_SESSION['user_id'];
$user_info = get_user_info($student_id);
$student_info = get_role_info($student_id, 'student');
$courses = get_student_courses($student_id);
$today_schedule = get_student_today_schedule($student_id);
$upcoming_assignments = get_student_upcoming_assignments($student_id);
$announcements = get_recent_announcements();
$academic_progress = get_student_academic_progress($student_id);
$current_grades = get_student_current_grades($student_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - IDSC Portal</title>
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
                <!-- Dashboard Content -->
                <div class="flex-1 overflow-y-auto p-4 md:p-6">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">Student Dashboard</h1>
                        <p class="text-gray-600">Welcome back, <?php echo htmlspecialchars($user_info['first_name']); ?>!</p>
                    </div>
                    
                    <!-- Academic Progress -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-white rounded-lg border p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-800">GPA</h2>
                                <span class="text-sm font-medium px-2 py-1 rounded-full bg-primary/10 text-primary"><?php echo htmlspecialchars($academic_progress['program']); ?></span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                                    <span class="text-xl font-bold text-primary"><?php echo number_format($academic_progress['gpa'], 2); ?></span>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Year Level: <?php echo htmlspecialchars($academic_progress['year_level']); ?></p>
                                    <p class="text-sm text-gray-600">Credits: <?php echo htmlspecialchars($academic_progress['credits_earned']); ?>/<?php echo htmlspecialchars($academic_progress['credits_required']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg border p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-800">Classes</h2>
                                <span class="text-sm font-medium px-2 py-1 rounded-full bg-blue-100 text-blue-600">Spring 2025</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-xl font-bold text-blue-600"><?php echo count($courses); ?></span>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm text-gray-600">Enrolled Courses</p>
                                    <a href="classes.php" class="text-sm text-primary hover:underline">View all</a>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg border p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-800">Assignments</h2>
                                <span class="text-sm font-medium px-2 py-1 rounded-full bg-amber-100 text-amber-600">3 Upcoming</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center">
                                    <span class="text-xl font-bold text-amber-600"><?php echo count($upcoming_assignments); ?></span>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm text-gray-600">Due This Week</p>
                                    <a href="classes.php" class="text-sm text-primary hover:underline">View all</a>
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
                    
                    <!-- Upcoming Assignments -->
                    <div class="bg-white rounded-lg border shadow-sm mb-6">
                        <div class="p-5 border-b">
                            <h2 class="text-lg font-semibold text-gray-800">Upcoming Assignments</h2>
                        </div>
                        <div class="p-5">
                            <?php if (empty($upcoming_assignments)): ?>
                                <div class="text-center py-6">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="ri-article-line text-2xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-gray-500 font-medium">No upcoming assignments</h3>
                                    <p class="text-sm text-gray-400 mt-1">You're all caught up!</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($upcoming_assignments as $assignment): ?>
                                        <?php 
                                            list($label, $bg_color) = get_due_date_label($assignment['due_date']);
                                        ?>
                                        <div class="border rounded-md overflow-hidden">
                                            <div class="p-4">
                                                <div class="flex items-center justify-between mb-2">
                                                    <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $bg_color; ?>"><?php echo $label; ?></span>
                                                </div>
                                                <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($assignment['course_id'] . ' - ' . $assignment['course_name']); ?></p>
                                                <p class="text-sm text-gray-500">Due: <?php echo format_date($assignment['due_date'], 'M j, Y - g:i A'); ?></p>
                                            </div>
                                            <div class="bg-gray-50 px-4 py-2 border-t flex justify-between items-center">
                                                <span class="text-xs text-gray-500">Worth: <?php echo htmlspecialchars($assignment['total_points']); ?> points</span>
                                                <a href="#" class="text-sm text-primary hover:underline">Submit</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Announcements -->
                    <div class="bg-white rounded-lg border shadow-sm">
                        <div class="p-5 border-b">
                            <h2 class="text-lg font-semibold text-gray-800">Recent Announcements</h2>
                        </div>
                        <div class="p-5">
                            <?php if (empty($announcements)): ?>
                                <div class="text-center py-6">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="ri-notification-2-line text-2xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-gray-500 font-medium">No recent announcements</h3>
                                </div>
                            <?php else: ?>
                                <div class="space-y-6">
                                    <?php foreach ($announcements as $announcement): ?>
                                        <div class="border-b pb-6 last:border-b-0 last:pb-0">
                                            <h3 class="font-medium text-gray-800 mb-1"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                            <div class="flex items-center text-sm text-gray-500 mb-3">
                                                <span><?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?></span>
                                                <span class="mx-2">·</span>
                                                <span><?php echo format_date($announcement['published_date'], 'M j, Y'); ?></span>
                                            </div>
                                            <p class="text-gray-600"><?php echo htmlspecialchars($announcement['content']); ?></p>
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