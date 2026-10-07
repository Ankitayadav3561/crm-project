
<?php

$pageTitle = $pageTitle ?? 'CRM';

$currentPage = basename($_SERVER['PHP_SELF']);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <title>
        <?php echo htmlspecialchars($pageTitle); ?>
    </title>

</head>


<body>


<div class="app-layout">


    <!--
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    -->

    <aside class="sidebar">


        <div class="sidebar-logo">

            <a href="dashboard.php">
                CRM
            </a>

            <span>
                Management System
            </span>

        </div>


        <!--
        |--------------------------------------------------------------------------
        | User Information
        |--------------------------------------------------------------------------
        -->

        <div class="sidebar-user">

            <div class="sidebar-user-name">

                <?php echo htmlspecialchars(
                    $_SESSION['user_name']
                ); ?>

            </div>

            <div class="sidebar-user-role">

                <?php echo htmlspecialchars(
                    ucfirst($_SESSION['user_role'])
                ); ?>

            </div>

        </div>


        <!--
        |--------------------------------------------------------------------------
        | Navigation
        |--------------------------------------------------------------------------
        -->

        <nav class="sidebar-nav">


            <div class="nav-section-title">
                MAIN
            </div>


            <!-- Dashboard -->

            <a
                href="dashboard.php"
                class="sidebar-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>"
            >

                <span class="sidebar-icon">
                    ⌂
                </span>

                <span>
                    Dashboard
                </span>

            </a>


            <!-- Customers -->

            <a
                href="customers.php"
                class="sidebar-link <?php echo in_array(
                    $currentPage,
                    [
                        'customers.php',
                        'customer-add.php',
                        'customer-edit.php',
                        'customer-view.php'
                    ],
                    true
                ) ? 'active' : ''; ?>"
            >

                <span class="sidebar-icon">
                    👥
                </span>

                <span>
                    Customers
                </span>

            </a>


            <!-- Add Customer -->

            <a
                href="customer-add.php"
                class="sidebar-link <?php echo $currentPage === 'customer-add.php' ? 'active' : ''; ?>"
            >

                <span class="sidebar-icon">
                    +
                </span>

                <span>
                    Add Customer
                </span>

            </a>


            <!-- Follow-ups -->

            <a
                href="follow-ups.php"
                class="sidebar-link <?php echo in_array(
                    $currentPage,
                    [
                        'follow-ups.php',
                        'follow-up-add.php',
                        'follow-up-edit.php'
                    ],
                    true
                ) ? 'active' : ''; ?>"
            >

                <span class="sidebar-icon">
                    ✓
                </span>

                <span>
                    Follow-ups
                </span>

            </a>


            <!-- Add Follow-up -->

            <a
                href="follow-up-add.php"
                class="sidebar-link <?php echo $currentPage === 'follow-up-add.php' ? 'active' : ''; ?>"
            >

                <span class="sidebar-icon">
                    +
                </span>

                <span>
                    Add Follow-up
                </span>

            </a>


            <?php if ($_SESSION['user_role'] === 'admin'): ?>


                <div class="nav-section-title">
                    ADMINISTRATION
                </div>


                <!-- Users -->

                <a
                    href="users.php"
                    class="sidebar-link <?php echo in_array(
                        $currentPage,
                        [
                            'users.php',
                            'user-add.php',
                            'user-edit.php'
                        ],
                        true
                    ) ? 'active' : ''; ?>"
                >

                    <span class="sidebar-icon">
                        ⚙
                    </span>

                    <span>
                        Users
                    </span>

                </a>


            <?php endif; ?>


        </nav>


        <!--
        |--------------------------------------------------------------------------
        | Sidebar Bottom
        |--------------------------------------------------------------------------
        -->

        <div class="sidebar-bottom">

            <a
                href="logout.php"
                class="sidebar-link logout-link"
            >

                <span class="sidebar-icon">
                    ⇥
                </span>

                <span>
                    Logout
                </span>

            </a>

        </div>


    </aside>


    <!--
    |--------------------------------------------------------------------------
    | Main Area
    |--------------------------------------------------------------------------
    -->

    <div class="main-area">


        <!-- Top Header -->

        <header class="topbar">


            <div>

                <button
                    type="button"
                    class="mobile-menu-button"
                    onclick="document.body.classList.toggle('sidebar-open')"
                >
                    ☰
                </button>

            </div>


            <div class="topbar-user">

                <span>

                    <?php echo htmlspecialchars(
                        $_SESSION['user_name']
                    ); ?>

                </span>

                <span class="badge">

                    <?php echo htmlspecialchars(
                        ucfirst($_SESSION['user_role'])
                    ); ?>

                </span>

            </div>


        </header>


        <!-- Main Content -->

        <main class="container">
