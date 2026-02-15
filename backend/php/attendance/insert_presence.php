<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "l3db";

// Receive data from Flask
$recognized_name = $_POST['identity'] ?? null;
$similarity = $_POST['similarity'] ?? null;
$status = $_POST['status'] ?? null;

// Reject if face is unknown or name missing
if ($status !== 'known' || !$recognized_name) {
    http_response_code(200);
    echo json_encode(["message" => "Face not recognized. No attendance recorded."]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Get user ID by full name
    $stmt = $conn->prepare("SELECT id_utilisateur FROM utilisateurs WHERE CONCAT(nom, ' ', prenom) = :full_name");
    $stmt->execute(['full_name' => $recognized_name]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["message" => "User not found in database."]);
        exit;
    }

    $id_utilisateur = $user['id_utilisateur'];

    // 2. Get today's active session
    $stmt = $conn->query("
        SELECT id_seance, heure_debut FROM seances 
        WHERE DATE(date_seance) = CURDATE()
        AND TIME(NOW()) BETWEEN heure_debut AND heure_fin
        LIMIT 1
    ");
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        http_response_code(404);
        echo json_encode(["message" => "No active session found today."]);
        exit;
    }

    $id_seance = $session['id_seance'];
    $heure_debut = $session['heure_debut'];

    // 3. Avoid duplicate presence
    $check = $conn->prepare("
        SELECT 1 FROM presences 
        WHERE id_utilisateur = :id_utilisateur AND id_seance = :id_seance
    ");
    $check->execute([
        'id_utilisateur' => $id_utilisateur,
        'id_seance' => $id_seance
    ]);

    if ($check->fetch()) {
        echo json_encode(["message" => "Presence already recorded."]);
        exit;
    }

    // 4. Determine late status (retard if >15min late)
    $stmt = $conn->query("SELECT TIME(NOW()) AS now_time");
    $now_time = $stmt->fetchColumn();
    $status = (strtotime($now_time) - strtotime($heure_debut) > 900) ? 'retard' : 'présent';

    // 5. Insert attendance
    $stmt = $conn->prepare("
        INSERT INTO presences (id_utilisateur, id_seance, statut, date_reconnaissance, heure_reconnaissance)
        VALUES (:id_utilisateur, :id_seance, :statut, CURDATE(), CURTIME())
    ");
    $stmt->execute([
        'id_utilisateur' => $id_utilisateur,
        'id_seance' => $id_seance,
        'statut' => $status
    ]);

    echo json_encode(["message" => "Presence recorded successfully as $status."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
