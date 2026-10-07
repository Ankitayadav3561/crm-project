
<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../config/database.php';

$customerId = $_GET['customer_id']
    ?? $_POST['customer_id']
    ?? null;

if (!$customerId || !is_numeric($customerId)) {
    die("Invalid customer ID.");
}

/*
|--------------------------------------------------------------------------
| Customer Access Check
|--------------------------------------------------------------------------
*/

if ($_SESSION['user_role'] === 'admin') {

    $sql = "SELECT
                id,
                name,
                company
            FROM customers
            WHERE id = :id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id' => $customerId
    ]);

} else {

    $sql = "SELECT
                id,
                name,
                company
            FROM customers
            WHERE id = :id
            AND assigned_to = :user_id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id' => $customerId,
        ':user_id' => $_SESSION['user_id']
    ]);
}

$customer = $stmt->fetch();

if (!$customer) {

    http_response_code(403);

    die("Access denied. You are not allowed to add activity for this customer.");
}

$message = '';

/*
|--------------------------------------------------------------------------
| Add Activity
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $activityType = $_POST['activity_type'] ?? '';
    $description = trim($_POST['description'] ?? '');

    $allowedTypes = [
        'call',
        'email',
        'meeting',
        'note',
        'other'
    ];

    if (!in_array($activityType, $allowedTypes, true)) {

        $message = "Invalid activity type.";

    } elseif ($description === '') {

        $message = "Description is required.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Insert Activity
        |--------------------------------------------------------------------------
        */

        $sql = "INSERT INTO activities
                (
                    customer_id,
                    user_id,
                    activity_type,
                    description
                )
                VALUES
                (
                    :customer_id,
                    :user_id,
                    :activity_type,
                    :description
                )";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':customer_id' => $customerId,
            ':user_id' => $_SESSION['user_id'],
            ':activity_type' => $activityType,
            ':description' => $description
        ]);

        header(
            "Location: customer-view.php?id=" .
            urlencode($customerId)
        );

        exit;
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>
        Add Activity
    </title>

</head>

<body>

<h1>
    Add Activity
</h1>

<p>

    <a href="dashboard.php">
        Dashboard
    </a>

    |

    <a href="customers.php">
        Customers
    </a>

    |

    <a
        href="customer-view.php?id=<?php echo $customer['id']; ?>"
    >
        Back to Customer
    </a>

</p>

<hr>

<h2>

    Customer:
    <?php echo htmlspecialchars($customer['name']); ?>

</h2>

<?php if (!empty($customer['company'])): ?>

    <p>

        Company:
        <?php echo htmlspecialchars($customer['company']); ?>

    </p>

<?php endif; ?>

<?php if ($message): ?>

    <p>
        <?php echo htmlspecialchars($message); ?>
    </p>

<?php endif; ?>

<hr>

<form method="POST">

    <input
        type="hidden"
        name="customer_id"
        value="<?php echo htmlspecialchars($customer['id']); ?>"
    >

    <div>

        <label>
            Activity Type
        </label>

        <br>

        <select name="activity_type" required>

            <option value="">
                Select Activity
            </option>

            <option value="call">
                Call
            </option>

            <option value="email">
                Email
            </option>

            <option value="meeting">
                Meeting
            </option>

            <option value="note">
                Note
            </option>

            <option value="other">
                Other
            </option>

        </select>

    </div>

    <br>

    <div>

        <label>
            Description
        </label>

        <br>

        <textarea
            name="description"
            rows="6"
            cols="60"
            placeholder="Customer ke saath kya hua, yahan likhein..."
            required
        ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>

    </div>

    <br>

    <button type="submit">
        Save Activity
    </button>

</form>

</body>

</html>

