<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$db = "l3db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sessionId = $_GET['session'] ?? 0;

$sql = "SELECT e.matricule, u.nom, u.prenom, m.nom_module,
               p.statut,
               TIME(p.heure_reconnaissance) AS heure_reconnaissance,
               DATE(p.date_reconnaissance) AS date_reconnaissance
        FROM presences p
        JOIN utilisateurs u ON p.id_utilisateur = u.id_utilisateur
        JOIN etudiants e ON e.id_etudiant = u.id_utilisateur
        JOIN seances s ON p.id_seance = s.id_seance
        JOIN modules m ON s.id_module = m.id_module
        WHERE s.id_seance = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL error: " . $conn->error);
}

$stmt->bind_param("i", $sessionId);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
$conn->close();
?>
