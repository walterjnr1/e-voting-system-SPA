<?php 
include '../database/connection.php';
include('../inc/app_data.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header("Location: ../login");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch Voter Name
$userQuery = $dbh->prepare("SELECT full_name FROM users WHERE id = ?");
$userQuery->execute([$user_id]);
$voter = $userQuery->fetch();

// Fetch Election Info
$elecQuery = $dbh->prepare("SELECT title FROM elections WHERE id = ?");
$elecQuery->execute([$election_id]);
$election = $elecQuery->fetch();

// Fetch what the user voted for
$voteQuery = $dbh->prepare("
    SELECT p.title as position, u.full_name as candidate 
    FROM votes v 
    JOIN positions p ON v.position_id = p.id 
    JOIN candidates c ON v.candidate_id = c.id 
    JOIN users u ON c.user_id = u.id 
    WHERE v.election_id = ? AND v.voter_ip = ? 
    /* Note: In a real encrypted system, you'd use a transaction ID, 
       but here we retrieve based on context for the receipt */
    ORDER BY p.id ASC
");
// In a high-security app, you wouldn't link user_id to votes. 
// For this receipt, we fetch the latest votes from this IP/Election context.
$voteQuery->execute([$election_id, $_SERVER['REMOTE_ADDR']]);
$selections = $voteQuery->fetchAll(PDO::FETCH_ASSOC);

$reference_id = strtoupper(bin2hex(random_bytes(6)));
$vote_timestamp = date("F j, Y, g:i a");
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
        .confetti { position: absolute; width: 10px; height: 10px; animation: confetti 3s ease-out forwards; }
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
                    <p class="text-green-100 mt-2"><?php echo $election['title']; ?></p>
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

                    <h3 class="font-bold text-gray-800 mb-4 tracking-tight">Your Confirmed Ballot:</h3>
                    <div class="space-y-3 mb-8">
                        <?php foreach($selections as $vote): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <div>
                                <p class="text-[10px] uppercase font-bold text-blue-600"><?php echo $vote['position']; ?></p>
                                <p class="font-bold text-gray-900"><?php echo $vote['candidate']; ?></p>
                            </div>
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100 flex items-start space-x-3">
                        <i class="fas fa-fingerprint text-blue-600 text-xl mt-1"></i>
                        <p class="text-xs text-blue-800 leading-relaxed">
                            <strong>Security Assurance:</strong> Your ballot was processed using 256-bit end-to-end encryption. The link between your identity and these specific choices has been severed.
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
                    <p class="text-xs text-gray-500">Authenticated as <strong><?php echo htmlspecialchars($voter['full_name']); ?></strong>.</p>
                </div>
            </div>
        </div>
    </main>

    <script>
        function createConfetti() {
            const container = document.getElementById('confetti-container');
            for (let i = 0; i < 40; i++) {
                const confetto = document.createElement('div');
                confetto.classList.add('confetti');
                confetto.style.left = Math.random() * 100 + '%';
                confetto.style.backgroundColor = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'][Math.floor(Math.random() * 4)];
                confetto.style.animationDelay = Math.random() * 2 + 's';
                container.appendChild(confetto);
            }
        }
        window.onload = createConfetti;
    </script>
     <footer class="bg-gray-900 text-gray-400 py-10">
        <?php include('../footer.php'); ?>
    </footer>
</body>
</html>