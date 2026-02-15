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
if (!$data || !isset($data['id_enseignant'])) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$id = $data['id_enseignant'];

// Start transaction
$conn->begin_transaction();

try {
    // Delete from Enseignants table first (foreign key constraint)
    $stmt = $conn->prepare("DELETE FROM Enseignants WHERE id_enseignant = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Then delete from Utilisateurs table
    $stmt = $conn->prepare("DELETE FROM Utilisateurs WHERE id_utilisateur = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Commit the transaction
    $conn->commit();
    echo json_encode(["success" => true, "message" => "Professor deleted successfully"]);
} catch (Exception $e) {
    // Roll back the transaction in case of error
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Failed to delete professor: " . $e->getMessage()]);
}

$conn->close();
?>