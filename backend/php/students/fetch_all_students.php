<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "l3db");

$levelId = $_GET['level_id'] ?? null;
$groupId = $_GET['group_id'] ?? null;

$query = "SELECT e.id_etudiant, e.matricule, u.nom, u.prenom, u.email, 
                 n.nom as nom_niveau, g.nom as nom_groupe
          FROM Etudiants e
          JOIN Utilisateurs u ON e.id_etudiant = u.id_utilisateur
          JOIN Niveaux n ON e.id_niveau = n.id_niveau
          JOIN Groupes g ON e.id_groupe = g.id_groupe
          WHERE (? IS NULL OR e.id_niveau = ?)
          AND (? IS NULL OR e.id_groupe = ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $levelId, $levelId, $groupId, $groupId);
$stmt->execute();

$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($students);
$conn->close();
?>