<?php
include '../database/connection.php';
include('../inc/app_data.php');

header('Content-Type: application/json');

$election_id = $_GET['election_id'] ?? null;
if (!$election_id) { echo json_encode(['error' => 'No ID']); exit; }

// Encryption Settings (Must match ballot.php)
$encryption_method = "AES-256-CBC";
$key = hash('sha256', ENCRYPTION_KEY);
$iv = substr(hash('sha256', ENCRYPTION_IV), 0, 16);

// 1. Get Positions
$posQuery = $dbh->prepare("SELECT id, title FROM positions WHERE election_id = ? ORDER BY id ASC");
$posQuery->execute([$election_id]);
$positions = $posQuery->fetchAll(PDO::FETCH_ASSOC);

// 2. Get All Encrypted Votes for this election
$voteQuery = $dbh->prepare("SELECT candidate_id FROM votes WHERE election_id = ?");
$voteQuery->execute([$election_id]);
$all_votes = $voteQuery->fetchAll(PDO::FETCH_COLUMN);

// 3. Tally Decrypted Votes in PHP
$tally = [];
foreach ($all_votes as $encrypted_id) {
    $decrypted_id = openssl_decrypt($encrypted_id, $encryption_method, $key, 0, $iv);
    if ($decrypted_id) {
        if (!isset($tally[$decrypted_id])) {
            $tally[$decrypted_id] = 0;
        }
        $tally[$decrypted_id]++;
    }
}

// 4. Build Structure
$response_data = ['positions' => []];

foreach ($positions as $pos) {
    $pos_item = [
        'id' => $pos['id'],
        'title' => $pos['title'],
        'candidates' => []
    ];

    $canQuery = $dbh->prepare("SELECT c.id, u.full_name, c.photo FROM candidates c JOIN users u ON c.user_id = u.id WHERE c.position_id = ?");
    $canQuery->execute([$pos['id']]);
    $candidates = $canQuery->fetchAll(PDO::FETCH_ASSOC);

    foreach ($candidates as $can) {
        $pos_item['candidates'][] = [
            'name' => $can['full_name'],
            'photo' => $can['photo'],
            'votes' => $tally[$can['id']] ?? 0
        ];
    }
    
    $response_data['positions'][] = $pos_item;
}

// 5. Summary Stats
$total_voters = $dbh->query("SELECT COUNT(*) FROM users")->fetchColumn();
$votes_cast = $dbh->prepare("SELECT COUNT(DISTINCT id) FROM users WHERE has_voted = 1");
$votes_cast->execute();
$v_cast = $votes_cast->fetchColumn();

$response_data['summary'] = [
    'total_voters' => (int)$total_voters,
    'votes_cast' => (int)$v_cast,
    'turnout_pct' => $total_voters > 0 ? round(($v_cast / $total_voters) * 100, 1) : 0
];

echo json_encode($response_data);