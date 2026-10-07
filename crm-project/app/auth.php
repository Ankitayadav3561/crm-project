<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Agar user login nahi hai
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Admin Access
|--------------------------------------------------------------------------
| Sirf admin ko allow karega
*/

function requireAdmin()
{
    if (
        !isset($_SESSION['user_role']) ||
        $_SESSION['user_role'] !== 'admin'
    ) {
        http_response_code(403);
        die("Access denied.");
    }
}