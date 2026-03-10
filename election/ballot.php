<?php 
include '../database/connection.php'; 
include('../inc/app_data.php'); 

// Access Control
if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login"); 
    exit;
}

$user_id = $_SESSION['user_id'];
$encryption_method = "AES-256-CBC";
$key = hash('sha256', ENCRYPTION_KEY);
$iv = substr(hash('sha256', ENCRYPTION_IV), 0, 16);

try {
    $stmt = $dbh->prepare("SELECT * FROM elections WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$election) {
        die("No active election found or election has ended.");
    }

    $checkVote = $dbh->prepare("SELECT full_name, email, has_voted FROM users WHERE id = ?");
    $checkVote->execute([$user_id]);
    $voter_details = $checkVote->fetch();
    
    if ($voter_details['has_voted'] == 1) {
        header("Location: vote_success");
        exit;
    }

    // Handle Vote Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cast_vote'])) {
        $dbh->beginTransaction();
        $vote_summary_html = "";

        try {
            foreach ($_POST['votes'] as $position_id => $candidate_id) {
                // Encrypt Candidate ID
                $encrypted_candidate = openssl_encrypt($candidate_id, $encryption_method, $key, 0, $iv);
                
                $insert = $dbh->prepare("INSERT INTO votes (election_id, position_id, candidate_id, voter_ip) VALUES (?, ?, ?, ?)");
                $insert->execute([$election_id, $position_id, $encrypted_candidate, $_SERVER['REMOTE_ADDR']]);

                // Prepare Summary for Email
                $posInfo = $dbh->prepare("SELECT title FROM positions WHERE id = ?");
                $posInfo->execute([$position_id]);
                $pName = $posInfo->fetchColumn();

                $canInfo = $dbh->prepare("SELECT u.full_name FROM candidates c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
                $canInfo->execute([$candidate_id]);
                $cName = $canInfo->fetchColumn();

                $vote_summary_html .= "<li><strong>$pName:</strong> $cName</li>";
            }
            
            $updateUser = $dbh->prepare("UPDATE users SET has_voted = 1 WHERE id = ?");
            $updateUser->execute([$user_id]);
            
            $dbh->commit();

            // Email Notification Logic
            $subject = "Official Ballot Receipt: " . $election['title'];
            $email_body = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333; background-color: #f4f7f6; padding: 20px;'>
                <div style='max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 12px; overflow: hidden; background: white;'>
                    <div style='background: #1e3a8a; color: white; padding: 30px; text-align: center;'>
                        <h2 style='margin:0;'>Ballot Confirmed</h2>
                        <p style='font-size: 14px; opacity: 0.8;'>Election ID: #".$election_id."</p>
                    </div>
                    <div style='padding: 30px;'>
                        <p>Hello <strong>".htmlspecialchars($voter_details['full_name'])."</strong>,</p>
                        <p>Your choices for <strong>".htmlspecialchars($election['title'])."</strong> have been successfully encrypted and stored.</p>
                        
                        <h3 style='color:#1e3a8a;'>Your Vote Summary:</h3>
                        <ul style='background: #f8fafc; padding: 20px; border-radius: 8px; list-style: none;'>
                            $vote_summary_html
                        </ul>

                        <div style='margin-top:20px; padding:15px; background:#fff3cd; border-radius:5px; color:#856404;'>
                            <strong>Notice:</strong> Please await the official announcement of results after the ELECO committee completes the manual vote verification and audit process.
                        </div>

                       
                    </div>
                    <div style='background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; border-top: 1px solid #eee;'>
                         <p>&copy; ".date('Y')." ".htmlspecialchars($app_name).". All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>";

            sendEmail($voter_details['email'], $subject, $email_body);
            log_activity($dbh, $user_id, "voted successfully in election #$election_id", $_SERVER['REMOTE_ADDR']);

            header("Location: vote_success");
            exit;

        } catch (Exception $e) {
            $dbh->rollBack();
            $error = "Voting failed: " . $e->getMessage();
        }
    }

    $posStmt = $dbh->prepare("SELECT * FROM positions WHERE election_id = ? ORDER BY id ASC");
    $posStmt->execute([$election_id]);
    $positions = $posStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cast Vote | <?php echo htmlspecialchars($app_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="../<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">
</head>
<body class="bg-gray-50 font-sans">
    <nav class="bg-blue-900 text-white p-4 shadow-lg sticky top-0 z-50">
       <?php include('nav.php'); ?>
    </nav>

    <div class="bg-blue-50 border-b border-blue-100 py-3">
        <div class="container mx-auto px-4 flex flex-wrap justify-between items-center text-sm font-medium text-blue-800">
            <div><i class="fas fa-fingerprint mr-1"></i> ELECTION ID: <span class="font-bold">#<?php echo $election_id; ?></span></div>
            <div><i class="fas fa-clock mr-1"></i> EXPIRES: <span class="font-bold"><?php echo date("M j, Y - g:i a", strtotime($election['end_datetime'])); ?></span></div>
        </div>
    </div>

    <header class="bg-white py-10 px-4">
        <div class="container mx-auto text-center max-w-3xl">
            <h2 class="text-3xl font-extrabold text-gray-800 mb-4"><?php echo htmlspecialchars($election['title']); ?></h2>
            <div class="bg-amber-50 border-l-4 border-amber-400 p-4 text-left inline-block w-full">
                <div class="flex">
                    <div class="flex-shrink-0"><i class="fas fa-shield-alt text-amber-500"></i></div>
                    <div class="ml-3">
                        <p class="text-sm text-amber-800">
                            <strong>Security Protocol:</strong> All votes are encrypted. Results are released only after formal <strong>verification and audit</strong>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <form id="ballotForm" method="POST" class="max-w-4xl mx-auto space-y-8">
            <?php foreach ($positions as $pos): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-800 p-4 text-white flex justify-between items-center">
                    <h3 class="font-bold uppercase tracking-widest text-sm"><?php echo htmlspecialchars($pos['title']); ?></h3>
                    <span class="text-xs bg-blue-600 px-2 py-1 rounded">Select 1</span>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php
                    $canStmt = $dbh->prepare("SELECT c.*, u.full_name, u.nickname FROM candidates c JOIN users u ON c.user_id = u.id WHERE c.position_id = ? AND c.status = 'approved'");
                    $canStmt->execute([$pos['id']]);
                    while ($candidate = $canStmt->fetch()):
                    ?>
                    <label class="relative flex items-center p-4 border-2 border-gray-100 rounded-xl cursor-pointer hover:bg-blue-50 transition-all group has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                        <input type="radio" name="votes[<?php echo $pos['id']; ?>]" value="<?php echo $candidate['id']; ?>" class="hidden" required>
                        <img src="../<?php echo $candidate['photo'] ?: 'default.png'; ?>" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-sm mr-4">
                        <div class="flex-1">
                            <p class="font-bold text-gray-900"><?php echo htmlspecialchars($candidate['full_name']); ?></p>
                            <p class="text-xs text-gray-500 mb-1 italic">"<?php echo htmlspecialchars($candidate['nickname']); ?>"</p>
                        </div>
                        <div class="w-6 h-6 rounded-full border-2 border-gray-300 flex items-center justify-center group-has-[:checked]:border-blue-600 group-has-[:checked]:bg-blue-600">
                            <i class="fas fa-check text-white text-xs opacity-0 group-has-[:checked]:opacity-100"></i>
                        </div>
                    </label>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="pt-6 text-center">
                <button type="button" onclick="confirmVote()" class="w-full bg-green-600 text-white py-5 rounded-2xl font-black text-xl shadow-xl hover:bg-green-700 transform hover:scale-[1.02] transition-all">
                    <i class="fas fa-lock mr-2"></i> SUBMIT VOTE
                </button>
                <input type="hidden" name="cast_vote" value="1">
            </div>
        </form>
    </main>

    <script>
    function confirmVote() {
        const form = document.getElementById('ballotForm');
        if (!form.checkValidity()) {
            Swal.fire({ icon: 'error', title: 'Incomplete Ballot', text: 'Please select a candidate for every position.' });
            return;
        }
        Swal.fire({
            title: 'Cast Your Final Vote?',
            html: "Your choices will be <strong>encrypted</strong>. You cannot vote again.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Submit Ballot'
        }).then((result) => { if (result.isConfirmed) form.submit(); });
    }
    </script>
    
    <footer class="bg-gray-900 text-gray-400 py-10">
        <?php include('../footer.php'); ?>
    </footer>
</body>
</html>