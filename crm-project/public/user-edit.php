
<?php

require_once __DIR__ . '/../app/auth.php';

requireAdmin();

require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("Invalid user ID.");
}

/*
|--------------------------------------------------------------------------
| Get User
|--------------------------------------------------------------------------
*/

$sql = "SELECT *
        FROM users
        WHERE id = :id
        LIMIT 1";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id
]);

$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

$message = '';

/*
|--------------------------------------------------------------------------
| Update User
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'staff';
    $status = isset($_POST['status']) ? 1 : 0;

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $message = "Name is required.";

    } elseif ($email === '') {

        $message = "Email is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } elseif (!in_array($role, ['admin', 'staff'], true)) {

        $message = "Invalid role.";

    } elseif ($password !== '' && strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Check Duplicate Email
        |--------------------------------------------------------------------------
        */

        $sql = "SELECT id
                FROM users
                WHERE email = :email
                AND id != :id
                LIMIT 1";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':email' => $email,
            ':id' => $id
        ]);

        $existingUser = $stmt->fetch();

        if ($existingUser) {

            $message = "This email is already registered.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Update With / Without Password
            |--------------------------------------------------------------------------
            */

            if ($password !== '') {

                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $sql = "UPDATE users
                        SET
                            name = :name,
                            email = :email,
                            password = :password,
                            role = :role,
                            status = :status
                        WHERE id = :id";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $hashedPassword,
                    ':role' => $role,
                    ':status' => $status,
                    ':id' => $id
                ]);

            } else {

                $sql = "UPDATE users
                        SET
                            name = :name,
                            email = :email,
                            role = :role,
                            status = :status
                        WHERE id = :id";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':role' => $role,
                    ':status' => $status,
                    ':id' => $id
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | If Current Logged-in User Is Being Edited
            |--------------------------------------------------------------------------
            */

            if ((int) $_SESSION['user_id'] === (int) $id) {

                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $role;

            }

            header("Location: users.php");

            exit;
        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>Edit User</title>

</head>

<body>

<h1>Edit User</h1>

<p>

    <a href="dashboard.php">
        Dashboard
    </a>

    |

    <a href="users.php">
        Users
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
            Name
        </label>

        <br>

        <input
            type="text"
            name="name"
            value="<?php echo htmlspecialchars($user['name']); ?>"
            required
        >

    </div>

    <br>

    <div>

        <label>
            Email
        </label>

        <br>

        <input
            type="email"
            name="email"
            value="<?php echo htmlspecialchars($user['email']); ?>"
            required
        >

    </div>

    <br>

    <div>

        <label>
            New Password
        </label>

        <br>

        <input
            type="password"
            name="password"
        >

        <br>

        <small>
            Password change nahi karna hai to blank chhod dein.
        </small>

    </div>

    <br>

    <div>

        <label>
            Role
        </label>

        <br>

        <select name="role">

            <option
                value="staff"
                <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>
            >
                Staff
            </option>

            <option
                value="admin"
                <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>
            >
                Admin
            </option>

        </select>

    </div>

    <br>

    <div>

        <label>

            <input
                type="checkbox"
                name="status"
                value="1"
                <?php echo $user['status'] == 1 ? 'checked' : ''; ?>
            >

            Active User

        </label>

    </div>

    <br>

    <button type="submit">
        Update User
    </button>

</form>

</body>

</html>

