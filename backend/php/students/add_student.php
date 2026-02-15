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

// === Get Input (FormData) ===
$matricule = $_POST['matricule'] ?? '';
$nom = $_POST['nom'] ?? '';
$prenom = $_POST['prenom'] ?? '';
$email = $_POST['email'] ?? '';
$levelId = (int)($_POST['id_niveau'] ?? 0);
$groupId = (int)($_POST['id_groupe'] ?? 0);
$passwordPlain = $_POST['password'] ?? '';
$photo = null;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
    $photo = file_get_contents($_FILES['photo']['tmp_name']);
}

// Check if required fields are filled
if (!$matricule || !$nom || !$prenom || !$email || !$levelId || !$groupId || !$passwordPlain) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// === Hash Password ===
$passwordHashed = password_hash($passwordPlain, PASSWORD_BCRYPT);

// === Insert into Utilisateurs ===
$stmt = $conn->prepare("
    INSERT INTO Utilisateurs (nom, prenom, email, mot_de_passe, role, photo_ref)
    VALUES (?, ?, ?, ?, 'etudiant', ?)
");
$stmt->bind_param("sssss", $nom, $prenom, $email, $passwordHashed, $photo);

if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Email already used or failed"]);
    exit;
}

$userId = $stmt->insert_id;
$stmt->close();

// === Insert into Etudiants ===
$stmt = $conn->prepare("
    INSERT INTO Etudiants (id_etudiant, matricule, id_niveau, id_groupe)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("isii", $userId, $matricule, $levelId, $groupId);

if (!$stmt->execute()) {
    $conn->query("DELETE FROM Utilisateurs WHERE id_utilisateur = $userId"); // Rollback if fail
    echo json_encode(["success" => false, "message" => "Matricule already exists"]);
    exit;
}

echo json_encode(["success" => true, "message" => "Student added successfully"]);
$conn->close();
?>
