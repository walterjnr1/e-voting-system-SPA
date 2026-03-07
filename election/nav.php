<?php
try {
    $settingsStmt = $dbh->query("SELECT allow_registration FROM website_settings WHERE id = 1");
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    $is_registration_open = ($settings && $settings['allow_registration'] == 1);
} catch (PDOException $e) {
    $is_registration_open = false; // Default to closed on error
}

// Helper function to get initials from full name
function getInitials($name) {
    $words = explode(" ", $name);
    $initials = "";
    foreach ($words as $w) {
        if (!empty($w)) $initials .= strtoupper($w[0]);
    }
    return substr($initials, 0, 1); 
}

$user_initials = !empty($_SESSION['full_name']) ? getInitials($_SESSION['full_name']) : 'U';
$display_name = $_SESSION['full_name'] ?? 'User';
?>

<nav class="bg-blue-900 text-white p-4 shadow-lg sticky top-0 z-50">
    <div class="container mx-auto flex justify-between items-center">
        
        <a href="index" class="flex items-center space-x-3 shrink-0">
            <img src="../<?php echo htmlspecialchars($app_logo); ?>" alt="Logo" class="h-10 w-10 object-contain rounded-full bg-white p-1">
            <div class="leading-none">
                <h1 class="text-lg md:text-xl font-bold tracking-tight">
                    <?php echo htmlspecialchars($app_name); ?>
                </h1>
                <span class="text-[10px] uppercase tracking-widest text-blue-400 font-semibold block md:inline">Secured E-Voting Platform</span>
            </div>
        </a>

        <div class="hidden lg:flex items-center space-x-6">
            <a href="../index" class="hover:text-blue-300 transition font-medium">Home</a>
            
            <?php if (empty($_SESSION['user_id'])): ?>
                <a href="../login" class="hover:text-blue-300 transition font-medium">Login</a>
            <?php endif; ?>

            <?php if ($is_registration_open): ?>
                <?php if (empty($_SESSION['user_id'])): ?>
                    <a href="voter_signup" class="bg-blue-600 px-4 py-2 rounded-lg hover:bg-blue-700 transition shadow-md font-semibold text-sm">
                        Voter Signup
                    </a>
                <?php endif; ?>

                <a href="candidate_signup" class="bg-white text-blue-900 px-4 py-2 rounded-lg hover:bg-gray-100 transition shadow-md font-semibold text-sm">
                    Candidate Nomination
                </a>
            <?php endif; ?>

            <?php if (!empty($_SESSION['user_id'])): ?>
                <div class="relative ml-4 border-l border-blue-800 pl-6">
                    <button id="profile-menu-button" class="flex items-center focus:outline-none group">
                        <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-sm font-bold border-2 border-blue-400 group-hover:border-white transition uppercase">
                            <?php echo $user_initials; ?>
                        </div>
                        <i class="fas fa-chevron-down ml-2 text-xs text-blue-400 group-hover:text-white transition"></i>
                    </button>
                    <div id="profile-dropdown" class="hidden absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-2xl py-2 z-50 border border-gray-100 text-gray-800">
                        <div class="px-4 py-2 border-b border-gray-100 mb-1">
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Account</p>
                            <p class="text-sm font-semibold truncate"><?php echo htmlspecialchars($display_name); ?></p>
                        </div>
                        <a href="profile" class="block px-4 py-2 text-sm hover:bg-blue-50 hover:text-blue-600"><i class="fas fa-user-edit mr-3"></i> Edit Profile</a>
                        <a href="../logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-bold"><i class="fas fa-sign-out-alt mr-3"></i> Logout</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex lg:hidden items-center space-x-4">
            <?php if (!empty($_SESSION['user_id'])): ?>
                <div class="relative">
                    <button id="mobile-profile-button" class="focus:outline-none">
                        <div class="h-9 w-9 rounded-full bg-blue-500 flex items-center justify-center text-xs font-bold border-2 border-blue-400 uppercase">
                            <?php echo $user_initials; ?>
                        </div>
                    </button>
                    
                    <div id="mobile-profile-dropdown" class="hidden absolute right-0 mt-3 w-56 bg-white rounded-xl shadow-xl py-2 z-50 text-gray-800 border border-gray-100">
                        <div class="px-4 py-2 border-b border-gray-100 mb-1">
                            <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">Account</p>
                            <p class="text-xs font-bold text-blue-900 truncate"><?php echo htmlspecialchars($display_name); ?></p>
                        </div>
                        <a href="profile" class="block px-4 py-3 text-xs font-semibold hover:bg-gray-50"><i class="fas fa-user-edit mr-2 text-blue-600"></i> Edit Profile</a>
                        <a href="logout" class="block px-4 py-3 text-xs text-red-600 font-bold hover:bg-red-50"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
                    </div>
                </div>
            <?php endif; ?>

            <button id="mobile-menu-button" class="text-2xl focus:outline-none">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>

    <div id="mobile-menu" class="hidden lg:hidden flex flex-col space-y-2 mt-4 pb-4 border-t border-blue-800 pt-4 px-2 text-sm">
        <a href="../index" class="block text-center hover:bg-blue-800 py-3 rounded-lg font-medium">Home</a>
        
        <?php if (empty($_SESSION['user_id'])): ?>
            <a href="../login" class="block text-center hover:bg-blue-800 py-3 rounded-lg font-medium">Login</a>
        <?php endif; ?>

        <?php if ($is_registration_open): ?>
            <?php if (empty($_SESSION['user_id'])): ?>
                <a href="voter_signup" class="block text-center bg-blue-600 py-3 rounded-lg font-bold shadow-md">Voter Signup</a>
            <?php endif; ?>
            <a href="candidate_signup" class="block text-center bg-white text-blue-900 py-3 rounded-lg font-bold shadow-md">Candidate Nomination</a>
        <?php endif; ?>
    </div>
</nav>

<script>
    const setupToggle = (btnId, menuId) => {
        const btn = document.getElementById(btnId);
        const menu = document.getElementById(menuId);
        if(btn && menu) {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                
                // Toggle current
                const isHidden = menu.classList.contains('hidden');
                
                // Close all first
                const menus = ['profile-dropdown', 'mobile-profile-dropdown', 'mobile-menu'];
                menus.forEach(id => {
                    const m = document.getElementById(id);
                    if(m) m.classList.add('hidden');
                });

                // If it was hidden, show it now
                if(isHidden) menu.classList.remove('hidden');
            });
        }
    };

    setupToggle('mobile-menu-button', 'mobile-menu');
    setupToggle('profile-menu-button', 'profile-dropdown');
    setupToggle('mobile-profile-button', 'mobile-profile-dropdown');

    // Close on outside click
    window.addEventListener('click', () => {
        ['profile-dropdown', 'mobile-profile-dropdown', 'mobile-menu'].forEach(id => {
            const menu = document.getElementById(id);
            if(menu) menu.classList.add('hidden');
        });
    });
</script>