<?php
require_once 'config.php';
require_once 'auth.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check for prefill parameters (from login debug tool)
$prefill_userid = isset($_GET['prefill']) ? $_GET['prefill'] : '';
$prefill_role = isset($_GET['role']) ? $_GET['role'] : '';

// Redirect if already logged in
if (is_user_logged_in()) {
    redirect_to_dashboard($_SESSION['role']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IDSC Portal - Login</title>
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
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="container mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <h1 class="font-['Pacifico'] text-3xl text-primary">IDSC</h1>
                        <span class="text-gray-600 text-lg">Portal</span>
                    </div>
                    <div class="hidden md:flex items-center space-x-6">
                        <a href="#" class="text-gray-600 hover:text-primary">About</a>
                        <a href="#" class="text-gray-600 hover:text-primary">Contact</a>
                        <a href="#" class="text-gray-600 hover:text-primary">Help</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 max-w-5xl w-full">
                <!-- Left Side - Welcome Message -->
                <div class="hidden md:flex flex-col justify-center">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6">Welcome to IDSC Portal</h2>
                    <p class="text-gray-600 mb-8">Your comprehensive school management system for students, instructors, and administrators.</p>
                    
                    <div class="space-y-6">
                        <div class="flex items-start space-x-4">
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                <i class="ri-user-line text-primary"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-gray-800">Students</h3>
                                <p class="text-sm text-gray-600">Access your courses, schedules, grades, and assignments.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-4">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <i class="ri-user-star-line text-blue-600"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-gray-800">Instructors</h3>
                                <p class="text-sm text-gray-600">Manage your classes, grades, and communicate with students.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-4">
                            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                                <i class="ri-admin-line text-purple-600"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-gray-800">Administrators</h3>
                                <p class="text-sm text-gray-600">Oversee school operations, courses, staff, and student records.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side - Login Form -->
                <div class="bg-white p-8 rounded-lg shadow-sm border">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Log in to your account</h2>
                    
                    <?php if (isset($error_message)): ?>
                        <?php echo display_error($error_message); ?>
                    <?php endif; ?>
                    
                    <form method="POST" action="index.php">
                        <input type="hidden" name="action" value="login">
                        <div class="space-y-5">
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Select Role</label>
                                <select id="role" name="role" class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                    <option value="">-- Select your role --</option>
                                    <option value="student" <?php echo ($prefill_role === 'student') ? 'selected' : ''; ?>>Student</option>
                                    <option value="instructor" <?php echo ($prefill_role === 'instructor') ? 'selected' : ''; ?>>Instructor</option>
                                    <option value="admin" <?php echo ($prefill_role === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="userid" class="block text-sm font-medium text-gray-700 mb-1">User ID</label>
                                <input type="text" id="userid" name="userid" placeholder="Enter your user ID" value="<?php echo htmlspecialchars($prefill_userid); ?>" class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                <p class="text-xs text-gray-500 mt-1">Sample IDs: STU-202587 (student), INS-2025103 (instructor), ADM-2025001 (admin)</p>
                            </div>
                            
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                <input type="password" id="password" name="password" placeholder="Enter your password" value="<?php echo ($prefill_userid) ? 'password123' : ''; ?>" class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                <p class="text-xs text-gray-500 mt-1">Use 'password123' for all sample accounts</p>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary">
                                    <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
                                </div>
                                <a href="#" class="text-sm text-primary hover:underline">Forgot password?</a>
                            </div>
                            
                            <div>
                                <button type="submit" class="w-full bg-primary text-white px-4 py-2 rounded hover:bg-primary/90 transition">
                                    Log in
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="mt-6 text-center text-sm text-gray-500">
                        <p>Don't have an account? <a href="#" class="text-primary hover:underline">Contact your administrator</a></p>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <a href="setup.php" class="text-xs text-amber-600 hover:underline">Setup Database</a>
                        <span class="mx-2 text-gray-300">|</span>
                        <a href="login_debug.php" class="text-xs text-blue-600 hover:underline">Login Troubleshooter</a>
                        <span class="mx-2 text-gray-300">|</span>
                        <a href="fix_passwords.php" class="text-xs text-red-600 hover:underline">Fix Password Issues</a>
                    </div>
                    
                    <?php if (isset($_GET['debug']) && $_GET['debug'] === 'true'): ?>
                    <div class="mt-6 p-4 border border-gray-200 rounded-md bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">PHP Info:</h3>
                        <ul class="text-xs space-y-1 text-gray-600">
                            <li>PHP Version: <?php echo phpversion(); ?></li>
                            <li>Password Bcrypt Support: <?php echo defined('PASSWORD_BCRYPT') ? 'Yes' : 'No'; ?></li>
                            <li>password_hash(): <?php echo function_exists('password_hash') ? 'Available' : 'Not Available'; ?></li>
                            <li>password_verify(): <?php echo function_exists('password_verify') ? 'Available' : 'Not Available'; ?></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="bg-white border-t py-6">
            <div class="container mx-auto px-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="mb-4 md:mb-0">
                        <p class="text-sm text-gray-500">&copy; 2025 IDSC Portal. All rights reserved.</p>
                    </div>
                    <div class="flex space-x-6">
                        <a href="#" class="text-sm text-gray-500 hover:text-primary">Privacy Policy</a>
                        <a href="#" class="text-sm text-gray-500 hover:text-primary">Terms of Service</a>
                        <a href="#" class="text-sm text-gray-500 hover:text-primary">Contact Support</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</body>
</html> 