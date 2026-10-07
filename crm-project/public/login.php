<?php

session_start();

require_once __DIR__ . '/../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $error = 'Email and password are required.';

    } else {

        $sql = "SELECT *
                FROM users
                WHERE email = :email
                AND status = 1
                LIMIT 1";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            header("Location: dashboard.php");
            exit;

        } else {

            $error = 'Invalid email or password.';

        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>CRM Login</title>

</head>

<body>

<h1>CRM Login</h1>

<?php if ($error): ?>

    <p>
        <?php echo htmlspecialchars($error); ?>
    </p>

<?php endif; ?>


<form method="POST">

    <div>

        <label>Email</label>

        <br>

        <input
            type="email"
            name="email"
            required
        >

    </div>

    <br>

    <div>

        <label>Password</label>

        <br>

        <input
            type="password"
            name="password"
            required
        >

    </div>

    <br>

    <button type="submit">
        Login
    </button>

</form>

</body>

</html>