<?php
header("Content-Type: application/json");

$conn = new mysqli("localhost", "username", "password", "l3db");

// Fetch sessions with module names
$sql = "
    SELECT s.id_seance, s.date_seance, m.nom_module
    FROM Seances s
    JOIN Modules m ON s.id_module = m.id_module
";

$result = $conn->query($sql);
$sessions = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($sessions);
?>