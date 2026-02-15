<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "l3db");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$query = "
    SELECT m.id_module, m.nom_module, m.id_enseignant,
           u.nom AS nom_professeur, u.prenom AS prenom_professeur
    FROM Modules m
    LEFT JOIN Enseignants e ON m.id_enseignant = e.id_enseignant
    LEFT JOIN Utilisateurs u ON u.id_utilisateur = e.id_enseignant
    ORDER BY m.nom_module
";

$result = $conn->query($query);
$modules = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $modules[] = $row;
    }
}

echo json_encode($modules);
$conn->close();
?>
