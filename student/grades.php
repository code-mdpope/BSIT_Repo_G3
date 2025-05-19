<?php
require_once '../config.php';
require_once '../dashboard_functions.php';

// Check if user is logged in as student
if (!user_has_role('student')) {
    header('Location: ../index.php');
    exit();
}

// Set current page for navigation
$current_page = 'grades';

// Get student information
$student_id = $_SESSION['user_id'];
$user_info = get_user_info($student_id);
$student_info = get_role_info($student_id, 'student');
$academic_progress = get_student_academic_progress($student_id);

// Get current grades
$current_grades = get_student_current_grades($student_id);

// Get full transcript
$conn = get_db_connection();
$query = "SELECT 
            c.course_id, c.course_name, c.department, c.credits,
            cl.semester, cl.year, 
            e.grade, e.status,
            u.first_name, u.last_name, u.user_id as instructor_id
          FROM enrollments e
          JOIN classes cl ON e.class_id = cl.class_id
          JOIN courses c ON cl.course_id = c.course_id
          JOIN users u ON cl.instructor_id = u.user_id
          WHERE e.student_id = '$student_id'
          ORDER BY cl.year DESC, FIELD(cl.semester, 'Fall', 'Summer', 'Spring'), c.course_id";
$result = mysqli_query($conn, $query);
$transcript = [];

// Group by semester and year
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $term = $row['semester'] . ' ' . $row['year'];
        if (!isset($transcript[$term])) {
            $transcript[$term] = [];
        }
        $transcript[$term][] = $row;
    }
}

// GPA Chart data - last 5 terms
$gpa_terms = [];
$gpa_values = [];
$term_count = 0;
foreach ($transcript as $term => $courses) {
    if ($term_count >= 5) break;
    
    $term_credits = 0;
    $term_points = 0;
    $term_gpa = 0;
    
    foreach ($courses as $course) {
        if (!empty($course['grade']) && $course['grade'] != 'N/A') {
            $grade_points = 0;
            switch ($course['grade']) {
                case 'A': $grade_points = 4.0; break;
                case 'A-': $grade_points = 3.7; break;
                case 'B+': $grade_points = 3.3; break;
                case 'B': $grade_points = 3.0; break;
                case 'B-': $grade_points = 2.7; break;
                case 'C+': $grade_points = 2.3; break;
                case 'C': $grade_points = 2.0; break;
                case 'C-': $grade_points = 1.7; break;
                case 'D+': $grade_points = 1.3; break;
                case 'D': $grade_points = 1.0; break;
                case 'F': $grade_points = 0.0; break;
            }
            
            $term_credits += $course['credits'];
            $term_points += $grade_points * $course['credits'];
        }
    }
    
    if ($term_credits > 0) {
        $term_gpa = round($term_points / $term_credits, 2);
    }
    
    $gpa_terms[] = $term;
    $gpa_values[] = $term_gpa;
    $term_count++;
}

// Reverse arrays to show chronological order
$gpa_terms = array_reverse($gpa_terms);
$gpa_values = array_reverse($gpa_values);

// Function to convert grade to badge color
function get_grade_badge_color($grade) {
    if (empty($grade) || $grade == 'N/A') {
        return 'bg-gray-100 text-gray-600';
    }
    
    switch ($grade[0]) {
        case 'A': return 'bg-green-100 text-green-800';
        case 'B': return 'bg-blue-100 text-blue-800';
        case 'C': return 'bg-amber-100 text-amber-800';
        case 'D': return 'bg-orange-100 text-orange-800';
        case 'F': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-600';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Grades - IDSC Portal</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:'#2E7D32',secondary:'#43A047'},borderRadius:{'none':'0px','sm':'4px',DEFAULT:'8px','md':'12px','lg':'16px','xl':'20px','2xl':'24px','3xl':'32px','full':'9999px','button':'8px'}}}}</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <!-- Grades Content -->
                <div class="flex-1 overflow-y-auto p-4 md:p-6">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">Academic Performance</h1>
                        <p class="text-gray-600">View your grades and academic progress</p>
                    </div>
                    
                    <!-- Academic Summary -->
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
                        
                        <div class="bg-white rounded-lg border p-5 shadow-sm md:col-span-2">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">GPA Trend</h2>
                            <div style="height: 150px;">
                                <canvas id="gpaChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Current Grades -->
                    <div class="bg-white rounded-lg border shadow-sm mb-6">
                        <div class="p-5 border-b">
                            <h2 class="text-lg font-semibold text-gray-800">Current Term Grades</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course ID</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($current_grades)): ?>
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No grades available for the current term.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($current_grades as $grade): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($grade['course_name']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($grade['course_id']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo get_grade_badge_color($grade['grade']); ?>">
                                                        <?php echo htmlspecialchars($grade['grade']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Transcript -->
                    <div class="bg-white rounded-lg border shadow-sm">
                        <div class="p-5 border-b">
                            <h2 class="text-lg font-semibold text-gray-800">Transcript</h2>
                        </div>
                        <div class="p-5 space-y-6">
                            <?php if (empty($transcript)): ?>
                                <div class="text-center py-6">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="ri-file-list-line text-2xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-gray-500 font-medium">No transcript records found</h3>
                                </div>
                            <?php else: ?>
                                <?php foreach ($transcript as $term => $courses): ?>
                                    <div class="border rounded-lg overflow-hidden">
                                        <div class="bg-gray-50 px-4 py-3 border-b">
                                            <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($term); ?></h3>
                                        </div>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course ID</th>
                                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instructor</th>
                                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Credits</th>
                                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    <?php foreach ($courses as $course): ?>
                                                        <tr>
                                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($course['course_id']); ?></td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($course['course_name']); ?></td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($course['credits']); ?></td>
                                                            <td class="px-4 py-3 whitespace-nowrap">
                                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo get_grade_badge_color($course['grade']); ?>">
                                                                    <?php echo htmlspecialchars(!empty($course['grade']) ? $course['grade'] : 'N/A'); ?>
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                                <?php 
                                                                $status_color = '';
                                                                switch ($course['status']) {
                                                                    case 'enrolled': $status_color = 'text-blue-600'; break;
                                                                    case 'completed': $status_color = 'text-green-600'; break;
                                                                    case 'dropped': $status_color = 'text-red-600'; break;
                                                                }
                                                                ?>
                                                                <span class="<?php echo $status_color; ?> capitalize"><?php echo htmlspecialchars($course['status']); ?></span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Export Transcript Link -->
                    <div class="mt-6 text-center">
                        <a href="#" class="inline-flex items-center space-x-2 text-primary hover:underline">
                            <i class="ri-download-line"></i>
                            <span>Export Transcript as PDF</span>
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // GPA Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('gpaChart').getContext('2d');
            const terms = <?php echo json_encode($gpa_terms); ?>;
            const gpaValues = <?php echo json_encode($gpa_values); ?>;
            
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: terms,
                    datasets: [{
                        label: 'GPA',
                        data: gpaValues,
                        backgroundColor: 'rgba(46, 125, 50, 0.2)',
                        borderColor: 'rgba(46, 125, 50, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(46, 125, 50, 1)',
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 0,
                            max: 4,
                            ticks: {
                                stepSize: 0.5
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
    </script>
</body>
</html> 