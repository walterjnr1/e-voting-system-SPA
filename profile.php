<?php
include 'database/connection.php'; 
include('inc/app_data.php'); 

// Fetch current user data from session or database
// $user_id = $_SESSION['user_id'];
// $user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
// $user->execute([$user_id]);
// $current_user = $user->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | <?php echo htmlspecialchars($app_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">
</head>
<body class="bg-gray-50 font-sans min-h-screen flex flex-col">

   <nav class="bg-blue-900 text-white p-4 shadow-lg sticky top-0 z-50">
       <?php include('nav.php'); ?>
    </nav>

    <main class="flex-grow container mx-auto px-4 py-12">
        <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            
            <div class="bg-gradient-to-r from-blue-700 to-blue-900 p-8 text-white relative">
                <div class="relative z-10">
                    <h2 class="text-3xl font-bold">Profile Settings</h2>
                    <p class="text-blue-200 mt-2">Manage your personal information and account security.</p>
                </div>
                <i class="fas fa-user-circle absolute right-8 top-1/2 -translate-y-1/2 text-8xl text-white opacity-10"></i>
            </div>

            <form action="handlers/profile_update_handler.php" method="POST" enctype="multipart/form-data" class="p-8 space-y-6">
                
                <div class="flex flex-col items-center mb-6">
                    <div class="relative">
                        <img id="preview" src="<?php echo $user_image ?? 'assets/default-avatar.png'; ?>" 
                             class="h-24 w-24 rounded-full object-cover border-4 border-blue-100 shadow-md">
                        <div class="absolute bottom-0 right-0 bg-blue-600 text-white p-1.5 rounded-full border-2 border-white text-xs">
                            <i class="fas fa-pen"></i>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2 text-sm">Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($current_user['full_name'] ?? ''); ?>" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition" 
                               placeholder="Enter your name">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2 text-sm">Email Address</label>
                        <input type="email" value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>" disabled 
                               class="w-full px-4 py-3 border border-gray-200 bg-gray-50 text-gray-400 rounded-lg cursor-not-allowed" 
                               placeholder="email@example.com">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2 text-sm">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition" 
                               placeholder="e.g. 08012345678">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2 text-sm">Update Password</label>
                        <input type="password" name="new_password" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition" 
                               placeholder="Leave blank to keep current">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-sm">About Me / Bio</label>
                    <textarea name="bio" rows="3" 
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition" 
                              placeholder="A brief description about yourself..."><?php echo htmlspecialchars($current_user['bio'] ?? ''); ?></textarea>
                </div>

                <div class="bg-gray-50 p-6 rounded-xl border-2 border-dashed border-gray-300">
                    <label class="block text-gray-700 font-semibold mb-2 text-center text-sm">Change Profile Photo</label>
                    <div class="flex flex-col items-center">
                        <input type="file" name="profile_photo" id="photoInput" accept="image/*" 
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition cursor-pointer">
                        <p class="text-xs text-gray-400 mt-2">Recommended: Square PNG or JPG, max 1MB.</p>
                    </div>
                </div>

                <div class="flex flex-col items-center pt-4">
                    <button type="submit" name="update_profile" 
                            class="w-full md:w-1/2 bg-blue-600 text-white font-bold py-4 rounded-full shadow-lg hover:bg-blue-700 transform hover:scale-105 transition duration-200 uppercase tracking-wider">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                    <a href="dashboard.php" class="mt-4 text-sm text-gray-500 hover:text-blue-600 font-semibold transition">Cancel and Go Back</a>
                </div>
            </form>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-10">
        <?php include('footer.php'); ?>
    </footer>

    <script>
        const photoInput = document.getElementById('photoInput');
        const preview = document.getElementById('preview');

        photoInput.onchange = evt => {
            const [file] = photoInput.files;
            if (file) {
                preview.src = URL.createObjectURL(file);
            }
        }
    </script>

</body>
</html>