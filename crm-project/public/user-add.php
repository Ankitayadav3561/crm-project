
<?php

require_once __DIR__ . '/../app/auth.php';

requireAdmin();

require_once __DIR__ . '/../config/database.php';

$message = '';

/*
|--------------------------------------------------------------------------
| Add User
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

    } elseif ($password === '') {

        $message = "Password is required.";

    } elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";

    } elseif (!in_array($role, ['admin', 'staff'], true)) {

        $message = "Invalid role.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Check Duplicate Email
        |--------------------------------------------------------------------------
        */

        $sql = "SELECT id
                FROM users
                WHERE email = :email
                LIMIT 1";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);

        $existingUser = $stmt->fetch();

        if ($existingUser) {

            $message = "This email is already registered.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Hash Password
            |--------------------------------------------------------------------------
            */

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            /*
            |--------------------------------------------------------------------------
            | Insert User
            |--------------------------------------------------------------------------
            */

            $sql = "INSERT INTO users
                    (
                        name,
                        email,
                        password,
                        role,
                        status
                    )
                    VALUES
                    (
                        :name,
                        :email,
                        :password,
                        :role,
                        :status
                    )";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':role' => $role,
                ':status' => $status
            ]);

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

    <title>Add User</title>

</head>

<body>

<h1>Add User</h1>

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
            value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
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
            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
            required
        >

    </div>

    <br>

    <div>

        <label>
            Password
        </label>

        <br>

        <input
            type="password"
            name="password"
            required
        >

        <br>

        <small>
            Minimum 6 characters
        </small>

    </div>

    <br>

    <div>

        <label>
            Role
        </label>

        <br>

        <select name="role">

            <option value="staff">
                Staff
            </option>

            <option value="admin">
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
                checked
            >

            Active User

        </label>

    </div>

    <br>

    <button type="submit">
        Create User
    </button>

</form>

</body>

</html>
