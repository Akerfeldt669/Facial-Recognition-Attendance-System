<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "l3db");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$sql = "
SELECT 
    Seances.id_seance,
    Seances.date_seance,
    Seances.heure_debut,
    Seances.heure_fin,
    Modules.nom_module,
    CONCAT(Niveaux.nom_niveau, ' ', Groupes.nom_groupe) AS nom_groupe_complet
FROM 
    Seances
JOIN 
    Modules ON Seances.id_module = Modules.id_module
JOIN 
    Groupes ON Seances.id_groupe = Groupes.id_groupe
JOIN
    Niveaux ON Groupes.id_niveau = Niveaux.id_niveau
ORDER BY 
    Seances.date_seance ASC
";

$result = $conn->query($sql);

$sessions = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
    echo json_encode($sessions);
} else {
    echo json_encode([]);
}

$conn->close();
?>
