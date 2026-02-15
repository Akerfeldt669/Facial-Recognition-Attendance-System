<?php
// === CORS HEADERS ===
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// === CONNECT TO DB ===
$conn = new mysqli("localhost", "root", "", "l3db");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// === GET JSON INPUT ===
$data = json_decode(file_get_contents("php://input"), true);
$nom_module = $data['nom_module'] ?? '';
$id_enseignant = $data['id_enseignant'] ?? '';

// === VALIDATE ===
if (!$nom_module || !$id_enseignant) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// === INSERT INTO Modules ===
$stmt = $conn->prepare("INSERT INTO Modules (nom_module, id_enseignant) VALUES (?, ?)");
$stmt->bind_param("si", $nom_module, $id_enseignant);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Module added successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to add module"]);
}

$stmt->close();
$conn->close();
?>
