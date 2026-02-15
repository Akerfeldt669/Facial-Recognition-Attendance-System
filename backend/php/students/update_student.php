<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$conn = new mysqli("localhost", "root", "", "l3db");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON"]);
    exit;
}

$id = $data['id_etudiant'];
$matricule = $data['matricule'];
$nom = $data['nom'];
$prenom = $data['prenom'];
$email = $data['email'];
$niveau = $data['id_niveau'];
$groupe = $data['id_groupe'];

$conn->begin_transaction();

try {
    $stmt1 = $conn->prepare("UPDATE Utilisateurs SET nom = ?, prenom = ?, email = ? WHERE id_utilisateur = ?");
    $stmt1->bind_param("sssi", $nom, $prenom, $email, $id);
    $stmt1->execute();

    $stmt2 = $conn->prepare("UPDATE Etudiants SET matricule = ?, id_niveau = ?, id_groupe = ? WHERE id_etudiant = ?");
    $stmt2->bind_param("siii", $matricule, $niveau, $groupe, $id);
    $stmt2->execute();

    $conn->commit();
    echo json_encode(["success" => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Update failed: " . $e->getMessage()]);
}

$conn->close();
?>
