<?php
include 'database/connection.php'; 
include('inc/app_data.php'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo htmlspecialchars($app_name); ?> Secured E-Voting</title>
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
            
            <div class="bg-blue-900 p-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-800 rounded-full mb-4">
                    <i class="fas fa-lock text-blue-400 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-white">Voter Login</h2>
                <p class="text-blue-200 text-sm mt-1">Access the secured voting portal</p>
            </div>
            
            <form action="handlers/login_handler.php" method="POST" class="p-8 space-y-6">
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" name="email" required 
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" 
                               placeholder="yourname@alumni.com">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between mb-2">
                        <label class="text-gray-700 text-sm font-semibold">Password</label>
                        <a href="forgot-password.php" class="text-xs text-blue-600 hover:underline">Forgot Password?</a>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fas fa-shield-alt"></i>
                        </span>
                        <input type="password" name="password" required 
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" 
                               placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="remember" class="ml-2 block text-sm text-gray-600">Remember this device</label>
                </div>

                <button type="submit" name="login_btn" 
                        class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg shadow-lg hover:bg-blue-700 transform hover:-translate-y-1 transition duration-200">
                    Secure Login <i class="fas fa-sign-in-alt ml-2"></i>
                </button>

                <p class="text-center text-sm text-gray-600 mt-4">
                    New here? <a href="register.php" class="text-blue-600 font-bold hover:underline">Create an Account</a>
                </p>
            </form>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-10 mt-auto">
        <?php include('footer.php'); ?>
    </footer>

</body>
</html>