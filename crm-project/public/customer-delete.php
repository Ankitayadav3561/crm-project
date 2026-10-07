<?php

require_once __DIR__ . '/../app/auth.php';

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../config/database.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    die("Invalid request.");

}


$id = $_POST['id'] ?? null;


if (!$id || !is_numeric($id)) {

    die("Invalid customer ID.");

}


$sql = "DELETE FROM customers WHERE id = :id";


$stmt = $pdo->prepare($sql);


$stmt->execute([

    ':id' => $id

]);


header("Location: customers.php");

exit;