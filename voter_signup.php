<?php
include 'database/connection.php'; 
include('inc/app_data.php'); 

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | <?php echo htmlspecialchars($app_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">
</head>
<body class="bg-gray-50 font-sans min-h-screen flex flex-col">

   <nav class="bg-blue-900 text-white p-4 shadow-lg">
       <?php include('nav.php'); ?>
    </nav>

    <main class="flex-grow flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            <div class="bg-blue-600 p-6 text-center text-white">
                <h2 class="text-2xl font-bold">Create Account</h2>
                <p class="text-blue-100 text-sm mt-1">Join the <?php echo htmlspecialchars($app_name); ?> Alumni Network</p>
            </div>
            
            <form action="" method="POST" class="p-8 space-y-5">
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Full Name</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" name="full_name" required 
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" 
                               placeholder="Enter your full name">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" name="email" required 
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" 
                               placeholder="name@example.com">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" required 
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" 
                               placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-start">
                    <input type="checkbox" required class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label class="ml-2 block text-sm text-gray-600">
                        I agree to the <a href="election_guideline.pdf" class="text-blue-600 hover:underline">Election Guideline</a>.
                    </label>
                </div>

                <button type="submit" name="register" 
                        class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg shadow-lg hover:bg-blue-700 transform hover:-translate-y-1 transition duration-200">
                    Create Account
                </button>
            </form>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-10 mt-auto">
        <?php include('footer.php'); ?>
    </footer>

</body>
</html>