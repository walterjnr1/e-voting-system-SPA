<?php
include '../database/connection.php'; 
include('../inc/app_data.php'); 

/**
 * PRODUCTION LOGIC:
 * Fetch the active election and its end date.
 */
// Mocking the end date for 2 days from now. 
// In production, fetch this: $election['end_date'] = '2026-03-05 12:00:00';
$election_end_time = date('Y-m-d H:i:s', strtotime('+2 days + 14 hours'));
$election_title = "2026 Executive Council Elections";

// Mock Data for Progress Bars and Candidates
$total_voters = 1200;
$votes_cast = 845;
$participation_rate = round(($votes_cast / $total_voters) * 100);

$election_data = [
    ['id' => 1, 'title' => 'President', 'candidates' => [
        ['id' => 101, 'name' => 'John Doe', 'photo' => 'https://via.placeholder.com/150', 'bio' => 'Visionary leader with 10 years experience.', 'current_votes' => 450],
        ['id' => 102, 'name' => 'Jane Smith', 'photo' => 'https://via.placeholder.com/150', 'bio' => 'Dedicated to transparency and growth.', 'current_votes' => 395]
    ]],
    ['id' => 2, 'title' => 'Secretary General', 'candidates' => [
        ['id' => 201, 'name' => 'Alice Wong', 'photo' => 'https://via.placeholder.com/150', 'bio' => 'Expert in digital record keeping.', 'current_votes' => 500],
        ['id' => 202, 'name' => 'Bob Brown', 'photo' => 'https://via.placeholder.com/150', 'bio' => 'Committed to better communication.', 'current_votes' => 345]
    ]]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Ballot | <?php echo htmlspecialchars($app_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="../<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">
    <style>
        .radio-card:checked + .card-content { border-color: #2563eb; background-color: #eff6ff; }
        @keyframes pulse-soft {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .animate-pulse-soft { animation: pulse-soft 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
    </style>
</head>
<body class="bg-gray-50 font-sans min-h-screen">

    <nav class="bg-blue-900 text-white p-4 shadow-lg sticky top-0 z-50">
        <?php include('nav.php'); ?>
    </nav>

    <div class="bg-white border-b border-gray-200">
        <div class="container mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-2">
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                <span class="text-xs font-black text-green-600 uppercase tracking-widest">Election is Live</span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-500 font-medium">Polls close in:</span>
                <div id="countdown" class="flex space-x-2 text-blue-900 font-mono font-bold text-lg">
                    00d 00h 00m 00s
                </div>
            </div>
        </div>
    </div>

    <main class="container mx-auto px-4 py-10">
        <div class="flex flex-col lg:flex-row gap-8">
            
            <div class="lg:w-2/3">
                <header class="mb-8">
                    <h2 class="text-3xl font-black text-gray-900 uppercase"><?php echo $election_title; ?></h2>
                    <div class="h-1 w-20 bg-blue-600 mt-2 mb-4"></div>
                    <p class="text-gray-600 leading-relaxed">
                        This platform ensures that your vote is <strong>100% anonymous and encrypted</strong>. 
                        Please review the candidates' manifestos carefully. You are required to select <strong>one candidate per position</strong>. 
                    </p>
                </header>

                <form action="handlers/vote_handler.php" method="POST" id="ballotForm">
                    <?php foreach($election_data as $pos): ?>
                    <div class="mb-12">
                        <div class="flex items-center space-x-3 mb-6">
                            <span class="flex items-center justify-center bg-blue-900 text-white w-8 h-8 rounded-full font-bold text-sm">
                                <?php echo $pos['id']; ?>
                            </span>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo $pos['title']; ?></h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach($pos['candidates'] as $candidate): ?>
                            <div class="relative">
                                <input type="radio" name="pos_<?php echo $pos['id']; ?>" value="<?php echo $candidate['id']; ?>" id="cand_<?php echo $candidate['id']; ?>" class="peer hidden radio-card" required>
                                <label for="cand_<?php echo $candidate['id']; ?>" class="card-content block p-5 border-2 border-gray-200 rounded-2xl cursor-pointer hover:border-blue-300 transition-all bg-white shadow-sm peer-checked:border-blue-600 peer-checked:bg-blue-50 group">
                                    <div class="flex items-center space-x-4">
                                        <img src="<?php echo $candidate['photo']; ?>" class="h-16 w-16 rounded-full border shadow-sm object-cover group-hover:scale-105 transition-transform">
                                        <div class="flex-grow">
                                            <p class="font-bold text-lg text-gray-900"><?php echo $candidate['name']; ?></p>
                                            <button type="button" onclick="openManifesto('<?php echo addslashes($candidate['name']); ?>', '<?php echo addslashes($candidate['bio']); ?>')" class="text-blue-600 text-sm font-semibold hover:text-blue-800">
                                                Read Manifesto
                                            </button>
                                        </div>
                                        <div class="hidden peer-checked:block text-blue-600">
                                            <i class="fas fa-check-circle text-2xl"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <div class="flex justify-between text-[10px] uppercase font-bold text-gray-400 mb-1">
                                            <span>Current Tally</span>
                                            <span><?php echo $candidate['current_votes']; ?> votes</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-1.5">
                                            <div class="bg-blue-400 h-1.5 rounded-full" style="width: <?php echo ($candidate['current_votes'] / $votes_cast) * 100; ?>%"></div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="bg-blue-900 p-8 rounded-3xl text-center shadow-2xl">
                        <i class="fas fa-shield-alt text-blue-400 text-4xl mb-4"></i>
                        <h3 class="text-white text-xl font-bold mb-2">Ready to Cast Your Ballot?</h3>
                        <p class="text-blue-200 text-sm mb-6">Your session is secured. Once submitted, your vote is permanent.</p>
                        <button type="submit" class="w-full md:w-auto px-12 py-4 bg-green-500 hover:bg-green-600 text-white font-black rounded-full text-lg shadow-lg transition-transform hover:scale-105">
                            CONFIRM & SUBMIT VOTE
                        </button>
                    </div>
                </form>
            </div>

            <div class="lg:w-1/3 space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h4 class="font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-chart-line mr-2 text-blue-600"></i> Election Live Stats
                    </h4>
                    
                    <div class="space-y-6">
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-sm font-medium text-gray-600">Voter Turnout</span>
                                <span class="text-sm font-bold text-blue-700"><?php echo $participation_rate; ?>%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-3">
                                <div class="bg-blue-600 h-3 rounded-full animate-pulse-soft" style="width: <?php echo $participation_rate; ?>%"></div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-2 uppercase tracking-tighter">
                                <?php echo number_format($votes_cast); ?> out of <?php echo number_format($total_voters); ?> alumni have voted.
                            </p>
                        </div>

                        <hr class="border-gray-100">

                        <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                            <h5 class="text-xs font-bold text-blue-800 uppercase mb-2">Security Notice</h5>
                            <p class="text-[11px] text-blue-700 leading-relaxed">
                                Your IP (<?php echo $_SERVER['REMOTE_ADDR']; ?>) is monitored to prevent multi-session fraud. Your ballot selection remains private.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-900 p-6 rounded-2xl text-white">
                    <h4 class="font-bold mb-4">Need Help?</h4>
                    <ul class="text-sm space-y-3 text-gray-400">
                        <li><i class="fas fa-info-circle mr-2 text-blue-400"></i> Select one bubble per position.</li>
                        <li><i class="fas fa-phone mr-2 text-blue-400"></i> Tech Support: 08067361023</li>
                    </ul>
                </div>
            </div>

        </div>
    </main>

    <div id="manifestoModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-[100] flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl">
            <div class="p-6 border-b flex justify-between items-center bg-gray-50">
                <h3 id="modalName" class="text-xl font-bold text-gray-900"></h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-red-500 text-3xl">&times;</button>
            </div>
            <div class="p-8">
                <p id="modalBio" class="text-gray-700 leading-relaxed text-lg italic"></p>
            </div>
            <div class="p-6 bg-gray-50 flex justify-end">
                <button onclick="closeModal()" class="bg-blue-900 text-white px-8 py-3 rounded-xl font-bold shadow-lg">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Countdown Timer Logic
        const electionEndTime = new Date("<?php echo $election_end_time; ?>").getTime();

        const timer = setInterval(function() {
            const now = new Date().getTime();
            const distance = electionEndTime - now;

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById("countdown").innerHTML = 
                days + "d " + hours + "h " + minutes + "m " + seconds + "s ";

            if (distance < 0) {
                clearInterval(timer);
                document.getElementById("countdown").innerHTML = "ELECTION CLOSED";
                document.getElementById("ballotForm").innerHTML = "<div class='text-center p-20 bg-red-50 text-red-600 font-bold rounded-3xl'>The election has ended. No more votes can be cast.</div>";
            }
        }, 1000);

        function openManifesto(name, bio) {
            document.getElementById('modalName').innerText = name;
            document.getElementById('modalBio').innerText = bio;
            document.getElementById('manifestoModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('manifestoModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    </script>

    <footer class="bg-gray-900 text-gray-400 py-10">
        <?php include('../footer.php'); ?>
    </footer>

</body>
</html>