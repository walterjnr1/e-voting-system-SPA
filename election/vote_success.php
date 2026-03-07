<?php 
include '../database/connection.php';
include('../inc/app_data.php');
require '../email_vendor/autoload.php';

// Hardcoded data for demonstration
//$app_name = "SecureBallot Alumni";
//$app_logo = "assets/img/logo.png";
$voter_name = "John Doe";
$election_title = "2026 Executive Alumni Elections";
$reference_id = strtoupper(bin2hex(random_bytes(6))); // Generated Receipt ID
$vote_timestamp = date("F j, Y, g:i a");

// Hardcoded selections (what the user supposedly voted for)
$selections = [
    ['position' => 'President', 'candidate' => 'Hon. Sarah Jenkins'],
    ['position' => 'Vice President', 'candidate' => 'Dr. Michael Chen'],
    ['position' => 'Secretary General', 'candidate' => 'Amina Yusuf'],
    ['position' => 'Financial Secretary', 'candidate' => 'Robert Smith']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Confirmed | <?php echo $app_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="icon" href="../<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">

    <style>
        @keyframes confetti {
            0% { transform: translateY(0) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
        .confetti {
            position: absolute; width: 10px; height: 10px; background: #3b82f6;
            animation: confetti 3s ease-out forwards;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans min-h-screen">

      <nav class="bg-blue-900 text-white p-4 shadow-lg sticky top-0 z-50">
        <?php include('nav.php'); ?>
    </nav>

    <main class="container mx-auto px-4 py-12">
        <div class="max-w-2xl mx-auto">
            
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100 relative">
                
                <div class="absolute inset-0 pointer-events-none overflow-hidden" id="confetti-container"></div>

                <div class="bg-green-500 p-8 text-center text-white">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-4 animate-bounce">
                        <i class="fas fa-check text-4xl"></i>
                    </div>
                    <h2 class="text-3xl font-black uppercase">Vote Cast Successfully!</h2>
                    <p class="text-green-100 mt-2">Your voice has been heard and securely encrypted.</p>
                </div>

                <div class="p-8">
                    <div class="flex justify-between items-center mb-8 pb-6 border-b border-dashed border-gray-200">
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-black">Receipt Reference</p>
                            <p class="text-lg font-mono font-bold text-blue-900">#<?php echo $reference_id; ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400 uppercase font-black">Timestamp</p>
                            <p class="text-sm font-bold text-gray-700"><?php echo $vote_timestamp; ?></p>
                        </div>
                    </div>

                    <h3 class="font-bold text-gray-800 mb-4 tracking-tight">Summary of Selections:</h3>
                    
                    <div class="space-y-3 mb-8">
                        <?php foreach($selections as $vote): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <div>
                                <p class="text-[10px] uppercase font-bold text-blue-600"><?php echo $vote['position']; ?></p>
                                <p class="font-bold text-gray-900"><?php echo $vote['candidate']; ?></p>
                            </div>
                            <i class="fas fa-shield-check text-green-500"></i>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    
                    <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100 flex items-start space-x-3">
                        <i class="fas fa-fingerprint text-blue-600 text-xl mt-1"></i>
                        <p class="text-xs text-blue-800 leading-relaxed">
                            <strong>Security Assurance:</strong> Your ballot was processed using 256-bit end-to-end encryption. The link between your identity and these specific choices has been severed to maintain 100% voter anonymity.
                        </p>
                    </div>

                    <div class="mt-10 flex flex-col sm:flex-row gap-4">
                        <button onclick="window.print()" class="flex-1 px-6 py-4 bg-gray-900 text-white rounded-2xl font-bold hover:bg-black transition-colors flex items-center justify-center">
                            <i class="fas fa-print mr-2"></i> Print Receipt
                        </button>
                        <a href="results" class="flex-1 px-6 py-4 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-700 transition-colors flex items-center justify-center">
                            <i class="fas fa-chart-pie mr-2"></i> View Live Stats
                        </a>
                    </div>
                </div>

                <div class="p-6 bg-gray-50 text-center border-t border-gray-100">
                    <p class="text-xs text-gray-500">Logged in as <strong><?php echo $voter_name; ?></strong>. You cannot vote again in this election.</p>
                </div>
            </div>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500 mb-4 uppercase font-bold tracking-widest">I Voted! Spread the word:</p>
                <div class="flex justify-center space-x-4">
                    <button class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition-all"><i class="fab fa-facebook-f"></i></button>
                    <button class="w-12 h-12 rounded-full bg-blue-100 text-blue-400 hover:bg-blue-400 hover:text-white transition-all"><i class="fab fa-twitter"></i></button>
                    <button class="w-12 h-12 rounded-full bg-blue-100 text-green-500 hover:bg-green-500 hover:text-white transition-all"><i class="fab fa-whatsapp"></i></button>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Simple confetti effect
        function createConfetti() {
            const container = document.getElementById('confetti-container');
            for (let i = 0; i < 30; i++) {
                const confetto = document.createElement('div');
                confetto.classList.add('confetti');
                confetto.style.left = Math.random() * 100 + 'p%';
                confetto.style.backgroundColor = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'][Math.floor(Math.random() * 4)];
                confetto.style.animationDelay = Math.random() * 2 + 's';
                container.appendChild(confetto);
            }
        }
        window.onload = createConfetti;
    </script>

</body>
</html>