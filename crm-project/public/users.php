
<?php

require_once __DIR__ . '/../app/auth.php';

requireAdmin();

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Get All Users
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id,
            name,
            email,
            role,
            status,
            created_at
        FROM users
        ORDER BY id DESC";

$stmt = $pdo->query($sql);

$users = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>Users</title>

</head>

<body>

<h1>Users Management</h1>

<p>

    <a href="dashboard.php">
        Dashboard
    </a>

    |

    <a href="users.php">
        Users
    </a>

    |

    <a href="user-add.php">
        + Add User
    </a>

</p>

<hr>

<table border="1" cellpadding="10" cellspacing="0">

    <thead>

        <tr>

            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Action</th>

        </tr>

    </thead>

    <tbody>

        <?php if (empty($users)): ?>

            <tr>

                <td colspan="7">
                    No users found.
                </td>

            </tr>

        <?php else: ?>

            <?php foreach ($users as $user): ?>

                <tr>

                    <td>
                        <?php
                        echo htmlspecialchars($user['id']);
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars($user['name']);
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars($user['email']);
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars($user['role']);
                        ?>
                    </td>

                    <td>

                        <?php if ($user['status'] == 1): ?>

                            Active

                        <?php else: ?>

                            Inactive

                        <?php endif; ?>

                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars($user['created_at']);
                        ?>
                    </td>

                    <td>

                        <a
                            href="user-edit.php?id=<?php echo $user['id']; ?>"
                        >
                            Edit
                        </a>

                    </td>

                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

    </tbody>

</table>

</body>

</html>

