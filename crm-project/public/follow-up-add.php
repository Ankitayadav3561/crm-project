
<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../config/database.php';

$message = '';

/*
|--------------------------------------------------------------------------
| Customer List
|--------------------------------------------------------------------------
*/

if ($_SESSION['user_role'] === 'admin') {

    /*
    | Admin ko saare customers dikhेंगे
    */

    $sql = "SELECT
                id,
                name,
                company
            FROM customers
            ORDER BY name ASC";

    $stmt = $pdo->query($sql);

} else {

    /*
    | Staff ko sirf assigned customers dikhेंगे
    */

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

/*
|--------------------------------------------------------------------------
| Add Follow-up
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $customerId = $_POST['customer_id'] ?? '';
    $followUpDate = $_POST['follow_up_date'] ?? '';
    $type = $_POST['type'] ?? 'call';
    $remarks = trim($_POST['remarks'] ?? '');

    $allowedTypes = [
        'call',
        'email',
        'meeting',
        'other'
    ];

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($customerId === '' || !is_numeric($customerId)) {

        $message = "Please select a valid customer.";

    } elseif ($followUpDate === '') {

        $message = "Follow-up date and time are required.";

    } elseif (!in_array($type, $allowedTypes, true)) {

        $message = "Invalid follow-up type.";

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

            $message = "Access denied. You cannot add a follow-up for this customer.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Insert Follow-up
            |--------------------------------------------------------------------------
            */

            $sql = "INSERT INTO follow_ups
                    (
                        customer_id,
                        user_id,
                        follow_up_date,
                        type,
                        status,
                        remarks
                    )
                    VALUES
                    (
                        :customer_id,
                        :user_id,
                        :follow_up_date,
                        :type,
                        'pending',
                        :remarks
                    )";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':customer_id' => $customerId,
                ':user_id' => $_SESSION['user_id'],
                ':follow_up_date' => $followUpDate,
                ':type' => $type,
                ':remarks' => $remarks !== ''
                    ? $remarks
                    : null
            ]);

            header(
                "Location: customer-view.php?id=" .
                urlencode($customerId)
            );

            exit;
        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>
        Add Follow-up
    </title>

</head>

<body>

<h1>
    Add Follow-up
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

    <a href="customers.php">
        Customers
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
                        ($_POST['customer_id'] ?? '') ==
                        $customer['id']
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
            value="<?php echo htmlspecialchars($_POST['follow_up_date'] ?? ''); ?>"
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
                <?php echo ($_POST['type'] ?? 'call') === 'call'
                    ? 'selected'
                    : ''; ?>
            >
                Call
            </option>

            <option
                value="email"
                <?php echo ($_POST['type'] ?? '') === 'email'
                    ? 'selected'
                    : ''; ?>
            >
                Email
            </option>

            <option
                value="meeting"
                <?php echo ($_POST['type'] ?? '') === 'meeting'
                    ? 'selected'
                    : ''; ?>
            >
                Meeting
            </option>

            <option
                value="other"
                <?php echo ($_POST['type'] ?? '') === 'other'
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
            Remarks
        </label>

        <br>

        <textarea
            name="remarks"
            rows="5"
            cols="50"
            placeholder="Follow-up ke baare mein details..."
        ><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>

    </div>

    <br>

    <button type="submit">
        Save Follow-up
    </button>

</form>

</body>

</html>

