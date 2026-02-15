<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "l3db");
if ($conn->connect_error) {
    die(json_encode(["error" => "DB connection failed"]));
}

$levelId = $_GET['level_id'] ?? null;
$groupId = $_GET['group_id'] ?? null;

if (!$levelId || !$groupId) {
    die(json_encode(["error" => "Missing level_id or group_id"]));
}

$sql = "
    SELECT 
        e.id_etudiant,
        e.matricule, 
        u.nom, 
        u.prenom,
        u.email,
        n.nom_niveau AS level, 
        g.nom_groupe AS group_name
    FROM Etudiants e
    JOIN Utilisateurs u ON e.id_etudiant = u.id_utilisateur
    JOIN Niveaux n ON e.id_niveau = n.id_niveau
    JOIN Groupes g ON e.id_groupe = g.id_groupe
    WHERE n.id_niveau = ? AND g.id_groupe = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $levelId, $groupId);
$stmt->execute();
$result = $stmt->get_result();

$students = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($students);

$stmt->close();
$conn->close();
?>
