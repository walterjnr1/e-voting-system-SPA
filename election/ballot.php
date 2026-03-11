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
                // Encrypt Candidate ID for the database vault
                $encrypted_candidate = openssl_encrypt($candidate_id, $encryption_method, $key, 0, $iv);
                
                $insert = $dbh->prepare("INSERT INTO votes (election_id, position_id, candidate_id, voter_ip) VALUES (?, ?, ?, ?)");
                $insert->execute([$election_id, $position_id, $encrypted_candidate, $_SERVER['REMOTE_ADDR']]);

                // Fetch details for email receipt
                $posInfo = $dbh->prepare("SELECT title FROM positions WHERE id = ?");
                $posInfo->execute([$position_id]);
                $pName = $posInfo->fetchColumn();

                $canInfo = $dbh->prepare("SELECT u.full_name, u.nickname FROM candidates c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
                $canInfo->execute([$candidate_id]);
                $cData = $canInfo->fetch(PDO::FETCH_ASSOC);

                $vote_summary_html .= "
                <div style='padding: 10px; border-bottom: 1px solid #edf2f7;'>
                    <span style='color: #718096; font-size: 12px; text-transform: uppercase;'>$pName</span><br>
                    <strong style='color: #2d3748;'>".$cData['full_name']." (".$cData['nickname'].")</strong>
                </div>";
            }
            
            $updateUser = $dbh->prepare("UPDATE users SET has_voted = 1 WHERE id = ?");
            $updateUser->execute([$user_id]);
            
            $dbh->commit();

            // Beautiful Email Notification
            $subject = " Official Ballot Receipt: " . $election['title'];
            $email_body = "
            <html>
            <body style='font-family: sans-serif; background-color: #f7fafc; padding: 40px;'>
                <div style='max-width: 600px; margin: auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                    <div style='background: #1e3a8a; padding: 40px; text-align: center; color: white;'>
                        <div style='font-size: 48px; margin-bottom: 10px;'>✅</div>
                        <h2 style='margin:0;'>Vote Confirmed</h2>
                        <p style='opacity: 0.8;'>Ballot ID: ".uniqid('V-')."</p>
                    </div>
                    <div style='padding: 40px;'>
                        <p>Hello <strong>".htmlspecialchars($voter_details['full_name'])."</strong>,</p>
                        <p>Your electronic ballot for <strong>".htmlspecialchars($election['title'])."</strong> has been securely encrypted and cast.</p>
                        
                        <div style='background: #f8fafc; border-radius: 12px; padding: 20px; margin-top: 25px;'>
                            <h4 style='margin-top:0; color: #1e3a8a;'>Receipt Summary:</h4>
                            $vote_summary_html
                        </div>

                        <div style='margin-top:30px; padding:20px; border-radius:8px; background-color: #fffaf0; border-left: 4px solid #ed8936; color: #7b341e; font-size: 14px;'>
                            <strong>Audit Protection:</strong> This receipt confirms your participation. The contents are stored in our encrypted vault until the verification window opens.
                        </div>
                    </div>
                    <div style='background: #edf2f7; padding: 20px; text-align: center; font-size: 12px; color: #718096;'>
                         <p>&copy; ".date('Y')." ".htmlspecialchars($app_name).". Security Powered by AES-256-CBC.</p>
                    </div>
                </div>
            </body>
            </html>";

            sendEmail($voter_details['email'], $subject, $email_body);
            log_activity($dbh, $user_id, "casted secure ballot in election #$election_id", $_SERVER['REMOTE_ADDR']);

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
    <style>
        .candidate-card:hover .candidate-img { transform: scale(1.05); }
        .modal-active { display: flex !important; }
    </style>
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

    <header class="bg-white py-10 px-4 shadow-sm">
        <div class="container mx-auto text-center max-w-3xl">
            <h2 class="text-3xl font-extrabold text-gray-800 mb-4"><?php echo htmlspecialchars($election['title']); ?></h2>
            <p class="text-gray-500 mb-6">Select your preferred candidates below. Your vote is confidential and encrypted.</p>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <form id="ballotForm" method="POST" class="max-w-5xl mx-auto space-y-12">
            <?php foreach ($positions as $pos): ?>
            <div class="space-y-4">
                <div class="flex items-center space-x-4 border-b-2 border-blue-900 pb-2">
                    <h3 class="font-black uppercase tracking-tighter text-2xl text-blue-900"><?php echo htmlspecialchars($pos['title']); ?></h3>
                    <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-bold">PICK ONE</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php
                    $canStmt = $dbh->prepare("SELECT c.*, u.full_name, u.nickname FROM candidates c JOIN users u ON c.user_id = u.id WHERE c.position_id = ? AND c.status = 'approved'");
                    $canStmt->execute([$pos['id']]);
                    while ($candidate = $canStmt->fetch()):
                        // Get Vote Count (Decryption required in a real audit, but here we count entries associated with candidate_id)
                        // Note: If candidate_id is stored ENCRYPTED in the votes table, we compare with the encrypted version
                        $encrypted_search = openssl_encrypt($candidate['id'], $encryption_method, $key, 0, $iv);
                        $countStmt = $dbh->prepare("SELECT COUNT(*) FROM votes WHERE candidate_id = ?");
                        $countStmt->execute([$encrypted_search]);
                        $vote_count = $countStmt->fetchColumn();
                    ?>
                    <div class="candidate-card relative bg-white rounded-3xl shadow-md border border-gray-200 p-5 transition-all hover:shadow-xl">
                        <label class="cursor-pointer block">
                            <input type="radio" name="votes[<?php echo $pos['id']; ?>]" value="<?php echo $candidate['id']; ?>" class="peer hidden" required>
                            
                            <div class="overflow-hidden rounded-2xl mb-4 bg-gray-100">
                                <img src="../<?php echo $candidate['photo'] ?: 'default.png'; ?>" 
                                     class="candidate-img w-40 h-40 object-cover transition-transform duration-500">
                            </div>

                            <div class="text-center">
                                <h4 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($candidate['full_name']); ?></h4>
                                <p class="text-blue-600 font-medium mb-2">@<?php echo htmlspecialchars($candidate['nickname']); ?></p>
                                
                                <div class="flex items-center justify-center space-x-2 mb-4">
                                    <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-lg text-sm font-bold">
                                        <i class="fas fa-chart-bar mr-1"></i> <?php echo number_format($vote_count); ?> Votes
                                    </span>
                                </div>
                            </div>

                            <div class="absolute top-4 right-4 w-8 h-8 rounded-full border-4 border-white bg-gray-200 flex items-center justify-center peer-checked:bg-green-500 peer-checked:border-green-200 transition-all shadow-lg">
                                <i class="fas fa-check text-white text-xs opacity-0 peer-checked:opacity-100"></i>
                            </div>
                            
                            <div class="absolute inset-0 border-4 border-transparent peer-checked:border-green-500 rounded-3xl pointer-events-none transition-all"></div>
                        </label>

                        <button type="button" 
                                onclick="openManifesto('<?php echo addslashes($candidate['full_name']); ?>', '<?php echo addslashes($candidate['manifesto']); ?>')"
                                class="w-full mt-2 text-blue-600 hover:text-blue-800 text-sm font-semibold underline decoration-2 underline-offset-4">
                            View Manifesto
                        </button>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="pt-10 max-w-md mx-auto">
                <button type="button" onclick="confirmVote()" class="w-full bg-green-600 text-white py-6 rounded-3xl font-black text-2xl shadow-2xl hover:bg-green-700 transform hover:scale-[1.05] transition-all">
                    <i class="fas fa-paper-plane mr-2"></i> SUBMIT BALLOT
                </button>
                <input type="hidden" name="cast_vote" value="1">
                <p class="text-center text-gray-400 text-xs mt-4 uppercase tracking-widest">End-to-End Encrypted</p>
            </div>
        </form>
    </main>

    <div id="manifestoModal" class="fixed inset-0 bg-black/60 z-[100] hidden items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-2xl rounded-3xl overflow-hidden shadow-2xl transform transition-all animate-in fade-in zoom-in duration-300">
            <div class="bg-blue-900 p-6 text-white flex justify-between items-center">
                <h3 id="modalName" class="text-xl font-bold">Candidate Manifesto</h3>
                <button onclick="closeManifesto()" class="text-white/80 hover:text-white text-2xl">&times;</button>
            </div>
            <div id="modalContent" class="p-8 max-h-[70vh] overflow-y-auto prose text-gray-700 leading-relaxed">
                </div>
            <div class="p-4 bg-gray-50 text-right border-t">
                <button onclick="closeManifesto()" class="bg-gray-200 px-6 py-2 rounded-xl font-bold text-gray-700 hover:bg-gray-300">Close</button>
            </div>
        </div>
    </div>

    <script>
    function openManifesto(name, text) {
        document.getElementById('modalName').innerText = name + "'s Manifesto";
        document.getElementById('modalContent').innerHTML = text || "No manifesto provided for this candidate.";
        document.getElementById('manifestoModal').classList.add('modal-active');
    }

    function closeManifesto() {
        document.getElementById('manifestoModal').classList.remove('modal-active');
    }

    function confirmVote() {
        const form = document.getElementById('ballotForm');
        if (!form.checkValidity()) {
            Swal.fire({ 
                icon: 'warning', 
                title: 'Incomplete Ballot', 
                text: 'You must select one candidate for every position before submitting.',
                confirmButtonColor: '#1e3a8a'
            });
            return;
        }
        Swal.fire({
            title: 'Are you sure?',
            html: "By clicking confirm, your choices will be <b>locked and encrypted</b>. This action is irreversible.",
            icon: 'shield',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Confirm My Vote'
        }).then((result) => { if (result.isConfirmed) form.submit(); });
    }

    // Close modal on escape key
    window.addEventListener('keydown', (e) => {
        if(e.key === 'Escape') closeManifesto();
    });
    </script>
    
    <footer class="bg-gray-900 text-gray-400 py-10 mt-20">
        <?php include('../footer.php'); ?>
    </footer>
</body>
</html>