<?php
// check-status.php — Endpoint AJAX appelé par attente.php
include('db.php');
header('Content-Type: application/json');

$email = isset($_GET['email']) ? mysqli_real_escape_string($conn, $_GET['email']) : '';

if (empty($email)) {
    echo json_encode(['statut' => 'inconnu']);
    exit();
}

$result = mysqli_query($conn, "SELECT statut FROM utilisateur WHERE email='$email'");
$user   = mysqli_fetch_assoc($result);

if (!$user) {
    echo json_encode(['statut' => 'inconnu']);
    exit();
}

echo json_encode(['statut' => strtolower(trim($user['statut']))]);