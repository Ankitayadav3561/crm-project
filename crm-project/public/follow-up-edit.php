
<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("Invalid follow-up ID.");
}

/*
|--------------------------------------------------------------------------
| Get Follow-up
|--------------------------------------------------------------------------
*/

if ($_SESSION['user_role'] === 'admin') {

    $sql = "SELECT *
            FROM follow_ups
            WHERE id = :id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id' => $id
    ]);

} else {

    $sql = "SELECT
                follow_ups.*
            FROM follow_ups
            INNER JOIN customers
                ON follow_ups.customer_id = customers.id
            WHERE follow_ups.id = :id
            AND customers.assigned_to = :user_id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id' => $id,
        ':user_id' => $_SESSION['user_id']
    ]);
}

$followUp = $stmt->fetch();

if (!$followUp) {

    http_response_code(403);

    die("Access denied. You are not allowed to edit this follow-up.");
}

/*
|--------------------------------------------------------------------------
| Customer List
|--------------------------------------------------------------------------
*/

if ($_SESSION['user_role'] === 'admin') {

    $sql = "SELECT
                id,
                name,
                company
            FROM customers
            ORDER BY name ASC";

    $stmt = $pdo->query($sql);

} else {

    $sql = "SELECT
                id,
                name,
                company
            FROM customers
            WHERE assigned_to = :user_id
            ORDER BY name ASC";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':user_id' => $_SESSION['user_id']
    ]);
}

$customers = $stmt->fetchAll();

$message = '';

/*
|--------------------------------------------------------------------------
| Update Follow-up
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $customerId = $_POST['customer_id'] ?? '';
    $followUpDate = $_POST['follow_up_date'] ?? '';
    $type = $_POST['type'] ?? 'call';
    $status = $_POST['status'] ?? 'pending';
    $remarks = trim($_POST['remarks'] ?? '');

    $allowedTypes = [
        'call',
        'email',
        'meeting',
        'other'
    ];

    $allowedStatuses = [
        'pending',
        'completed',
        'cancelled'
    ];

    if ($customerId === '' || !is_numeric($customerId)) {

        $message = "Please select a valid customer.";

    } elseif ($followUpDate === '') {

        $message = "Follow-up date and time are required.";

    } elseif (!in_array($type, $allowedTypes, true)) {

        $message = "Invalid follow-up type.";

    } elseif (!in_array($status, $allowedStatuses, true)) {

        $message = "Invalid follow-up status.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Verify Customer Access
        |--------------------------------------------------------------------------
        */

        if ($_SESSION['user_role'] === 'admin') {

            $sql = "SELECT id
                    FROM customers
                    WHERE id = :customer_id
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':customer_id' => $customerId
            ]);

        } else {

            $sql = "SELECT id
                    FROM customers
                    WHERE id = :customer_id
                    AND assigned_to = :user_id
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':customer_id' => $customerId,
                ':user_id' => $_SESSION['user_id']
            ]);
        }

        $customer = $stmt->fetch();

        if (!$customer) {

            $message = "Access denied. You cannot use this customer.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Update
            |--------------------------------------------------------------------------
            */

            if ($_SESSION['user_role'] === 'admin') {

                $sql = "UPDATE follow_ups
                        SET
                            customer_id = :customer_id,
                            follow_up_date = :follow_up_date,
                            type = :type,
                            status = :status,
                            remarks = :remarks
                        WHERE id = :id";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':customer_id' => $customerId,
                    ':follow_up_date' => $followUpDate,
                    ':type' => $type,
                    ':status' => $status,
                    ':remarks' => $remarks !== ''
                        ? $remarks
                        : null,
                    ':id' => $id
                ]);

            } else {

                /*
                | Staff ke liye extra security
                */

                $sql = "UPDATE follow_ups
                        SET
                            customer_id = :customer_id,
                            follow_up_date = :follow_up_date,
                            type = :type,
                            status = :status,
                            remarks = :remarks
                        WHERE id = :id
                        AND customer_id = :old_customer_id";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':customer_id' => $customerId,
                    ':follow_up_date' => $followUpDate,
                    ':type' => $type,
                    ':status' => $status,
                    ':remarks' => $remarks !== ''
                        ? $remarks
                        : null,
                    ':id' => $id,
                    ':old_customer_id' => $followUp['customer_id']
                ]);
            }

            header(
                "Location: customer-view.php?id=" .
                urlencode($customerId)
            );

            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Form Values
