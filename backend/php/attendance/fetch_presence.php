<?php
require 'db_connection.php'; // update to your DB connection script

$sessionId = $_GET['session_id'] ?? null;

if (!$sessionId) {
    echo json_encode(["error" => "Missing session ID"]);
    exit;
}

$sql = "
SELECT 
    e.matricule,
    u.nom,
    u.prenom,
    m.nom_module,
    p.heure_reconnaissance,
    p.date_reconnaissance
FROM presences p
JOIN etudiants e ON p.id_etudiant = e.id_etudiant
JOIN utilisateurs u ON e.id_etudiant = u.id_utilisateur
JOIN seances s ON p.id_seance = s.id_seance
JOIN modules m ON s.id_module = m.id_module
WHERE p.id_seance = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$sessionId]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
