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

if (!$id) {
    echo json_encode(["success" => false, "message" => "Missing module ID"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM Modules WHERE id_module = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Module deleted"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to delete"]);
}

$stmt->close();
$conn->close();
?>
