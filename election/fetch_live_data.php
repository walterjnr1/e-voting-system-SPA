<?php
include '../database/connection.php';
header('Content-Type: application/json');

$election_id = $_GET['election_id'] ?? 0;

try {
    // 1. Get Positions
    $pos_stmt = $dbh->prepare("SELECT id, title FROM positions WHERE election_id = ?");
    $pos_stmt->execute([$election_id]);
    $positions = $pos_stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];

    foreach ($positions as $pos) {
        // 2. Get Candidates and their specific Vote Counts
        $cand_stmt = $dbh->prepare("
            SELECT c.id, u.full_name as name, c.photo, 
            (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = c.id AND v.position_id = ?) as votes
            FROM candidates c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.position_id = ? AND c.status = 'approved'
        ");
        $cand_stmt->execute([$pos['id'], $pos['id']]);
        $candidates = $cand_stmt->fetchAll(PDO::FETCH_ASSOC);

        $results[] = [
            'id' => $pos['id'],
            'title' => $pos['title'],
            'candidates' => $candidates
        ];
    }

    // 3. Summary Stats
    $total_eligible = $dbh->query("SELECT COUNT(*) FROM users ")->fetchColumn();
    $total_voted = $dbh->query("SELECT COUNT(DISTINCT id) FROM users WHERE has_voted = 1")->fetchColumn();
    $turnout = $total_eligible > 0 ? ($total_voted / $total_eligible) * 100 : 0;

    echo json_encode([
        'summary' => [
            'total_voters' => (int)$total_eligible,
            'votes_cast' => (int)$total_voted,
            'turnout_pct' => round($turnout, 1)
        ],
        'positions' => $results
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}