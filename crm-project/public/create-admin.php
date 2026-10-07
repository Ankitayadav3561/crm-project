<?php

require_once __DIR__ . '/../config/database.php';

$name = 'Admin';
$email = 'admin@crm.com';
$password = 'admin123';

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users
        (name, email, password, role, status)
        VALUES
        (:name, :email, :password, :role, :status)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':name' => $name,
    ':email' => $email,
    ':password' => $hashedPassword,
    ':role' => 'admin',
    ':status' => 1
]);

echo "Admin created successfully!";