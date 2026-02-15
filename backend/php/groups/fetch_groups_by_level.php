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

if (!$levelId) {
    die(json_encode(["error" => "Missing level_id"]));
}

$sql = "
    SELECT 
        id_groupe, 
        nom_groupe 
    FROM Groupes 
    WHERE id_niveau = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $levelId);
$stmt->execute();
$result = $stmt->get_result();

$groups = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($groups);

$stmt->close();
$conn->close();
?>
