<?php
// This is a reusable navigation template for student pages
// It should be included in all student pages
// Usage: include 'inc_nav.php'; with $current_page set to the current page name

// If $current_page is not set, default to dashboard
if (!isset($current_page)) {
    $current_page = 'dashboard';
}

// Function to check if a page is active
function is_active($page, $current_page) {
    return $page === $current_page;
}

// Page definitions with their icons and titles
$pages = [
    'dashboard' => ['icon' => 'ri-dashboard-line', 'title' => 'Dashboard'],
    'classes' => ['icon' => 'ri-book-line', 'title' => 'Classes'],
    'schedules' => ['icon' => 'ri-calendar-line', 'title' => 'Schedules'],
    'grades' => ['icon' => 'ri-file-list-line', 'title' => 'Grades'],
    'payments' => ['icon' => 'ri-money-dollar-circle-line', 'title' => 'Payments']
];
?>

<!-- Sidebar for Desktop -->
<aside class="w-64 bg-white shadow-sm border-r hidden lg:block">
    <div class="p-5 border-b">
        <div class="flex items-center space-x-2">
            <h1 class="font-['Pacifico'] text-2xl text-primary">IDSC</h1>
            <span class="text-gray-600 text-lg">Portal</span>
        </div>
    </div>
    
    <nav class="p-4 space-y-1">
        <?php foreach ($pages as $page => $details): ?>
            <a href="<?php echo $page; ?>.php" class="flex items-center space-x-2 px-3 py-2 rounded-md <?php echo is_active($page, $current_page) ? 'bg-green-50 text-primary font-medium' : 'text-gray-700 hover:bg-green-50 hover:text-primary transition-colors'; ?>">
                <i class="<?php echo $details['icon']; ?>"></i>
                <span><?php echo $details['title']; ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>

<!-- Mobile Navigation Menu (hidden by default) -->
<div id="mobileMenu" class="fixed inset-0 z-20 transform transition-transform duration-300 ease-in-out translate-x-full">
    <div class="absolute inset-0 bg-gray-600 bg-opacity-75" id="mobileMenuOverlay"></div>
    <div class="absolute right-0 top-0 h-full w-64 bg-white shadow-lg transform transition-all duration-300 ease-in-out">
        <div class="p-5 border-b flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <h1 class="font-['Pacifico'] text-2xl text-primary">IDSC</h1>
                <span class="text-gray-600 text-lg">Portal</span>
            </div>
            <button id="closeMobileMenu" class="text-gray-500 hover:text-gray-700">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>
        <nav class="p-4 space-y-1">
            <?php foreach ($pages as $page => $details): ?>
                <a href="<?php echo $page; ?>.php" class="flex items-center space-x-2 px-3 py-2 rounded-md <?php echo is_active($page, $current_page) ? 'bg-green-50 text-primary font-medium' : 'text-gray-700 hover:bg-green-50 hover:text-primary transition-colors'; ?>">
                    <i class="<?php echo $details['icon']; ?>"></i>
                    <span><?php echo $details['title']; ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</div>

<!-- Header with Mobile Menu Button -->
<header class="bg-white shadow-sm border-b sticky top-0 z-10">
    <div class="flex items-center justify-between px-4 py-3">
        <div class="lg:hidden">
            <button type="button" id="openMobileMenu" class="text-gray-600 hover:text-primary">
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

<!-- Scripts for Mobile Menu -->
<script>
    // Mobile menu functionality
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenu = document.getElementById('mobileMenu');
        const openMobileMenuBtn = document.getElementById('openMobileMenu');
        const closeMobileMenuBtn = document.getElementById('closeMobileMenu');
        const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
        
        // Open mobile menu
        openMobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.remove('translate-x-full');
            document.body.classList.add('overflow-hidden');
        });
        
        // Close mobile menu
        function closeMenu() {
            mobileMenu.classList.add('translate-x-full');
            document.body.classList.remove('overflow-hidden');
        }
        
        closeMobileMenuBtn.addEventListener('click', closeMenu);
        mobileMenuOverlay.addEventListener('click', closeMenu);
    });
</script> 