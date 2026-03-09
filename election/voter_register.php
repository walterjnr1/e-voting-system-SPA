<?php 
include '../database/connection.php'; 
include('../inc/app_data.php'); 

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login"); 
    exit;
}

// --- FETCH VERIFIED VOTERS ---
try {
    // Fetching user details including the user_image column
    $stmt = $dbh->prepare("SELECT full_name, nickname, phone, user_image, created_at 
                           FROM users 
                           WHERE is_verified = 1 ans status='active' 
                           ORDER BY full_name ASC");
    $stmt->execute();
    $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_voters = $stmt->rowCount();
} catch (PDOException $e) {
    $voters = [];
    $total_voters = 0;
    $error = "Could not retrieve the register. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Register | <?php echo htmlspecialchars($app_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="../<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">
    <style>
        .voter-card:hover { transform: translateY(-5px); transition: all 0.3s ease; }
        .voter-img-container { position: relative; width: 110px; height: 110px; }
        .voter-img { width: 100%; height: 100%; object-fit: cover; border-radius: 9999px; border: 4px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
    </style>
</head>
<body class="bg-gray-50 font-sans min-h-screen flex flex-col">

    <nav class="bg-blue-900 text-white p-4 shadow-lg sticky top-0 z-50">
       <?php include('nav.php'); ?>
    </nav>

    <header class="bg-white py-12 px-4 border-b">
        <div class="container mx-auto text-center">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-2">Verified Voter Register</h2>
            <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                Official list of verified members cleared to vote.
            </p>
            <div class="mt-6 inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-bold">
                <i class="fas fa-id-card mr-2"></i> Total Voters: <?php echo $total_voters; ?>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 -mt-6">
        <div class="max-w-md mx-auto bg-white shadow-lg rounded-xl p-2 flex items-center border border-gray-100">
            <i class="fas fa-search text-gray-400 ml-3"></i>
            <input type="text" id="voterSearch" onkeyup="filterVoters()" placeholder="Search voters..." 
                   class="w-full p-3 outline-none text-gray-700 bg-transparent">
        </div>
    </div>

    <main class="container mx-auto px-4 py-12 flex-grow">
        <?php if ($total_voters > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 gap-6" id="voterGrid">
                <?php foreach ($voters as $voter): 
                    // 1. Check if user_image exists in DB, otherwise use a generic system avatar path
                    $image_path = (!empty($voter['user_image'])) ? '../' . $voter['user_image'] : '../assets/images/default_voter.png';
                ?>
                    <div class="voter-card bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                        <div class="p-6 flex flex-col items-center text-center">
                            
                            <div class="voter-img-container mb-4">
                                <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                     alt="<?php echo htmlspecialchars($voter['full_name']); ?>" 
                                     class="voter-img"
                                     onerror="this.src='https://cdn-icons-png.flaticon.com/512/149/149071.png'">
                                
                                <div class="absolute bottom-1 right-2 bg-green-500 w-6 h-6 rounded-full border-2 border-white flex items-center justify-center shadow-sm" title="Verified Voter">
                                    <i class="fas fa-check text-[10px] text-white"></i>
                                </div>
                            </div>

                            <h3 class="voter-name font-bold text-gray-800 text-lg leading-tight mb-1">
                                <?php echo htmlspecialchars($voter['full_name']); ?>
                            </h3>
                            
                            <?php if(!empty($voter['nickname'])): ?>
                                <span class="voter-nick text-blue-600 text-xs font-bold uppercase tracking-widest px-2 py-1 bg-blue-50 rounded">
                                    <?php echo htmlspecialchars($voter['nickname']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-transparent text-xs select-none">No Nickname</span>
                            <?php endif; ?>

                            <div class="mt-6 w-full pt-4 border-t border-gray-50 flex items-center justify-center text-gray-600 font-medium">
                                <div class="bg-gray-100 rounded-lg px-3 py-1 text-sm">
                                    <i class="fas fa-phone-alt mr-2 text-blue-900 opacity-70"></i>
                                    <?php echo htmlspecialchars($voter['phone']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-20 bg-white rounded-3xl shadow-sm border border-dashed border-gray-300">
                <i class="fas fa-user-clock text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-bold text-gray-800">Register Empty</h3>
                <p class="text-gray-500">No verified voters have been listed yet.</p>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function filterVoters() {
            const input = document.getElementById('voterSearch');
            const filter = input.value.toUpperCase();
            const grid = document.getElementById('voterGrid');
            const cards = grid.getElementsByClassName('voter-card');

            for (let i = 0; i < cards.length; i++) {
                const name = cards[i].querySelector('.voter-name').innerText;
                const nick = cards[i].querySelector('.voter-nick') ? cards[i].querySelector('.voter-nick').innerText : '';
                
                if (name.toUpperCase().indexOf(filter) > -1 || nick.toUpperCase().indexOf(filter) > -1) {
                    cards[i].style.display = "";
                } else {
                    cards[i].style.display = "none";
                }
            }
        }
    </script>

    <footer class="bg-gray-900 text-gray-400 py-10 mt-auto">
        <?php include('../footer.php'); ?>
    </footer>

</body>
</html>