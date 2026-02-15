<?php
header("Content-Type: application/json");

$conn = new mysqli("localhost", "username", "password", "l3db");

// Fetch professors with grade
$sql = "
    SELECT 
        e.id_enseignant, 
        u.nom, 
        u.prenom, 
        e.grade, 
        e.departement
    FROM Enseignants e
    JOIN Utilisateurs u ON e.id_enseignant = u.id_utilisateur
";

$result = $conn->query($sql);
$professors = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($professors);
?>