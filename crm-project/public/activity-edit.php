
<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Activity ID
|--------------------------------------------------------------------------
*/

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("Invalid activity ID.");
}

/*
|--------------------------------------------------------------------------
| Get Activity
|--------------------------------------------------------------------------
*/

if ($_SESSION['user_role'] === 'admin') {

    /*
    | Admin can edit any activity
    */

    $sql = "SELECT
                activities.*,
                customers.name AS customer_name,
                customers.company
            FROM activities
            INNER JOIN customers
                ON activities.customer_id = customers.id
            WHERE activities.id = :id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id' => $id
    ]);

} else {

    /*
    | Staff can edit activity only for assigned customer
    */

    $sql = "SELECT
                activities.*,
                customers.name AS customer_name,
                customers.company
            FROM activities
            INNER JOIN customers
                ON activities.customer_id = customers.id
            WHERE activities.id = :id
            AND customers.assigned_to = :user_id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id' => $id,
        ':user_id' => $_SESSION['user_id']
    ]);
}

$activity = $stmt->fetch();

if (!$activity) {

    http_response_code(403);

    die("Access denied. You are not allowed to edit this activity.");
}

$message = '';

/*
|--------------------------------------------------------------------------
| Update Activity
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

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (!in_array($activityType, $allowedTypes, true)) {

        $message = "Invalid activity type.";

    } elseif ($description === '') {

        $message = "Description is required.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        if ($_SESSION['user_role'] === 'admin') {

            $sql = "UPDATE activities
                    SET
                        activity_type = :activity_type,
                        description = :description
                    WHERE id = :id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':activity_type' => $activityType,
                ':description' => $description,
                ':id' => $id
            ]);

        } else {

            /*
            | Staff ke liye extra access protection
            */

            $sql = "UPDATE activities
                    INNER JOIN customers
                        ON activities.customer_id = customers.id
                    SET
                        activities.activity_type = :activity_type,
                        activities.description = :description
                    WHERE activities.id = :id
                    AND customers.assigned_to = :user_id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':activity_type' => $activityType,
                ':description' => $description,
                ':id' => $id,
                ':user_id' => $_SESSION['user_id']
            ]);
        }

        header(
            "Location: customer-view.php?id=" .
            urlencode($activity['customer_id'])
        );

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Form Values
|--------------------------------------------------------------------------
*/

$formActivityType = $_POST['activity_type']
    ?? $activity['activity_type'];

$formDescription = $_POST['description']
    ?? ($activity['description'] ?? '');

?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>
        Edit Activity
    </title>

</head>

<body>

<h1>
    Edit Activity
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
        href="customer-view.php?id=<?php echo $activity['customer_id']; ?>"
    >
        Back to Customer
    </a>

</p>

<hr>

<h2>
    Customer:
    <?php echo htmlspecialchars($activity['customer_name']); ?>
</h2>

<?php if (!empty($activity['company'])): ?>

    <p>
        Company:
        <?php echo htmlspecialchars($activity['company']); ?>
    </p>

<?php endif; ?>

<?php if ($message): ?>

    <p>
        <?php echo htmlspecialchars($message); ?>
    </p>

<?php endif; ?>

<hr>

<form method="POST">

    <div>

        <label>
            Activity Type
        </label>

        <br>

        <select name="activity_type" required>

            <option
                value="call"
                <?php
                echo $formActivityType === 'call'
                    ? 'selected'
                    : '';
                ?>
            >
                Call
            </option>

            <option
                value="email"
                <?php
                echo $formActivityType === 'email'
                    ? 'selected'
                    : '';
                ?>
            >
                Email
            </option>

            <option
                value="meeting"
                <?php
                echo $formActivityType === 'meeting'
                    ? 'selected'
                    : '';
                ?>
            >
                Meeting
            </option>

            <option
                value="note"
                <?php
                echo $formActivityType === 'note'
                    ? 'selected'
                    : '';
                ?>
            >
                Note
            </option>

            <option
                value="other"
                <?php
                echo $formActivityType === 'other'
                    ? 'selected'
                    : '';
                ?>
            >
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
            required
        ><?php echo htmlspecialchars($formDescription); ?></textarea>

    </div>

    <br>

    <button type="submit">
        Update Activity
    </button>

</form>

</body>

</html>

