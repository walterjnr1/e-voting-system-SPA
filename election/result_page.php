<?php 
include '../database/connection.php'; 
include('../inc/app_data.php'); 

// 1. Fetch the currently active or most recent election
$stmt = $dbh->prepare("SELECT * FROM elections WHERE status IN ('active', 'closed') ORDER BY id DESC LIMIT 1");
$stmt->execute();
$election = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$election) {
    die("No active or recently closed election found.");
}

$election_id = $election['id'];
$election_end_time = $election['end_datetime'];

// 2. Check if user is allowed to view results
if ($election['allow_result_view'] == 0) {
    die("Results for this election are currently hidden by the administrator.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Results | <?php echo htmlspecialchars($app_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="../<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">
</head>
<body class="bg-gray-50 font-sans min-h-screen">

    <nav class="bg-blue-900 text-white p-4 shadow-lg sticky top-0 z-50">
        <?php include('nav.php'); ?>
    </nav>

    <main class="container mx-auto px-4 py-10">
        <div class="flex flex-col lg:flex-row gap-8">
            
            <div class="lg:w-2/3">
                <header class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end">
                    <div>
                        <h2 class="text-3xl font-black text-gray-900 uppercase tracking-tighter"><?php echo htmlspecialchars($election['title']); ?></h2>
                        <div class="h-1 w-20 bg-blue-600 mt-2 mb-4"></div>
                        <p class="text-gray-600">Real-time breakdown of decrypted votes per candidate.</p>
                    </div>
                    <div class="mt-4 md:mt-0 flex flex-col items-end space-y-2">
                        <?php if($election['status'] == 'active'): ?>
                        <div class="flex items-center space-x-2 bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold animate-pulse">
                            <span class="relative flex h-2 w-2">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            <span>LIVE UPDATING</span>
                        </div>
                        <?php else: ?>
                        <div class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold">
                             <i class="fas fa-lock mr-1"></i> FINAL TALLY
                        </div>
                        <?php endif; ?>

                        <div class="text-right">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Election Status</p>
                            <p id="header-timer" class="text-xl font-mono font-black text-blue-900 uppercase">CALCULATING...</p>
                        </div>
                    </div>
                </header>

                <div id="results-container" class="space-y-12">
                    <div class="flex justify-center py-20">
                        <i class="fas fa-circle-notch fa-spin text-4xl text-blue-600"></i>
                    </div>
                </div>
            </div>

            <div class="lg:w-1/3 space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 sticky top-24">
                    
                    <div class="mb-6 bg-blue-50 p-4 rounded-xl border border-blue-100">
                        <h4 class="font-bold text-blue-900 mb-2 flex items-center text-sm">
                            <i class="fas fa-clock mr-2"></i> Polls Status:
                        </h4>
                        <div id="sidebar-timer" class="text-2xl font-mono font-black text-blue-700 tracking-tighter">
                            --d --h --m --s
                        </div>
                    </div>

                    <h4 class="font-bold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-chart-pie mr-2 text-blue-600"></i> Participation Summary
                    </h4>
                    
                    <div id="stats-sidebar">
                        <div class="animate-pulse space-y-4">
                            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                            <div class="h-8 bg-gray-200 rounded w-full"></div>
                        </div>
                    </div>

                    <div class="mt-8 bg-gray-900 p-5 rounded-2xl">
                        <h5 class="text-white text-xs font-bold uppercase mb-2 tracking-widest">Audit Transparency</h5>
                        <p class="text-[11px] text-gray-400 leading-relaxed">
                            <strong>Security:</strong> All votes are stored with AES-256 encryption. This dashboard performs live decryption of the vote vault to provide real-time updates.
                            <strong>Audit:</strong> Final results are cross-referenced with network IP logs and voter authentication tokens.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        const chartInstances = {};
        const electionEndTime = new Date("<?php echo $election_end_time; ?>").getTime();
        const electionStatus = "<?php echo $election['status']; ?>";

        function updateTimer() {
            const now = new Date().getTime();
            const distance = electionEndTime - now;

            if (distance < 0 || electionStatus === 'closed') {
                document.getElementById("header-timer").innerHTML = "CLOSED";
                document.getElementById("sidebar-timer").innerHTML = "ELECTION ENDED";
                document.getElementById("sidebar-timer").classList.replace('text-blue-700', 'text-red-600');
                return false; 
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            const timerStr = `${days}d ${hours}h ${minutes}m ${seconds}s`;
            document.getElementById("header-timer").innerText = timerStr;
            document.getElementById("sidebar-timer").innerText = timerStr;
            return true;
        }

        async function fetchElectionData() {
            try {
                const response = await fetch(`fetch_live_data.php?election_id=<?php echo $election_id; ?>`);
                const data = await response.json();
                renderUI(data);
            } catch (error) {
                console.error("Error fetching live data:", error);
            }
        }

        function renderUI(data) {
            const container = document.getElementById('results-container');
            let html = '';

            data.positions.forEach(pos => {
                const totalPositionVotes = pos.candidates.reduce((acc, c) => acc + parseInt(c.votes), 0);
                
                html += `
                <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                    <h3 class="text-xl font-black text-gray-800 mb-6 flex items-center">
                        <span class="w-2 h-6 bg-blue-600 rounded-full mr-3"></span>
                        ${pos.title}
                        <span class="ml-auto text-xs font-normal text-gray-400">${totalPositionVotes} Total Votes</span>
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                        <div class="space-y-6">
                            ${pos.candidates.map(cand => {
                                const pct = totalPositionVotes > 0 ? ((cand.votes / totalPositionVotes) * 100).toFixed(1) : 0;
                                return `
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <div class="flex items-center">
                                            <img src="../${cand.photo || 'assets/images/default.png'}" class="w-24 h-24 rounded-full mr-2 object-cover border border-gray-200">
                                            <span class="font-bold text-gray-700">${cand.name}</span>
                                        </div>
                                        <span class="text-blue-600 font-black">${cand.votes} <small class="text-gray-400 text-[10px]">VOTES</small></span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-4 overflow-hidden">
                                        <div class="bg-blue-600 h-full rounded-full transition-all duration-1000" style="width: ${pct}%"></div>
                                    </div>
                                    <p class="text-[10px] text-right mt-1 text-gray-400 font-bold">${pct}% OF SECTOR</p>
                                </div>`;
                            }).join('')}
                        </div>
                        <div class="h-56 flex justify-center">
                            <canvas id="chart-${pos.id}"></canvas>
                        </div>
                    </div>
                </div>`;
            });

            container.innerHTML = html;

            data.positions.forEach(pos => {
                const ctx = document.getElementById(`chart-${pos.id}`).getContext('2d');
                const labels = pos.candidates.map(c => c.name);
                const votes = pos.candidates.map(c => c.votes);

                if (chartInstances[pos.id]) {
                    chartInstances[pos.id].data.labels = labels;
                    chartInstances[pos.id].data.datasets[0].data = votes;
                    chartInstances[pos.id].update();
                } else {
                    chartInstances[pos.id] = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: votes,
                                backgroundColor: ['#1e3a8a', '#2563eb', '#60a5fa', '#93c5fd', '#bfdbfe'],
                                borderWidth: 4,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { 
                                legend: { position: 'bottom', labels: { boxWidth: 12, font: { weight: 'bold', size: 10 } } } 
                            },
                            cutout: '70%'
                        }
                    });
                }
            });

            updateStatsSidebar(data.summary);
        }

        function updateStatsSidebar(summary) {
            document.getElementById('stats-sidebar').innerHTML = `
                <div class="space-y-6">
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600 tracking-tight">Voter Turnout</span>
                            <span class="text-sm font-black text-blue-700">${summary.turnout_pct}%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-3">
                            <div class="bg-blue-600 h-3 rounded-full transition-all duration-1000" style="width: ${summary.turnout_pct}%"></div>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-2 uppercase font-bold">
                            ${summary.votes_cast} / ${summary.total_voters} eligible voters participated
                        </p>
                    </div>
                    <hr class="border-gray-100">
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="bg-gray-50 p-3 rounded-xl shadow-sm border border-gray-100">
                            <p class="text-2xl font-black text-gray-900">${summary.votes_cast}</p>
                            <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest">Total Votes</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-xl shadow-sm border border-gray-100">
                            <p class="text-2xl font-black text-gray-300">${summary.total_voters - summary.votes_cast}</p>
                            <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest">Yet to Vote</p>
                        </div>
                    </div>
                </div>
            `;
        }

        window.onload = () => {
            fetchElectionData();
            updateTimer();
            setInterval(fetchElectionData, 10000); // 10s refresh for production stability
            setInterval(updateTimer, 1000); 
        };
    </script>

    <footer class="bg-gray-900 text-gray-400 py-10">
        <?php include('../footer.php'); ?>
    </footer>
</body>
</html>