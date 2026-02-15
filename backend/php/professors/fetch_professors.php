<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "l3db");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$query = "SELECT e.id_enseignant, u.nom, u.prenom, u.email, e.grade, e.departement 
          FROM Enseignants e 
          JOIN Utilisateurs u ON e.id_enseignant = u.id_utilisateur 
          ORDER BY u.nom, u.prenom";

$result = $conn->query($query);
$professors = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $professors[] = $row;
    }
}

echo json_encode($professors);
$conn->close();
?>