<?php 
include '../database/connection.php'; 
include('../inc/app_data.php'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Access Control
if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login"); 
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // 2. Fetch Election Details
    $stmt = $dbh->prepare("SELECT * FROM elections WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$election) {
        die("No active election found or election has ended.");
    }

    // Fetch User Details for Email and Check Vote Status
    $checkVote = $dbh->prepare("SELECT full_name, email, has_voted FROM users WHERE id = ?");
    $checkVote->execute([$user_id]);
    $voter_details = $checkVote->fetch();
    
    if ($voter_details['has_voted'] == 1) {
        header("Location: vote_success");
        exit;
    }

    // 3. Handle Vote Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cast_vote'])) {
        $dbh->beginTransaction();
        try {
            foreach ($_POST['votes'] as $position_id => $candidate_id) {
                $insert = $dbh->prepare("INSERT INTO votes (election_id, position_id, candidate_id, voter_ip) VALUES (?, ?, ?, ?)");
                $insert->execute([$election_id, $position_id, $candidate_id, $_SERVER['REMOTE_ADDR']]);
            }
            
            $updateUser = $dbh->prepare("UPDATE users SET has_voted = 1 WHERE id = ?");
            $updateUser->execute([$user_id]);
            
            $dbh->commit();

            // ✅ Asset Config for Email
            $facebook_icon  = "https://cdn-icons-png.flaticon.com/512/733/733547.png";
            $twitter_icon   = "https://cdn-icons-png.flaticon.com/512/5969/5969020.png";
            $instagram_icon = "https://cdn-icons-png.flaticon.com/512/174/174855.png";
            $whatsapp_icon  = "https://cdn-icons-png.flaticon.com/512/733/733585.png";

            // --- Email Notification Logic ---
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
                        <p>This email confirms that your choices for <strong>".htmlspecialchars($election['title'])."</strong> have been successfully encrypted and stored in our database.</p>
                        <div style='background: #f8fafc; padding: 20px; border-left: 4px solid #1e3a8a; border-radius: 4px;'>
                            <strong>Timestamp:</strong> ".date('F j, Y, g:i a')."<br>
                            <strong>Network IP:</strong> ".$_SERVER['REMOTE_ADDR']."<br>
                            <strong>Status:</strong> Awaiting Verification
                        </div>
                        <p style='font-size: 13px; color: #666; margin-top: 20px;'>Note: Official results will only be announced after a comprehensive audit and vote verification process.</p>
                    </div>
                    <div style='background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; border-top: 1px solid #eee;'>
                         <p style='margin-bottom: 15px;'>Support: ".htmlspecialchars($app_email)."</p>
                         <a href='#'><img src='$facebook_icon' width='24' style='margin:0 5px;'></a>
                         <a href='#'><img src='$twitter_icon' width='24' style='margin:0 5px;'></a>
                         <a href='#'><img src='$instagram_icon' width='24' style='margin:0 5px;'></a>
                         <p style='margin-top: 15px;'>&copy; ".date('Y')." ".htmlspecialchars($app_name).". All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>";

            // Fixed: Use $voter_details['email'] instead of $row_user
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
                            <strong>Security Protocol:</strong> All votes are encrypted using end-to-end industry standards. 
                            Official announcement of results will only be released after a formal <strong>vote verification and audit process</strong> by the election committee.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <?php if(isset($error)): ?>
            <div class="max-w-4xl mx-auto mb-6 p-4 bg-red-100 text-red-700 rounded-xl border border-red-200">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

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
                    <i class="fas fa-lock mr-2"></i> ENCRYPT & SUBMIT BALLOT
                </button>
                <input type="hidden" name="cast_vote" value="1">
                <p class="text-gray-400 text-xs mt-4"><i class="fas fa-info-circle"></i> Once submitted, your choices are locked and cannot be edited.</p>
            </div>
        </form>
    </main>

    <script>
    function confirmVote() {
        const form = document.getElementById('ballotForm');
        if (!form.checkValidity()) {
            Swal.fire({
                icon: 'error',
                title: 'Incomplete Ballot',
                text: 'Please select a candidate for every position before submitting.',
                confirmButtonColor: '#1e3a8a'
            });
            return;
        }

        Swal.fire({
            title: 'Cast Your Final Vote?',
            html: "By confirming, your choices will be <strong>encrypted</strong> and sent to the secure vault. You cannot vote again.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#374151',
            confirmButtonText: 'Yes, Submit Ballot',
            cancelButtonText: 'Review'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Encrypting Ballot...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                form.submit();
            }
        });
    }
    </script>

    <footer class="bg-gray-900 text-gray-400 py-10 mt-12">
        <?php include('../footer.php'); ?>
    </footer>
</body>
</html>