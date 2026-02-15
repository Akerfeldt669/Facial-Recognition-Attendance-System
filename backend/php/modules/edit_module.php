<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$conn = new mysqli("localhost", "root", "", "l3db");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id_module'] ?? '';
$nom = $data['nom_module'] ?? '';
$id_ens = $data['id_enseignant'] ?? '';

if (!$id || !$nom || !$id_ens) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

$stmt = $conn->prepare("UPDATE Modules SET nom_module = ?, id_enseignant = ? WHERE id_module = ?");
$stmt->bind_param("sii", $nom, $id_ens, $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Module updated"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update module"]);
}

$stmt->close();
$conn->close();
?>
