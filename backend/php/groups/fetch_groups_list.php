<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "l3db");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

$sql = "
    SELECT 
        g.id_groupe, 
        g.nom_groupe, 
        n.nom_niveau
    FROM Groupes g
    JOIN Niveaux n ON g.id_niveau = n.id_niveau
";

$result = $conn->query($sql);

$groups = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $groups[] = $row;
    }
}

echo json_encode($groups);
$conn->close();
?>
