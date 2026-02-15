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

file_put_contents("debug_log.txt", "DATA: " . json_encode($data) . PHP_EOL, FILE_APPEND);

// FIX: New expected fields
$id_module    = $data['id_module'] ?? '';
$id_groupe    = $data['id_groupe'] ?? ''; // must come from frontend
$date_seance  = $data['date_seance'] ?? ''; // must come from frontend
$heure_debut  = $data['heure_debut'] ?? '';
$heure_fin    = $data['heure_fin'] ?? '';

file_put_contents("debug_log.txt", "Parsed: " . json_encode([
    "id_module" => $id_module,
    "id_groupe" => $id_groupe,
    "date_seance" => $date_seance,
    "heure_debut" => $heure_debut,
    "heure_fin" => $heure_fin
]) . PHP_EOL, FILE_APPEND);

// Required field check
if (!$id_module || !$id_groupe || !$date_seance || !$heure_debut || !$heure_fin) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// FIX: Insert correct columns
$stmt = $conn->prepare("INSERT INTO Seances (id_module, id_groupe, date_seance, heure_debut, heure_fin) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iisss", $id_module, $id_groupe, $date_seance, $heure_debut, $heure_fin);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    file_put_contents("debug_log.txt", "DB ERROR: " . $stmt->error . PHP_EOL, FILE_APPEND);
    echo json_encode(["success" => false, "message" => "Insert failed"]);
}

$stmt->close();
$conn->close();
?>
