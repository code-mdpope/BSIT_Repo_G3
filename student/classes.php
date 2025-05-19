<?php
require_once '../config.php';
require_once '../dashboard_functions.php';

// Check if user is logged in as student
if (!user_has_role('student')) {
    header('Location: ../index.php');
    exit();
}

// Set current page for navigation
$current_page = 'classes';

// Get student information
$student_id = $_SESSION['user_id'];
$user_info = get_user_info($student_id);
$student_info = get_role_info($student_id, 'student');

// Get courses data
$courses = get_student_courses($student_id);

// Get assignments data
$conn = get_db_connection();
$query = "SELECT 
            a.assignment_id, a.title, a.description, a.due_date, a.total_points, a.class_id,
            c.course_id, c.course_name,
            cl.semester, cl.year,
            IFNULL(s.submission_id, 0) as submission_id,
            IFNULL(s.status, 'not_submitted') as submission_status,
            IFNULL(s.grade, 'Not graded') as grade
          FROM assignments a
          JOIN classes cl ON a.class_id = cl.class_id
          JOIN courses c ON cl.course_id = c.course_id
          JOIN enrollments e ON cl.class_id = e.class_id AND e.student_id = '$student_id'
          LEFT JOIN assignment_submissions s ON a.assignment_id = s.assignment_id AND s.student_id = '$student_id'
          WHERE e.status = 'enrolled'
          ORDER BY a.due_date DESC";
$result = mysqli_query($conn, $query);
$assignments = [];

// Group assignments by class
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $class_id = $row['class_id'];
        if (!isset($assignments[$class_id])) {
            $assignments[$class_id] = [];
        }
        $assignments[$class_id][] = $row;
    }
}

// Function to get submission status badge color
function get_submission_status_color($status) {
    switch ($status) {
        case 'submitted': return 'bg-blue-100 text-blue-800';
        case 'graded': return 'bg-green-100 text-green-800';
        case 'late': return 'bg-yellow-100 text-yellow-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-600';
    }
}

