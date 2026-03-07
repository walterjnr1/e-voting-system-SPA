<?php
include 'database/connection.php'; 
include('inc/app_data.php'); 

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login"); 
    exit;
}

// --- 1. FETCH THE CURRENTLY ACTIVE OR SCHEDULED ELECTION ---
try {
    $stmt = $dbh->prepare("SELECT title, end_datetime, start_datetime, allow_result_view, status 
                           FROM elections 
                           WHERE status IN ('active', 'scheduled') 
                           ORDER BY status ASC, start_datetime ASC LIMIT 1");
    $stmt->execute();
    $election = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Helper variable for states
    $is_live = ($election && $election['status'] === 'active');
    $title = $election ? htmlspecialchars($election['title']) : "Election";
} catch (PDOException $e) {
    $election = null;
    $is_live = false;
    $title = "Election";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_name); ?> | Secure E-Vote</title>
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
                Securely cast your vote for the <strong><?php echo $title; ?></strong>. Verified, encrypted, and transparent.
            </p>
            
            <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                <?php if ($is_live): ?>
                    <a href="election/ballot" class="w-full sm:w-auto bg-green-600 text-white px-8 py-4 rounded-full text-lg font-bold hover:bg-green-700 shadow-xl transform hover:scale-105 transition flex items-center justify-center">
                        <i class="fas fa-check-to-slot mr-2"></i> Cast Your Vote
                    </a>
                <?php else: ?>
                    <button disabled class="w-full sm:w-auto bg-gray-300 text-gray-500 cursor-not-allowed px-8 py-4 rounded-full text-lg font-bold shadow-none flex items-center justify-center">
                        <i class="fas fa-lock mr-2"></i> Voting Unavailable
                    </button>
                <?php endif; ?>

                <?php if ($is_live && $election['allow_result_view'] == 1): ?>
                    <a href="election/result_page" class="w-full sm:w-auto bg-blue-600 text-white px-8 py-4 rounded-full text-lg font-bold hover:bg-blue-700 shadow-xl transform hover:scale-105 transition flex items-center justify-center">
                        <i class="fas fa-chart-line mr-2"></i> View Live Results
                    </a>
                <?php else: ?>
                    <button disabled class="w-full sm:w-auto bg-gray-200 text-gray-400 cursor-not-allowed px-8 py-4 rounded-full text-lg font-bold flex items-center justify-center">
                        <i class="fas fa-eye-slash mr-2"></i> Results Locked
                    </button>
                <?php endif; ?>

                <a href="#how-it-works" class="w-full sm:w-auto bg-gray-100 text-gray-700 px-8 py-4 rounded-full text-lg font-semibold hover:bg-gray-200 transition">
                    Learn More
                </a>
            </div>
        </div>
    </header>

    <?php if ($election): ?>
        <section class="container mx-auto -mt-8 px-4">
            <?php if ($election['status'] === 'active'): ?>
                <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-r-lg shadow-md flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center">
                        <div class="mr-4 hidden md:block">
                            <i class="fas fa-clock text-blue-500 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-blue-900 uppercase tracking-wider text-sm md:text-base">
                                Ongoing: <?php echo htmlspecialchars($election['title']); ?>
                            </h3>
                            <p class="text-blue-700 font-medium">
                                Ends in: <span id="electionTimer" class="font-mono font-bold text-blue-900" 
                                               data-time="<?php echo $election['end_datetime']; ?>">
                                               Initializing...
                                         </span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="bg-green-200 text-green-800 px-4 py-1 rounded-full text-xs font-black animate-pulse border border-green-300">
                            ● LIVE
                        </span>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-amber-50 border-l-4 border-amber-500 p-6 rounded-r-lg shadow-md flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center">
                        <div class="mr-4 hidden md:block">
                            <i class="fas fa-calendar-alt text-amber-500 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-amber-900 uppercase tracking-wider text-sm md:text-base">
                                Next Election: <?php echo htmlspecialchars($election['title']); ?>
                            </h3>
                            <p class="text-amber-700 font-medium">
                                Starts in: <span id="electionTimer" class="font-mono font-bold text-amber-900" 
                                                 data-time="<?php echo $election['start_datetime']; ?>">
                                                 Initializing...
                                           </span>
                            </p>
                        </div>
                    </div>
                    <span class="bg-amber-200 text-amber-800 px-4 py-1 rounded-full text-xs font-black border border-amber-300">
                        COMING SOON
                    </span>
                </div>
            <?php endif; ?>
        </section>

        <script>
            function initElectionTimer() {
                const display = document.getElementById('electionTimer');
                if (!display) return;

                const targetTime = new Date(display.getAttribute('data-time')).getTime();

                const interval = setInterval(function() {
                    const now = new Date().getTime();
                    const diff = targetTime - now;

                    // If time is up, stop interval and reload ONCE
                    if (diff <= 0) {
                        clearInterval(interval);
                        display.innerHTML = "ELECTION UPDATING...";
                        // Delay reload by 2 seconds to prevent aggressive looping
                        setTimeout(() => { location.reload(); }, 2000);
                        return;
                    }

                    const d = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const s = Math.floor((diff % (1000 * 60)) / 1000);

                    display.innerHTML = `${d}d ${h}h ${m}m ${s}s`;
                }, 1000);
            }
            document.addEventListener('DOMContentLoaded', initElectionTimer);
        </script>

    <?php else: ?>
        <section class="container mx-auto -mt-8 px-4">
            <div class="bg-gray-50 border-l-4 border-gray-400 p-6 rounded-r-lg shadow-sm">
                <h3 class="font-bold text-gray-500 uppercase tracking-widest text-sm">No active elections at the moment.</h3>
            </div>
        </section>
    <?php endif; ?>

    <section id="how-it-works" class="py-20 container mx-auto px-4">
        <h3 class="text-3xl font-black text-center text-gray-900 mb-12 uppercase tracking-tighter">Why Vote With Us?</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 text-center">
                <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600 text-2xl">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h4 class="font-bold text-gray-800 mb-2">Verified Identity</h4>
                <p class="text-gray-500 text-sm">Only verified alumni from the official database can participate.</p>
            </div>
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 text-center">
                <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-green-600 text-2xl">
                    <i class="fas fa-fingerprint"></i>
                </div>
                <h4 class="font-bold text-gray-800 mb-2">Anonymous Ballot</h4>
                <p class="text-gray-500 text-sm">Your identity is decoupled from your vote for 100% privacy.</p>
            </div>
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 text-center">
                <div class="bg-yellow-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-yellow-600 text-2xl">
                    <i class="fas fa-bolt"></i>
                </div>
                <h4 class="font-bold text-gray-800 mb-2">Instant Results</h4>
                <p class="text-gray-500 text-sm">View real-time tallying as soon as votes are cast.</p>
            </div>
        </div>
    </section>

    <footer class="bg-gray-900 text-gray-400 py-10">
        <?php include('footer.php'); ?>
    </footer>

</body>
</html>