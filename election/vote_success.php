<?php 
include '../database/connection.php';
include('../inc/app_data.php');

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['user_id'])) { header("Location: ../login"); exit; }

$user_id = $_SESSION['user_id'];
// Use the same keys defined in your config
$encryption_method = "AES-256-CBC";
$key = hash('sha256', ENCRYPTION_KEY);
$iv = substr(hash('sha256', ENCRYPTION_IV), 0, 16);

// 1. Fetch Voter Name
$userQuery = $dbh->prepare("SELECT full_name FROM users WHERE id = ?");
$userQuery->execute([$user_id]);
$voter = $userQuery->fetch();

// 2. Fetch Election Info
$elecQuery = $dbh->prepare("SELECT title FROM elections WHERE id = ?");
$elecQuery->execute([$election_id]);
$election = $elecQuery->fetch();

// 3. FIX: Fetch position count separately to avoid SQL syntax error in LIMIT
$countQuery = $dbh->prepare("SELECT COUNT(*) FROM positions WHERE election_id = ?");
$countQuery->execute([$election_id]);
$posCount = (int)$countQuery->fetchColumn();

// 4. Fetch Encrypted Votes using the fetched count
// We use the count to ensure we only show the votes cast in the most recent session
$voteQuery = $dbh->prepare("SELECT position_id, candidate_id FROM votes 
                            WHERE election_id = ? AND voter_ip = ? 
                            ORDER BY id DESC LIMIT $posCount");
$voteQuery->execute([$election_id, $_SERVER['REMOTE_ADDR']]);
$raw_votes = $voteQuery->fetchAll(PDO::FETCH_ASSOC);

$selections = [];
foreach($raw_votes as $v) {
    // Decrypt the candidate ID
    $decrypted_id = openssl_decrypt($v['candidate_id'], $encryption_method, $key, 0, $iv);
    
    // Fetch names for display
    $pQuery = $dbh->prepare("SELECT title FROM positions WHERE id = ?");
    $pQuery->execute([$v['position_id']]);
    $pos_title = $pQuery->fetchColumn();

    $cQuery = $dbh->prepare("SELECT u.full_name FROM candidates c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
    $cQuery->execute([$decrypted_id]);
    $can_name = $cQuery->fetchColumn();

    $selections[] = [
        'position' => $pos_title, 
        'candidate' => $can_name ?: "Unknown Candidate"
    ];
}

// Reverse the array so they appear in the original order (since we used DESC)
$selections = array_reverse($selections);

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
</head>
<body class="bg-gray-50 min-h-screen font-sans">

    <nav class="bg-blue-900 text-white p-4 shadow-lg sticky top-0 z-50">
        <?php include('nav.php'); ?>
    </nav>

    <main class="container mx-auto px-4 py-12">
        <div class="max-w-2xl mx-auto bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100">
            <div class="bg-green-600 p-8 text-center text-white">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-white/20 rounded-full mb-4">
                    <i class="fas fa-check text-3xl"></i>
                </div>
                <h2 class="text-3xl font-black uppercase tracking-tight">Success!</h2>
                <p class="mt-1 text-green-100 opacity-90"><?php echo htmlspecialchars($election['title']); ?></p>
            </div>

            <div class="p-8">
                <div class="flex justify-between items-center mb-8 pb-4 border-b border-dashed border-gray-200">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase font-black">Ref Number</p>
                        <p class="font-mono font-bold text-gray-700">#<?php echo $reference_id; ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400 uppercase font-black">Date & Time</p>
                        <p class="text-sm font-bold text-gray-700"><?php echo $vote_timestamp; ?></p>
                    </div>
                </div>

                <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-file-invoice me-2 text-blue-600"></i> Your Ballot Receipt:
                </h3>

                <div class="space-y-3 mb-8">
                    <?php if(!empty($selections)): ?>
                        <?php foreach($selections as $vote): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <div>
                                <p class="text-[10px] uppercase font-bold text-blue-600 tracking-wider"><?php echo htmlspecialchars($vote['position']); ?></p>
                                <p class="font-bold text-gray-900"><?php echo htmlspecialchars($vote['candidate']); ?></p>
                            </div>
                            <div class="text-green-500">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-gray-500 py-4">No recent votes found.</p>
                    <?php endif; ?>
                </div>

                <div class="bg-blue-50 p-5 rounded-2xl border border-blue-100 flex items-start space-x-3 mb-8">
                    <i class="fas fa-user-shield text-blue-600 text-lg mt-1"></i>
                    <div class="text-xs text-blue-800 leading-relaxed">
                        <p class="font-bold mb-1">Official Verification Notice:</p>
                        Please await the official announcement of results after the ELECO committee finishes vote verification and audit. You can follow the live data count using the button below.
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    <button onclick="window.print()" class="flex-1 px-6 py-4 bg-gray-800 text-white rounded-2xl font-bold hover:bg-black transition-all flex items-center justify-center">
                        <i class="fas fa-print mr-2"></i> Print Receipt
                    </button>
                    <a href="result_page" class="flex-1 px-6 py-4 bg-blue-600 text-white rounded-2xl font-bold text-center hover:bg-blue-700 transition-all flex items-center justify-center">
                        <i class="fas fa-chart-line mr-2"></i> Live Results
                    </a>
                </div>
            </div>

            <div class="bg-gray-50 p-4 text-center border-t border-gray-100">
                <p class="text-[10px] text-gray-400 uppercase">Securely authenticated for: <strong><?php echo htmlspecialchars($voter['full_name']); ?></strong></p>
            </div>
        </div>
    </main>

   
    <footer class="bg-gray-900 text-gray-400 py-10">
        <?php include('../footer.php'); ?>
    </footer>

</body>
</html>