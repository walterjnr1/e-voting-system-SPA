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

// 2. Fetch Election Details (Using $election_id from app_data)
try {
    $stmt = $dbh->prepare("SELECT * FROM elections WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$election) {
        die("No active election found or election has ended.");
    }

    // Check if user already voted
    $checkVote = $dbh->prepare("SELECT has_voted FROM users WHERE id = ?");
    $checkVote->execute([$user_id]);
    $voter = $checkVote->fetch();
    if ($voter['has_voted'] == 1) {
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
            
            // Mark user as voted
            $updateUser = $dbh->prepare("UPDATE users SET has_voted = 1 WHERE id = ?");
            $updateUser->execute([$user_id]);
            
            $dbh->commit();
            header("Location: vote_success");
            exit;
        } catch (Exception $e) {
            $dbh->rollBack();
            $error = "Voting failed. Please try again.";
        }
    }

    // 4. Fetch Positions and Candidates
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
        <link rel="icon" href="../<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">

</head>
<body class="bg-gray-50 font-sans">

    <nav class="bg-blue-900 text-white p-4 shadow-lg sticky top-0 z-50">
       <?php include('nav.php'); ?>
    </nav>

    <header class="bg-white py-10 px-4 border-b">
        <div class="container mx-auto text-center">
            <h2 class="text-3xl font-extrabold text-gray-800 mb-2"><?php echo htmlspecialchars($election['title']); ?></h2>
            <p class="text-gray-600">Please select one candidate for each position below.</p>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <form method="POST" class="max-w-4xl mx-auto space-y-8">
            <?php foreach ($positions as $pos): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gray-800 p-4 text-white flex justify-between items-center">
                        <h3 class="font-bold uppercase tracking-widest text-sm"><?php echo $pos['title']; ?></h3>
                        <span class="text-xs bg-blue-600 px-2 py-1 rounded">Select 1</span>
                    </div>
                    
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php
                        $canStmt = $dbh->prepare("SELECT c.*, u.full_name, u.nickname FROM candidates c JOIN users u ON c.user_id = u.id WHERE c.position_id = ? AND c.status = 'approved'");
                        $canStmt->execute([$pos['id']]);
                        while ($candidate = $canStmt->fetch()):
                            
                            // logic for displaying total votes if allowed
                            $vote_count_html = "";
                            if ($election['allow_result_view'] == 1) {
                                $countStmt = $dbh->prepare("SELECT COUNT(*) FROM votes WHERE candidate_id = ?");
                                $countStmt->execute([$candidate['id']]);
                                $count = $countStmt->fetchColumn();
                                $vote_count_html = "<span class='text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded-full'>$count Votes So Far</span>";
                            }
                        ?>
                        <label class="relative flex items-center p-4 border-2 border-gray-100 rounded-xl cursor-pointer hover:bg-blue-50 transition-all group has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                            <input type="radio" name="votes[<?php echo $pos['id']; ?>]" value="<?php echo $candidate['id']; ?>" class="hidden" required>
                            
                            <img src="../<?php echo $candidate['photo'] ?: 'default.png'; ?>" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-sm mr-4">
                            
                            <div class="flex-1">
                                <p class="font-bold text-gray-900"><?php echo htmlspecialchars($candidate['full_name']); ?></p>
                                <p class="text-xs text-gray-500 mb-1 italic">"<?php echo htmlspecialchars($candidate['nickname']); ?>"</p>
                                <?php echo $vote_count_html; ?>
                            </div>

                            <div class="w-6 h-6 rounded-full border-2 border-gray-300 flex items-center justify-center group-has-[:checked]:border-blue-600 group-has-[:checked]:bg-blue-600">
                                <i class="fas fa-check text-white text-xs opacity-0 group-has-[:checked]:opacity-100"></i>
                            </div>
                        </label>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="pt-6">
                <button type="submit" name="cast_vote" class="w-full bg-green-600 text-white py-5 rounded-2xl font-black text-xl shadow-xl hover:bg-green-700 transform hover:scale-[1.02] transition-all">
                    <i class="fas fa-paper-plane mr-2"></i> SUBMIT FINAL BALLOT
                </button>
                <p class="text-center text-gray-400 text-xs mt-4">By clicking submit, your choices will be encrypted and cannot be changed.</p>
            </div>
        </form>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-10 mt-12">
        <?php include('footer.php'); ?>
    </footer>
</body>
</html>