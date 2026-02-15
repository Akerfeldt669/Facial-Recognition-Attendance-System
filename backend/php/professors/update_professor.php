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
if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON"]);
    exit;
}

$id = $data['id_enseignant'] ?? '';
$nom = $data['nom'] ?? '';
$prenom = $data['prenom'] ?? '';
$email = $data['email'] ?? '';
$grade = $data['grade'] ?? '';
$departement = $data['departement'] ?? '';

if (!$id || !$nom || !$prenom || !$email || !$grade || !$departement) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// Update Utilisateurs table
$stmt = $conn->prepare("UPDATE Utilisateurs SET nom = ?, prenom = ?, email = ? WHERE id_utilisateur = ?");
$stmt->bind_param("sssi", $nom, $prenom, $email, $id);
if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Failed to update user information"]);
    exit;
}
$stmt->close();

// Update Enseignants table
$stmt = $conn->prepare("UPDATE Enseignants SET grade = ?, departement = ? WHERE id_enseignant = ?");
$stmt->bind_param("ssi", $grade, $departement, $id);
if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Failed to update professor information"]);
    exit;
}

echo json_encode(["success" => true, "message" => "Professor updated successfully"]);
$conn->close();
?>