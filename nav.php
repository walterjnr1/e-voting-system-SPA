<nav class="bg-blue-900 text-white p-4 shadow-lg sticky top-0 z-50">
    <div class="container mx-auto flex justify-between items-center">
        
        <a href="index" class="flex items-center space-x-3 shrink-0">
            <img src="<?php echo htmlspecialchars($app_logo); ?>" alt="Logo" class="h-10 w-10 object-contain rounded-full bg-white p-1">
            <div class="leading-none">
                <h1 class="text-lg md:text-xl font-bold tracking-tight">
                    <?php echo htmlspecialchars($app_name); ?>
                </h1>
                <span class="text-[10px] uppercase tracking-widest text-blue-400 font-semibold block md:inline">Secured E-Voting Platform</span>
            </div>
        </a>

        <div class="hidden lg:flex items-center space-x-6">
            <a href="index" class="hover:text-blue-300 transition font-medium">Home</a>
            <a href="login" class="hover:text-blue-300 transition font-medium">Login</a>
            <a href="voter_signup" class="bg-blue-600 px-4 py-2 rounded-lg hover:bg-blue-700 transition shadow-md font-semibold text-sm">
                Voter Signup
            </a>
            <a href="candidate_signup" class="bg-white text-blue-900 px-4 py-2 rounded-lg hover:bg-gray-100 transition shadow-md font-semibold text-sm">
                Candidate Registration
            </a>

            <div class="relative ml-4 border-l border-blue-800 pl-6">
                <button id="profile-menu-button" class="flex items-center focus:outline-none group">
                    <img src="<?php echo $user_image ?? 'assets/default-avatar.png'; ?>" class="h-9 w-9 rounded-full object-cover border-2 border-blue-400 group-hover:border-white transition">
                    <i class="fas fa-chevron-down ml-2 text-xs text-blue-400 group-hover:text-white transition"></i>
                </button>
                <div id="profile-dropdown" class="hidden absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-2xl py-2 z-50 border border-gray-100 text-gray-800">
                    <a href="profile/edit" class="block px-4 py-2 text-sm hover:bg-blue-50 hover:text-blue-600"><i class="fas fa-user-edit mr-3"></i> Edit Profile</a>
                                        <a href="election/result" class="block px-4 py-2 text-sm hover:bg-blue-50 hover:text-blue-600"><i class="fas fa-user-edit mr-3"></i> Result</a>

                    <a href="logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-bold"><i class="fas fa-sign-out-alt mr-3"></i> Logout</a>
                </div>
            </div>
        </div>

        <div class="flex lg:hidden items-center space-x-4">
            <div class="relative">
                <button id="mobile-profile-button" class="focus:outline-none">
                    <img src="<?php echo $user_image ?? 'assets/default-avatar.png'; ?>" class="h-9 w-9 rounded-full border-2 border-blue-400">
                </button>
                <div id="mobile-profile-dropdown" class="hidden absolute right-0 mt-3 w-40 bg-white rounded-lg shadow-xl py-2 z-50 text-gray-800 border border-gray-100">
                    <a href="profile/edit" class="block px-4 py-2 text-xs font-bold"><i class="fas fa-user-edit mr-2"></i> Profile</a>
                    <a href="logout" class="block px-4 py-2 text-xs text-red-600 font-bold"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
                </div>
            </div>

            <button id="mobile-menu-button" class="text-2xl focus:outline-none">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>

    <div id="mobile-menu" class="hidden lg:hidden flex flex-col space-y-2 mt-4 pb-4 border-t border-blue-800 pt-4 px-2">
        <a href="login" class="block text-center hover:bg-blue-800 py-3 rounded-lg">Login</a>
         <a href="index" class="block text-center hover:bg-blue-800 py-3 rounded-lg">Home</a>
        <a href="voter_signup" class="block text-center bg-blue-600 py-3 rounded-lg font-semibold">Voter Signup</a>
        <a href="candidate_signup" class="block text-center bg-white text-blue-900 py-3 rounded-lg font-semibold">Candidate Nomination</a>
        <a href="election/result" class="block text-center bg-white text-blue-900 py-3 rounded-lg font-semibold">Election Result</a>

    </div>
</nav>

<script>
    // Utility function to handle toggles
    const setupToggle = (btnId, menuId) => {
        const btn = document.getElementById(btnId);
        const menu = document.getElementById(menuId);
        if(btn && menu) {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                menu.classList.toggle('hidden');
                // Close other menus if one is opened
                ['profile-dropdown', 'mobile-profile-dropdown', 'mobile-menu'].forEach(id => {
                    if(id !== menuId) document.getElementById(id).classList.add('hidden');
                });
            });
        }
    };

    setupToggle('mobile-menu-button', 'mobile-menu');
    setupToggle('profile-menu-button', 'profile-dropdown');
    setupToggle('mobile-profile-button', 'mobile-profile-dropdown');

    // Close all menus when clicking outside
    window.addEventListener('click', () => {
        document.getElementById('profile-dropdown').classList.add('hidden');
        document.getElementById('mobile-profile-dropdown').classList.add('hidden');
        document.getElementById('mobile-menu').classList.add('hidden');
    });
</script>