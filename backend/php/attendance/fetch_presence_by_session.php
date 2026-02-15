<?php
require_once 'db_connection.php';

$sessionId = $_GET['session_id'] ?? null;

if (!$sessionId) {
    echo json_encode([]);
    exit;
}

$query = "
SELECT 
    u.nom,
    u.prenom,
    g.nom_groupe,
    p.heure_reconnaissance,
    p.statut
FROM presences p
JOIN utilisateurs u ON p.id_utilisateur = u.id_utilisateur
JOIN etudiants e ON u.id_utilisateur = e.id_etudiant
JOIN groupes g ON e.id_groupe = g.id_groupe
WHERE p.id_seance = ?
ORDER BY p.heure_reconnaissance ASC;
";

$stmt = $pdo->prepare($query);
$stmt->execute([$sessionId]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($data);
