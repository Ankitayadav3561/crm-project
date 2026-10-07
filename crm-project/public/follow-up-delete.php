<?php

require_once __DIR__ . '/../app/auth.php';


// Sirf admin
requireAdmin();


require_once __DIR__ . '/../config/database.php';


// POST request only

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}


$id = $_POST['id'] ?? null;


if (!$id || !is_numeric($id)) {
    die("Invalid follow-up ID.");
}


// Delete

$sql = "DELETE FROM follow_ups
        WHERE id = :id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id
]);


header("Location: follow-ups.php");

exit;