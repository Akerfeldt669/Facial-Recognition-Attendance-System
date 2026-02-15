<?php
// Connect to the database
$conn = new mysqli("localhost", "root", "", "l3db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get session_id from URL parameter
$sessionId = $_GET['session_id'] ?? null;

if (!$sessionId) {
    echo json_encode(["error" => "Missing session ID"]);
    exit;
}

$sql = "
SELECT 
    e.matricule,
    u.nom,
    u.prenom,
    m.nom_module,
    p.heure_reconnaissance,
    p.date_reconnaissance
FROM presences p
JOIN etudiants e ON p.id_etudiant = e.id_etudiant
JOIN utilisateurs u ON e.id_etudiant = u.id_utilisateur
JOIN seances s ON p.id_seance = s.id_seance
JOIN modules m ON s.id_module = m.id_module
WHERE p.id_seance = ?
";

// Prepare the SQL statement
$stmt = $conn->prepare($sql);

// Check if prepare() succeeded
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

// Bind the parameter (assuming session_id is integer)
$stmt->bind_param("i", $sessionId);

// Execute the query
$stmt->execute();

// Get the result set
$result = $stmt->get_result();

// Fetch all data as associative array
$data = $result->fetch_all(MYSQLI_ASSOC);

// Output JSON encoded data
echo json_encode($data);

// Close statement and connection
$stmt->close();
$conn->close();
?>
