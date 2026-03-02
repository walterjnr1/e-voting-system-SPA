<?php
include 'database/connection.php'; 
include('inc/app_data.php'); 

// Logic to fetch available positions from the database for the dropdown
// $positions = $pdo->query("SELECT id, position_name FROM positions")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Nomination | <?php echo htmlspecialchars($app_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">
</head>
<body class="bg-gray-50 font-sans min-h-screen flex flex-col">

   <nav class="bg-blue-900 text-white p-4 shadow-lg">
       <?php include('nav.php'); ?>
    </nav>
    <main class="flex-grow container mx-auto px-4 py-12">
        <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            
            <div class="bg-gradient-to-r from-blue-700 to-blue-900 p-8 text-white">
                <h2 class="text-3xl font-bold">Candidate Nomination Form</h2>
                <p class="text-blue-200 mt-2">Submit your details to run for an executive position.</p>
            </div>

            <form action="handlers/nomination_handler.php" method="POST" enctype="multipart/form-data" class="p-8 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Full Name</label>
                        <input type="text" name="candidate_name" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" 
                               placeholder="As it should appear on ballot">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Position Applied For</label>
                        <select name="position_id" required 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                            <option value="">-- Select Position --</option>
                            <option value="1">President</option>
                            <option value="2">Vice President</option>
                            <option value="3">Secretary General</option>
                            <option value="4">Financial Secretary</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Brief Manifesto / Bio</label>
                    <textarea name="manifesto" rows="5" required 
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" 
                              placeholder="Tell the alumni why they should vote for you..."></textarea>
                    <p class="text-xs text-gray-500 mt-2">Maximum 500 words.</p>
                </div>

                <div class="bg-gray-50 p-6 rounded-xl border-2 border-dashed border-gray-300">
                    <label class="block text-gray-700 font-semibold mb-2 text-center">Upload Official Portrait</label>
                    <div class="flex flex-col items-center">
                        <i class="fas fa-camera text-4xl text-gray-400 mb-3"></i>
                        <input type="file" name="candidate_photo" accept="image/*" required 
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-400 mt-2">Recommended: Square image (PNG or JPG), max 2MB.</p>
                    </div>
                </div>

                <div class="flex items-start bg-blue-50 p-4 rounded-lg">
                    <input type="checkbox" required class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded">
                    <label class="ml-3 text-sm text-blue-900 leading-relaxed">
                        I hereby declare that the information provided is accurate and I agree to abide by the **<?php echo htmlspecialchars($app_name); ?>** election guidelines as stated in the constitution.
                    </label>
                </div>

                <div class="flex flex-col items-center">
                    <button type="submit" name="submit_nomination" 
                            class="w-full md:w-1/2 bg-blue-600 text-white font-bold py-4 rounded-full shadow-lg hover:bg-blue-700 transform hover:scale-105 transition duration-200">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Application
                    </button>
                    <p class="text-sm text-gray-500 mt-4 italic">Note: All nominations are subject to screening by the Electoral Committee.</p>
                </div>
            </form>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-10">
        <?php include('footer.php'); ?>
    </footer>

</body>
</html>