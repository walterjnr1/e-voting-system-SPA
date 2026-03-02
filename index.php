<?php
include 'database/connection.php'; 
include('inc/app_data.php'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_name); ?> Alumni E-Vote | Secure & Transparent</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">
</head>
<body class="bg-gray-50 font-sans">

    <nav class="bg-blue-900 text-white p-4 shadow-lg sticky top-0 z-50">
       <?php include('nav.php'); ?>
    </nav>

    <header class="bg-white py-16 px-4 border-b">
        <div class="container mx-auto text-center">
            <h2 class="text-4xl md:text-5xl font-extrabold text-gray-800 mb-4 tracking-tight">Your Voice, Our Future.</h2>
            <p class="text-gray-600 text-lg mb-8 max-w-2xl mx-auto">
                Securely cast your vote for the 2026 Alumni Executive Committee. Verified, encrypted, and transparent.
            </p>
            
            <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                <a href="electon/ballot" class="w-full sm:w-auto bg-green-600 text-white px-8 py-4 rounded-full text-lg font-bold hover:bg-green-700 shadow-xl transform hover:scale-105 transition flex items-center justify-center">
                    <i class="fas fa-check-to-slot mr-2"></i> Cast Your Vote
                </a>
                <a href="electon/result_page" class="w-full sm:w-auto bg-blue-600 text-white px-8 py-4 rounded-full text-lg font-bold hover:bg-blue-700 shadow-xl transform hover:scale-105 transition flex items-center justify-center">
                    <i class="fas fa-chart-line mr-2"></i> View Live Results
                </a>
                <a href="#how-it-works" class="w-full sm:w-auto bg-gray-100 text-gray-700 px-8 py-4 rounded-full text-lg font-semibold hover:bg-gray-200 transition">
                    Learn More
                </a>
            </div>
        </div>
    </header>

    <section class="container mx-auto -mt-8 px-4">
        <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-r-lg shadow-md flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center">
                <div class="mr-4 hidden md:block">
                    <i class="fas fa-clock text-blue-500 text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-blue-900 text-lg uppercase tracking-wider text-sm md:text-base">Ongoing Election: 2026 Executives</h3>
                    <p class="text-blue-700 font-medium">Ends in: <span id="countdown" class="font-mono font-bold">2d 14h 05m</span></p>
                </div>
            </div>
            <div class="flex items-center space-x-4 w-full md:w-auto justify-between md:justify-end">
                <a href="results.php" class="text-blue-600 hover:text-blue-800 font-bold text-sm underline flex items-center">
                    <i class="fas fa-eye mr-1"></i> Quick Tally
                </a>
                <span class="bg-green-200 text-green-800 px-4 py-1 rounded-full text-xs font-black animate-pulse flex items-center">
                    <span class="mr-2">●</span> LIVE
                </span>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="py-20 container mx-auto px-4">
        <h3 class="text-3xl font-black text-center text-gray-900 mb-12 uppercase tracking-tighter">Why Vote With Us?</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 text-center hover:shadow-md transition">
                <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600 text-2xl">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h4 class="font-bold text-gray-800 mb-2">Verified Identity</h4>
                <p class="text-gray-500 leading-relaxed text-sm">Only verified alumni from the official database can participate in the electoral process.</p>
            </div>
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 text-center hover:shadow-md transition">
                <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-green-600 text-2xl">
                    <i class="fas fa-fingerprint"></i>
                </div>
                <h4 class="font-bold text-gray-800 mb-2">Anonymous Ballot</h4>
                <p class="text-gray-500 leading-relaxed text-sm">Your identity is completely decoupled from your vote, ensuring 100% privacy and integrity.</p>
            </div>
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 text-center hover:shadow-md transition">
                <div class="bg-yellow-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-yellow-600 text-2xl">
                    <i class="fas fa-bolt"></i>
                </div>
                <h4 class="font-bold text-gray-800 mb-2">Instant Results</h4>
                <p class="text-gray-500 leading-relaxed text-sm">View real-time tallying and analytics as soon as votes are cast and verified by the system.</p>
            </div>
        </div>
    </section>

    <footer class="bg-gray-900 text-gray-400 py-10">
        <?php include('footer.php'); ?>
    </footer>

</body>
</html>