|--------------------------------------------------------------------------
*/

$formCustomerId = $_POST['customer_id']
    ?? $followUp['customer_id'];

$formFollowUpDate = $_POST['follow_up_date']
    ?? date(
        'Y-m-d\TH:i',
        strtotime($followUp['follow_up_date'])
    );

$formType = $_POST['type']
    ?? $followUp['type'];

$formStatus = $_POST['status']
    ?? $followUp['status'];

$formRemarks = $_POST['remarks']
    ?? ($followUp['remarks'] ?? '');

?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>
        Edit Follow-up
    </title>

</head>

<body>

<h1>
    Edit Follow-up
</h1>

<p>

    <a href="dashboard.php">
        Dashboard
    </a>

    |

    <a href="follow-ups.php">
        Follow-ups
    </a>

    |

    <a href="customer-view.php?id=<?php echo $followUp['customer_id']; ?>">
        Back to Customer
    </a>

</p>

<hr>

<?php if ($message): ?>

    <p>
        <?php echo htmlspecialchars($message); ?>
    </p>

<?php endif; ?>

<form method="POST">

    <div>

        <label>
            Customer
        </label>

        <br>

        <select name="customer_id" required>

            <option value="">
                Select Customer
            </option>

            <?php foreach ($customers as $customer): ?>

                <option
                    value="<?php echo htmlspecialchars($customer['id']); ?>"
                    <?php
                    echo (
                        (string) $formCustomerId ===
                        (string) $customer['id']
                    )
                        ? 'selected'
                        : '';
                    ?>
                >

                    <?php echo htmlspecialchars($customer['name']); ?>

                    <?php if (!empty($customer['company'])): ?>

                        -
                        <?php echo htmlspecialchars($customer['company']); ?>

                    <?php endif; ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>

    <br>

    <div>

        <label>
            Follow-up Date & Time
        </label>

        <br>

        <input
            type="datetime-local"
            name="follow_up_date"
            value="<?php echo htmlspecialchars($formFollowUpDate); ?>"
            required
        >

    </div>

    <br>

    <div>

        <label>
            Type
        </label>

        <br>

        <select name="type">

            <option
                value="call"
                <?php echo $formType === 'call'
                    ? 'selected'
                    : ''; ?>
            >
                Call
            </option>

            <option
                value="email"
                <?php echo $formType === 'email'
                    ? 'selected'
                    : ''; ?>
            >
                Email
            </option>

            <option
                value="meeting"
                <?php echo $formType === 'meeting'
                    ? 'selected'
                    : ''; ?>
            >
                Meeting
            </option>

            <option
                value="other"
                <?php echo $formType === 'other'
                    ? 'selected'
                    : ''; ?>
            >
                Other
            </option>

        </select>

    </div>

    <br>

    <div>

        <label>
            Status
        </label>

        <br>

        <select name="status">

            <option
                value="pending"
                <?php echo $formStatus === 'pending'
                    ? 'selected'
                    : ''; ?>
            >
                Pending
            </option>

            <option
                value="completed"
                <?php echo $formStatus === 'completed'
                    ? 'selected'
                    : ''; ?>
            >
                Completed
            </option>

            <option
                value="cancelled"
                <?php echo $formStatus === 'cancelled'
                    ? 'selected'
                    : ''; ?>
            >
                Cancelled
            </option>

        </select>

    </div>

    <br>

    <div>

        <label>
            Remarks
        </label>

        <br>

        <textarea
            name="remarks"
            rows="5"
            cols="50"
        ><?php echo htmlspecialchars($formRemarks); ?></textarea>

    </div>

    <br>

    <button type="submit">
        Update Follow-up
    </button>

</form>

</body>

</html>

