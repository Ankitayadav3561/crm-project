
<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Customer ID
|--------------------------------------------------------------------------
*/

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("Invalid customer ID.");
}

/*
|--------------------------------------------------------------------------
| Customer Details
|--------------------------------------------------------------------------
*/

if ($_SESSION['user_role'] === 'admin') {

    /*
    | Admin can view any customer
    */

    $sql = "SELECT
                customers.*,
                users.name AS assigned_user_name
            FROM customers
            LEFT JOIN users
                ON customers.assigned_to = users.id
            WHERE customers.id = :id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id' => $id
    ]);

} else {

    /*
    | Staff can view only assigned customer
    */

    $sql = "SELECT
                customers.*,
                users.name AS assigned_user_name
            FROM customers
            LEFT JOIN users
                ON customers.assigned_to = users.id
            WHERE customers.id = :id
            AND customers.assigned_to = :user_id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id' => $id,
        ':user_id' => $_SESSION['user_id']
    ]);
}

$customer = $stmt->fetch();

if (!$customer) {

    http_response_code(403);

    die("Access denied. You are not allowed to view this customer.");
}

/*
|--------------------------------------------------------------------------
| Customer Follow-ups
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            follow_ups.*,
            users.name AS user_name
        FROM follow_ups
        LEFT JOIN users
            ON follow_ups.user_id = users.id
        WHERE follow_ups.customer_id = :customer_id
        ORDER BY follow_ups.follow_up_date DESC";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':customer_id' => $id
]);

$followUps = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Customer Activities
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            activities.*,
            users.name AS user_name
        FROM activities
        LEFT JOIN users
            ON activities.user_id = users.id
        WHERE activities.customer_id = :customer_id
        ORDER BY activities.created_at DESC";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':customer_id' => $id
]);

$activities = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>
        Customer Details -
        <?php echo htmlspecialchars($customer['name']); ?>
    </title>

</head>

<body>

<h1>
    Customer Details
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

    <a href="customer-edit.php?id=<?php echo $customer['id']; ?>">
        Edit Customer
    </a>

</p>

<hr>

<h2>
    Customer Information
</h2>

<table border="1" cellpadding="8" cellspacing="0">

    <tr>

        <th>
            ID
        </th>

        <td>
            <?php echo htmlspecialchars($customer['id']); ?>
        </td>

    </tr>

    <tr>

        <th>
            Name
        </th>

        <td>
            <?php echo htmlspecialchars($customer['name']); ?>
        </td>

    </tr>

    <tr>

        <th>
            Email
        </th>

        <td>
            <?php echo htmlspecialchars($customer['email'] ?? ''); ?>
        </td>

    </tr>

    <tr>

        <th>
            Phone
        </th>

        <td>
            <?php echo htmlspecialchars($customer['phone'] ?? ''); ?>
        </td>

    </tr>

    <tr>

        <th>
            Company
        </th>

        <td>
            <?php echo htmlspecialchars($customer['company'] ?? ''); ?>
        </td>

    </tr>

    <tr>

        <th>
            Status
        </th>

        <td>
            <?php echo htmlspecialchars($customer['status']); ?>
        </td>

    </tr>

    <tr>

        <th>
            Source
        </th>

        <td>
            <?php echo htmlspecialchars($customer['source'] ?? ''); ?>
        </td>

    </tr>

    <tr>

        <th>
            Assigned To
        </th>

        <td>

            <?php

            echo htmlspecialchars(
                $customer['assigned_user_name']
                ?? 'Not Assigned'
            );

            ?>

        </td>

    </tr>

    <tr>

        <th>
            Notes
        </th>

        <td>

            <?php

            echo nl2br(
                htmlspecialchars(
                    $customer['notes'] ?? ''
                )
            );

            ?>

        </td>

    </tr>

    <tr>

        <th>
            Created At
        </th>

        <td>
            <?php echo htmlspecialchars($customer['created_at']); ?>
        </td>

    </tr>

    <tr>

        <th>
            Updated At
        </th>

        <td>
            <?php echo htmlspecialchars($customer['updated_at']); ?>
        </td>

    </tr>

</table>

<br>

<a href="follow-up-add.php">

    Add Follow-up

</a>

<br><br>

<a
    href="activity-add.php?customer_id=<?php echo $customer['id']; ?>"
>

    Add Activity

</a>

<hr>

<h2>
    Follow-ups
</h2>

<?php if (empty($followUps)): ?>

    <p>
        No follow-ups found for this customer.
    </p>

<?php else: ?>

    <table
        border="1"
        cellpadding="8"
        cellspacing="0"
    >

        <thead>

            <tr>

                <th>
                    ID
                </th>

                <th>
                    Date & Time
                </th>

                <th>
                    Type
                </th>

                <th>
                    Status
                </th>

                <th>
                    Assigned To
                </th>

                <th>
                    Remarks
                </th>

                <th>
                    Action
                </th>

            </tr>

        </thead>

        <tbody>

            <?php foreach ($followUps as $followUp): ?>

                <tr>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $followUp['id']
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $followUp['follow_up_date']
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $followUp['type']
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $followUp['status']
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $followUp['user_name']
                            ?? 'Unknown'
                        );
                        ?>
                    </td>

                    <td>

                        <?php

                        echo nl2br(
                            htmlspecialchars(
                                $followUp['remarks'] ?? ''
                            )
                        );

                        ?>

                    </td>

                    <td>

                        <a
                            href="follow-up-edit.php?id=<?php echo $followUp['id']; ?>"
                        >
                            Edit
                        </a>

                        <?php if ($_SESSION['user_role'] === 'admin'): ?>

                            &nbsp; | &nbsp;

                            <form
                                method="POST"
                                action="follow-up-delete.php"
                                style="display:inline;"
                                onsubmit="return confirm('Are you sure you want to delete this follow-up?');"
                            >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?php echo $followUp['id']; ?>"
                                >

                                <button type="submit">
                                    Delete
                                </button>

                            </form>

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

<?php endif; ?>

<hr>

<h2>
    Activity History
</h2>

<?php if (empty($activities)): ?>

    <p>
        No activity history found.
    </p>

<?php else: ?>

    <table
        border="1"
        cellpadding="8"
        cellspacing="0"
    >

        <thead>

            <tr>

                <th>
                    ID
                </th>

                <th>
                    Type
                </th>

                <th>
                    Description
                </th>

                <th>
                    User
                </th>

                <th>
                    Date
                </th>

                <th>
                    Action
                </th>

            </tr>

        </thead>

        <tbody>

            <?php foreach ($activities as $activity): ?>

                <tr>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $activity['id']
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $activity['activity_type']
                        );
                        ?>
                    </td>

                    <td>

                        <?php

                        echo nl2br(
                            htmlspecialchars(
                                $activity['description'] ?? ''
                            )
                        );

                        ?>

                    </td>

                    <td>

                        <?php

                        echo htmlspecialchars(
                            $activity['user_name']
                            ?? 'Unknown'
                        );

                        ?>

                    </td>

                    <td>

                        <?php

                        echo htmlspecialchars(
                            $activity['created_at']
                        );

                        ?>

                    </td>

                    <td>

                        <a
                            href="activity-edit.php?id=<?php echo $activity['id']; ?>"
                        >
                            Edit
                        </a>

                        <?php if ($_SESSION['user_role'] === 'admin'): ?>

                            &nbsp; | &nbsp;

                            <form
                                method="POST"
                                action="activity-delete.php"
                                style="display:inline;"
                                onsubmit="return confirm('Are you sure you want to delete this activity?');"
                            >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?php echo $activity['id']; ?>"
                                >

                                <button type="submit">
                                    Delete
                                </button>

                            </form>

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

<?php endif; ?>

</body>

</html>

