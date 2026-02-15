<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "l3db");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid data"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO Seances (id_module, id_groupe, date_seance, heure_debut, heure_fin) VALUES (?, ?, ?, ?, ?)");

foreach ($data as $session) {
    $stmt->bind_param("iisss", 
        $session['id_module'], 
        $session['id_groupe'], 
        $session['date_seance'], 
        $session['heure_debut'], 
        $session['heure_fin']
    );
    $stmt->execute();
}

$stmt->close();
$conn->close();

echo json_encode(["success" => true]);
?>
