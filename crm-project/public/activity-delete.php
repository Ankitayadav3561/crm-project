
<?php

require_once __DIR__ . '/../app/auth.php';

requireAdmin();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$id = $_POST['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("Invalid activity ID.");
}

/*
|--------------------------------------------------------------------------
| Get Customer ID
|--------------------------------------------------------------------------
*/

$sql = "SELECT customer_id
        FROM activities
        WHERE id = :id
        LIMIT 1";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id
]);

$activity = $stmt->fetch();

if (!$activity) {
    die("Activity not found.");
}

$customerId = $activity['customer_id'];

/*
|--------------------------------------------------------------------------
| Delete Activity
|--------------------------------------------------------------------------
*/

$sql = "DELETE FROM activities
        WHERE id = :id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id
]);

header(
    "Location: customer-view.php?id=" .
    urlencode($customerId)
);

exit;