// Check if a specific class is selected
$selected_class = isset($_GET['class_id']) ? $_GET['class_id'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Classes - IDSC Portal</title>
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
                <!-- Classes Content -->
                <div class="flex-1 overflow-y-auto p-4 md:p-6">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">My Classes</h1>
                        <p class="text-gray-600">View your enrolled courses and assignments</p>
                    </div>
                    
                    <?php if (empty($courses)): ?>
                        <div class="bg-white rounded-lg border shadow-sm p-8 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ri-book-line text-2xl text-gray-400"></i>
                            </div>
                            <h3 class="text-gray-800 font-medium text-lg">No Classes Found</h3>
                            <p class="text-gray-600 mt-2">You are not enrolled in any classes for the current term.</p>
                        </div>
                    <?php else: ?>
                        <!-- Classes Overview -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                            <?php foreach ($courses as $course): ?>
                                <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
                                    <div class="p-4 border-b bg-gray-50">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($course['course_name']); ?></h3>
                                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($course['course_id'] . ' • ' . $course['department']); ?></p>
                                            </div>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($course['semester'] . ' ' . $course['year']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="p-4">
                                        <p class="text-sm text-gray-700 mb-4">
                                            <span class="font-medium">Instructor:</span> <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?><br>
                                            <span class="font-medium">Credits:</span> <?php echo htmlspecialchars($course['credits']); ?>
                                        </p>
                                        
                                        <?php if (isset($assignments[$course['class_id']]) && count($assignments[$course['class_id']]) > 0): ?>
                                            <p class="text-xs font-medium text-gray-500 uppercase mb-2">Upcoming Assignments</p>
                                            <ul class="text-sm space-y-2 mb-4">
                                                <?php 
                                                $assignmentCount = 0;
                                                foreach ($assignments[$course['class_id']] as $assignment): 
                                                    if ($assignmentCount >= 3) break;
                                                    list($label, $bg_color) = get_due_date_label($assignment['due_date']);
                                                ?>
                                                    <li class="flex justify-between items-center">
                                                        <span class="truncate"><?php echo htmlspecialchars($assignment['title']); ?></span>
                                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full <?php echo $bg_color; ?> ml-2">
                                                            <?php echo $label; ?>
                                                        </span>
                                                    </li>
                                                <?php 
                                                    $assignmentCount++;
                                                endforeach; 
                                                ?>
                                            </ul>
                                        <?php else: ?>
                                            <p class="text-sm text-gray-500 mb-4">No upcoming assignments</p>
                                        <?php endif; ?>
                                        
                                        <a href="?class_id=<?php echo $course['class_id']; ?>" class="block text-center w-full py-2 bg-primary text-white rounded hover:bg-primary/90 transition">
                                            View Class Details
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($selected_class): ?>
                            <?php 
                            // Find selected course
                            $selected_course = null;
                            foreach ($courses as $course) {
                                if ($course['class_id'] == $selected_class) {
                                    $selected_course = $course;
                                    break;
                                }
                            }
                            
                            if ($selected_course): 
                            ?>
                                <!-- Class Details -->
                                <div class="bg-white rounded-lg border shadow-sm mb-8">
                                    <div class="p-5 border-b">
                                        <div class="flex justify-between items-center">
                                            <h2 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($selected_course['course_id'] . ' - ' . $selected_course['course_name']); ?></h2>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($selected_course['semester'] . ' ' . $selected_course['year']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="p-5">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                            <div>
                                                <h3 class="font-medium text-gray-800 mb-2">Course Information</h3>
                                                <ul class="space-y-2 text-sm text-gray-600">
                                                    <li><span class="font-medium">Department:</span> <?php echo htmlspecialchars($selected_course['department']); ?></li>
                                                    <li><span class="font-medium">Credits:</span> <?php echo htmlspecialchars($selected_course['credits']); ?></li>
                                                    <li>
                                                        <span class="font-medium">Instructor:</span> 
                                                        <?php echo htmlspecialchars($selected_course['first_name'] . ' ' . $selected_course['last_name']); ?>
                                                    </li>
                                                    <li><span class="font-medium">Status:</span> <?php echo htmlspecialchars(ucfirst($selected_course['status'])); ?></li>
                                                </ul>
                                            </div>
                                            <div>
                                                <h3 class="font-medium text-gray-800 mb-2">Class Resources</h3>
                                                <ul class="space-y-2 text-sm">
                                                    <li>
                                                        <a href="#" class="flex items-center text-primary hover:underline">
                                                            <i class="ri-file-list-line mr-2"></i>
                                                            <span>Course Syllabus</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#" class="flex items-center text-primary hover:underline">
                                                            <i class="ri-book-2-line mr-2"></i>
                                                            <span>Course Materials</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#" class="flex items-center text-primary hover:underline">
                                                            <i class="ri-discussion-line mr-2"></i>
                                                            <span>Discussion Board</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#" class="flex items-center text-primary hover:underline">
                                                            <i class="ri-team-line mr-2"></i>
                                                            <span>Contact Instructor</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <!-- Assignments -->
                                        <h3 class="font-medium text-gray-800 mb-4 border-t pt-6">Assignments</h3>
                                        <?php if (isset($assignments[$selected_class]) && !empty($assignments[$selected_class])): ?>
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200">
                                                        <?php foreach ($assignments[$selected_class] as $assignment): ?>
                                                            <tr>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                                    <?php echo htmlspecialchars($assignment['title']); ?>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                    <?php echo format_date($assignment['due_date'], 'M j, Y - g:i A'); ?>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap">
                                                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo get_submission_status_color($assignment['submission_status']); ?> capitalize">
                                                                        <?php 
                                                                        $status = str_replace('_', ' ', $assignment['submission_status']);
                                                                        echo htmlspecialchars($status); 
                                                                        ?>
                                                                    </span>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                    <?php 
                                                                    if ($assignment['submission_status'] === 'graded') {
                                                                        echo htmlspecialchars($assignment['grade'] . ' / ' . $assignment['total_points']);
                                                                    } else {
                                                                        echo htmlspecialchars($assignment['grade']);
                                                                    }
                                                                    ?>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                                    <?php if ($assignment['submission_status'] === 'not_submitted' || $assignment['submission_status'] === 'rejected'): ?>
                                                                        <a href="#" class="text-primary hover:underline">Submit</a>
                                                                    <?php elseif ($assignment['submission_status'] === 'submitted'): ?>
                                                                        <a href="#" class="text-primary hover:underline">View</a>
                                                                    <?php else: ?>
                                                                        <a href="#" class="text-primary hover:underline">View Feedback</a>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-6">
                                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                                    <i class="ri-file-list-line text-2xl text-gray-400"></i>
                                                </div>
                                                <h3 class="text-gray-500 font-medium">No assignments found</h3>
                                                <p class="text-sm text-gray-400 mt-1">No assignments have been posted for this class yet.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 