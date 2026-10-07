
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
| Get Customer
|--------------------------------------------------------------------------
*/

if ($_SESSION['user_role'] === 'admin') {

    /*
    | Admin can edit any customer
    */

    $sql = "SELECT *
            FROM customers
            WHERE id = :id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id' => $id
    ]);

} else {

    /*
    | Staff can edit only assigned customer
    */

    $sql = "SELECT *
            FROM customers
            WHERE id = :id
            AND assigned_to = :user_id
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

    die("Access denied. You are not allowed to edit this customer.");
}

/*
|--------------------------------------------------------------------------
| Active Users
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id,
            name,
            email,
            role
        FROM users
        WHERE status = 1
        ORDER BY name ASC";

$stmt = $pdo->query($sql);

$users = $stmt->fetchAll();

$message = '';

/*
|--------------------------------------------------------------------------
| Update Customer
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $status = $_POST['status'] ?? 'lead';
    $source = trim($_POST['source'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $assignedTo = $_POST['assigned_to'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    $allowedStatuses = [
        'lead',
        'prospect',
        'customer',
        'inactive'
    ];

    if ($name === '') {

        $message = "Name is required.";

    } elseif (
        $email !== '' &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $message = "Please enter a valid email address.";

    } elseif (!in_array($status, $allowedStatuses, true)) {

        $message = "Invalid customer status.";

    } elseif (
        $assignedTo !== '' &&
        !is_numeric($assignedTo)
    ) {

        $message = "Invalid assigned user.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Staff Assignment Protection
        |--------------------------------------------------------------------------
        |
        | Staff kisi customer ko kisi doosre staff ke paas assign
        | nahi kar sakta.
        |
        | Admin kisi active user ko assign kar sakta hai.
        |--------------------------------------------------------------------------
        */

        if ($_SESSION['user_role'] === 'staff') {

            /*
            | Staff apne customer ko kisi aur user ko assign nahi
            | kar sakta.
            */

            if (
                $assignedTo !== '' &&
                (int) $assignedTo !== (int) $_SESSION['user_id']
            ) {

                $message = "Staff can only keep the customer assigned to themselves.";

            } elseif ($assignedTo === '') {

                /*
                | Staff apne assigned customer ko unassign bhi
                | nahi kar sakta.
                */

                $message = "Staff cannot unassign this customer.";

            }

        }

        /*
        |--------------------------------------------------------------------------
        | Validate Assigned User
        |--------------------------------------------------------------------------
        */

        if ($message === '' && $assignedTo !== '') {

            $sql = "SELECT id
                    FROM users
                    WHERE id = :id
                    AND status = 1
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':id' => $assignedTo
            ]);

            $assignedUser = $stmt->fetch();

            if (!$assignedUser) {

                $message = "Selected user is not active or does not exist.";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        if ($message === '') {

            $sql = "UPDATE customers
                    SET
                        name = :name,
                        email = :email,
                        phone = :phone,
                        company = :company,
                        status = :status,
                        source = :source,
                        assigned_to = :assigned_to,
                        notes = :notes
                    WHERE id = :id";

            /*
            | Extra protection:
            |
            | Staff ke case me UPDATE bhi sirf uske assigned
            | customer par chalega.
            */

            if ($_SESSION['user_role'] === 'staff') {

                $sql = "UPDATE customers
                        SET
                            name = :name,
                            email = :email,
                            phone = :phone,
                            company = :company,
                            status = :status,
                            source = :source,
                            assigned_to = :assigned_to,
                            notes = :notes
                        WHERE id = :id
                        AND assigned_to = :user_id";
            }

            $stmt = $pdo->prepare($sql);

            $params = [
                ':name' => $name,
                ':email' => $email !== '' ? $email : null,
                ':phone' => $phone !== '' ? $phone : null,
                ':company' => $company !== '' ? $company : null,
                ':status' => $status,
                ':source' => $source !== '' ? $source : null,
                ':assigned_to' => $assignedTo !== ''
                    ? $assignedTo
                    : null,
                ':notes' => $notes !== '' ? $notes : null,
                ':id' => $id
            ];

            if ($_SESSION['user_role'] === 'staff') {

                $params[':user_id'] = $_SESSION['user_id'];
            }

            $stmt->execute($params);

            header(
                "Location: customer-view.php?id=" .
                urlencode($id)
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

$formName = $_POST['name'] ?? $customer['name'];

$formEmail = $_POST['email']
    ?? ($customer['email'] ?? '');

$formPhone = $_POST['phone']
    ?? ($customer['phone'] ?? '');

$formCompany = $_POST['company']
    ?? ($customer['company'] ?? '');

$formStatus = $_POST['status']
    ?? $customer['status'];

$formSource = $_POST['source']
    ?? ($customer['source'] ?? '');

$formNotes = $_POST['notes']
    ?? ($customer['notes'] ?? '');

$formAssignedTo = $_POST['assigned_to']
    ?? ($customer['assigned_to'] ?? '');

?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>
        Edit Customer
    </title>

</head>

<body>

<h1>
    Edit Customer
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

    <a href="customer-view.php?id=<?php echo $customer['id']; ?>">
        View Customer
    </a>

</p>

<hr>

<?php if ($message): ?>

    <p>
        <?php echo htmlspecialchars($message); ?>
    </p>

<?php endif; ?>

<form method="POST">

    <!-- Name -->

    <div>

        <label>
            Name
        </label>

        <br>

        <input
            type="text"
            name="name"
            value="<?php echo htmlspecialchars($formName); ?>"
            required
        >

    </div>

    <br>

    <!-- Email -->

    <div>

        <label>
            Email
        </label>

        <br>

        <input
            type="email"
            name="email"
            value="<?php echo htmlspecialchars($formEmail); ?>"
        >

    </div>

    <br>

    <!-- Phone -->

    <div>

        <label>
            Phone
        </label>

        <br>

        <input
            type="text"
            name="phone"
            value="<?php echo htmlspecialchars($formPhone); ?>"
        >

    </div>

    <br>

    <!-- Company -->

    <div>

        <label>
            Company
        </label>

        <br>

        <input
            type="text"
            name="company"
            value="<?php echo htmlspecialchars($formCompany); ?>"
        >

    </div>

    <br>

    <!-- Status -->

    <div>

        <label>
            Status
        </label>

        <br>

        <select name="status">

            <option
                value="lead"
                <?php echo $formStatus === 'lead'
                    ? 'selected'
                    : ''; ?>
            >
                Lead
            </option>

            <option
                value="prospect"
                <?php echo $formStatus === 'prospect'
                    ? 'selected'
                    : ''; ?>
            >
                Prospect
            </option>

            <option
                value="customer"
                <?php echo $formStatus === 'customer'
                    ? 'selected'
                    : ''; ?>
            >
                Customer
            </option>

            <option
                value="inactive"
                <?php echo $formStatus === 'inactive'
                    ? 'selected'
                    : ''; ?>
            >
                Inactive
            </option>

        </select>

    </div>

    <br>

    <!-- Source -->

    <div>

        <label>
            Source
        </label>

        <br>

        <input
            type="text"
            name="source"
            value="<?php echo htmlspecialchars($formSource); ?>"
            placeholder="Website, Facebook, Referral..."
        >

    </div>

    <br>

    <!-- Assigned To -->

    <div>

        <label>
            Assigned To
        </label>

        <br>

        <select
            name="assigned_to"
            <?php
            echo $_SESSION['user_role'] === 'staff'
                ? 'disabled'
                : '';
            ?>
        >

            <option value="">
                -- Not Assigned --
            </option>

            <?php foreach ($users as $user): ?>

                <option
                    value="<?php echo htmlspecialchars($user['id']); ?>"
                    <?php
                    echo (
                        (string) $formAssignedTo ===
                        (string) $user['id']
                    )
                        ? 'selected'
                        : '';
                    ?>
                >

                    <?php echo htmlspecialchars($user['name']); ?>

                    -

                    <?php echo htmlspecialchars($user['role']); ?>

                </option>

            <?php endforeach; ?>

        </select>

        <?php if ($_SESSION['user_role'] === 'staff'): ?>

            <p>
                <small>
                    Staff customer assignment change nahi kar sakta.
                </small>
            </p>

            <!-- Disabled select POST nahi hota,
                 isliye hidden field zaroori hai. -->

            <input
                type="hidden"
                name="assigned_to"
                value="<?php echo htmlspecialchars($formAssignedTo); ?>"
            >

        <?php endif; ?>

    </div>

    <br>

    <!-- Notes -->

    <div>

        <label>
            Notes
        </label>

        <br>

        <textarea
            name="notes"
            rows="5"
            cols="50"
        ><?php echo htmlspecialchars($formNotes); ?></textarea>

    </div>

    <br>

    <button type="submit">
        Update Customer
    </button>

</form>

</body>

</html>

