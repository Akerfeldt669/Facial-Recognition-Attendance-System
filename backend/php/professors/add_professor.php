<?php
// === CORS Headers ===
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// === Error Reporting ===
ini_set('display_errors', 1);
error_reporting(E_ALL);
// === DB Connection ===
$conn = new mysqli("localhost", "root", "", "l3db");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}
// === Get JSON Input ===
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON"]);
    exit;
}
// === Extract & Validate Input ===
$nom = $data['nom'] ?? '';
$prenom = $data['prenom'] ?? '';
$email = $data['email'] ?? '';
$grade = $data['grade'] ?? '';
$departement = $data['departement'] ?? '';
if (!$nom || !$prenom || !$email || !$grade || !$departement) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}
// === Insert into Utilisateurs ===
$stmt = $conn->prepare("
    INSERT INTO Utilisateurs (nom, prenom, email, mot_de_passe, role)
    VALUES (?, ?, ?, ?, 'enseignant')
");
$password = password_hash("default123", PASSWORD_BCRYPT);
$stmt->bind_param("ssss", $nom, $prenom, $email, $password);
if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Email already used or failed"]);
    exit;
}
$userId = $stmt->insert_id;
$stmt->close();
// === Insert into Enseignants ===
$stmt = $conn->prepare("
    INSERT INTO Enseignants (id_enseignant, grade, departement)
    VALUES (?, ?, ?)
");
$stmt->bind_param("iss", $userId, $grade, $departement);
if (!$stmt->execute()) {
    $conn->query("DELETE FROM Utilisateurs WHERE id_utilisateur = $userId");
    echo json_encode(["success" => false, "message" => "Failed to add professor"]);
    exit;
}
echo json_encode(["success" => true, "message" => "Professor added successfully"]);
$conn->close();
?>