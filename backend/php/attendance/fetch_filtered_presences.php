<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Database connection
$conn = new mysqli("localhost", "root", "", "l3db");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Initialize where conditions array
$where_conditions = [];
$params = [];
$types = "";

// Check for filters
if (isset($_GET['session_id']) && !empty($_GET['session_id'])) {
    $where_conditions[] = "s.id_seance = ?";
    $params[] = $_GET['session_id'];
    $types .= "i";
}

if (isset($_GET['date']) && !empty($_GET['date'])) {
    $where_conditions[] = "s.date_seance = ?";
    $params[] = $_GET['date'];
    $types .= "s";
}

if (isset($_GET['level_id']) && !empty($_GET['level_id'])) {
    $where_conditions[] = "n.id_niveau = ?";
    $params[] = $_GET['level_id'];
    $types .= "i";
}

if (isset($_GET['group_id']) && !empty($_GET['group_id'])) {
    $where_conditions[] = "g.id_groupe = ?";
    $params[] = $_GET['group_id'];
    $types .= "i";
}

// Construct base SQL query
$sql = "SELECT 
            e.matricule,
            u.nom,
            u.prenom,
            n.nom_niveau,
            g.nom_groupe,
            p.statut,
            DATE_FORMAT(p.date_heure_reconnaissance, '%Y-%m-%d') AS date_reconnaissance,
            DATE_FORMAT(p.date_heure_reconnaissance, '%H:%i:%s') AS heure_reconnaissance
        FROM 
            Présences p
        JOIN 
            Utilisateur u ON p.id_utilisateur = u.id_utilisateur
        JOIN 
            Etudiants e ON u.id_utilisateur = e.id_etudiant
        JOIN 
            Séances s ON p.id_seance = s.id_seance
        JOIN 
            Groupes g ON e.id_groupe = g.id_groupe
        JOIN 
            Niveaux n ON g.id_niveau = n.id_niveau";

// Add where conditions if any exist
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

// Add order by clause
$sql .= " ORDER BY n.nom_niveau, g.nom_groupe, u.nom, u.prenom";

// Prepare and execute statement with parameters
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Fetch all records
$presences = [];
while ($row = $result->fetch_assoc()) {
    $presences[] = $row;
}

// Return JSON response
echo json_encode($presences);

// Close connection
$stmt->close();
$conn->close();
?>