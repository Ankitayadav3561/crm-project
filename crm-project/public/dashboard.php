
<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| Page Title
|--------------------------------------------------------------------------
*/

$pageTitle = 'Dashboard';


/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

$userId = (int) $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$userName = $_SESSION['user_name'];


/*
|--------------------------------------------------------------------------
| Customer Statistics
|--------------------------------------------------------------------------
*/

if ($userRole === 'admin') {

    $sql = "SELECT
                COUNT(*) AS total,
                SUM(status = 'lead') AS lead,
                SUM(status = 'prospect') AS prospect,
                SUM(status = 'customer') AS customer,
                SUM(status = 'inactive') AS inactive
            FROM customers";

    $stmt = $pdo->query($sql);

} else {

    $sql = "SELECT
                COUNT(*) AS total,
                SUM(status = 'lead') AS lead,
                SUM(status = 'prospect') AS prospect,
                SUM(status = 'customer') AS customer,
                SUM(status = 'inactive') AS inactive
            FROM customers
            WHERE assigned_to = :user_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':user_id' => $userId
    ]);
}

$customerStats = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Make Sure All Customer Stats Have Values
|--------------------------------------------------------------------------
*/

$totalCustomers = (int) ($customerStats['total'] ?? 0);
$totalLeads = (int) ($customerStats['lead'] ?? 0);
$totalProspects = (int) ($customerStats['prospect'] ?? 0);
$totalActiveCustomers = (int) ($customerStats['customer'] ?? 0);
$totalInactive = (int) ($customerStats['inactive'] ?? 0);


/*
|--------------------------------------------------------------------------
| Follow-up Statistics
|--------------------------------------------------------------------------
*/

if ($userRole === 'admin') {

    $sql = "SELECT

                SUM(
                    DATE(follow_up_date) = CURDATE()
                    AND status = 'pending'
                ) AS today_count,

                SUM(
                    follow_up_date > NOW()
                    AND DATE(follow_up_date) != CURDATE()
                    AND status = 'pending'
                ) AS upcoming_count,

                SUM(
                    follow_up_date < NOW()
                    AND status = 'pending'
                ) AS overdue_count,

                SUM(
                    status = 'completed'
                ) AS completed_count

            FROM follow_ups";

    $stmt = $pdo->query($sql);

} else {

    $sql = "SELECT

                SUM(
                    DATE(follow_ups.follow_up_date) = CURDATE()
                    AND follow_ups.status = 'pending'
                ) AS today_count,

                SUM(
                    follow_ups.follow_up_date > NOW()
                    AND DATE(follow_ups.follow_up_date) != CURDATE()
                    AND follow_ups.status = 'pending'
                ) AS upcoming_count,

                SUM(
                    follow_ups.follow_up_date < NOW()
                    AND follow_ups.status = 'pending'
                ) AS overdue_count,

                SUM(
                    follow_ups.status = 'completed'
                ) AS completed_count

            FROM follow_ups

            INNER JOIN customers
                ON follow_ups.customer_id = customers.id

            WHERE customers.assigned_to = :user_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':user_id' => $userId
    ]);
}

$followUpStats = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Make Sure All Follow-up Stats Have Values
|--------------------------------------------------------------------------
*/

$todayFollowUpsCount = (int) (
    $followUpStats['today_count'] ?? 0
);

$upcomingFollowUpsCount = (int) (
    $followUpStats['upcoming_count'] ?? 0
);

$overdueFollowUpsCount = (int) (
    $followUpStats['overdue_count'] ?? 0
);

$completedFollowUpsCount = (int) (
    $followUpStats['completed_count'] ?? 0
);


/*
|--------------------------------------------------------------------------
| Today's Follow-ups
|--------------------------------------------------------------------------
*/

if ($userRole === 'admin') {

    $sql = "SELECT
                follow_ups.*,
                customers.name AS customer_name,
                customers.company

            FROM follow_ups

            INNER JOIN customers
                ON follow_ups.customer_id = customers.id

            WHERE DATE(follow_ups.follow_up_date) = CURDATE()

            AND follow_ups.status = 'pending'

            ORDER BY follow_ups.follow_up_date ASC

            LIMIT 10";

    $stmt = $pdo->query($sql);

} else {

    $sql = "SELECT
                follow_ups.*,
                customers.name AS customer_name,
                customers.company

            FROM follow_ups

            INNER JOIN customers
                ON follow_ups.customer_id = customers.id

            WHERE DATE(follow_ups.follow_up_date) = CURDATE()

            AND follow_ups.status = 'pending'

            AND customers.assigned_to = :user_id

            ORDER BY follow_ups.follow_up_date ASC

            LIMIT 10";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':user_id' => $userId
    ]);
}

$todayFollowUps = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Overdue Follow-ups
|--------------------------------------------------------------------------
*/

if ($userRole === 'admin') {

    $sql = "SELECT
                follow_ups.*,
                customers.name AS customer_name,
                customers.company

            FROM follow_ups

            INNER JOIN customers
                ON follow_ups.customer_id = customers.id

            WHERE follow_ups.follow_up_date < NOW()

            AND follow_ups.status = 'pending'

            ORDER BY follow_ups.follow_up_date ASC

            LIMIT 10";

    $stmt = $pdo->query($sql);

} else {

    $sql = "SELECT
                follow_ups.*,
                customers.name AS customer_name,
                customers.company

            FROM follow_ups

            INNER JOIN customers
                ON follow_ups.customer_id = customers.id

            WHERE follow_ups.follow_up_date < NOW()

            AND follow_ups.status = 'pending'

            AND customers.assigned_to = :user_id

            ORDER BY follow_ups.follow_up_date ASC

            LIMIT 10";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':user_id' => $userId
    ]);
}

$overdueFollowUps = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Recent Customers
|--------------------------------------------------------------------------
*/

if ($userRole === 'admin') {

    $sql = "SELECT
                id,
                name,
                company,
                status,
                created_at

            FROM customers

            ORDER BY id DESC

            LIMIT 5";

    $stmt = $pdo->query($sql);

} else {

    $sql = "SELECT
                id,
                name,
                company,
                status,
                created_at

            FROM customers

            WHERE assigned_to = :user_id

            ORDER BY id DESC

            LIMIT 5";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':user_id' => $userId
    ]);
}

$recentCustomers = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Common Header
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../app/views/header.php';

?>


<!--
|--------------------------------------------------------------------------
| Dashboard Welcome
|--------------------------------------------------------------------------
-->

<div class="page-header">

    <div>

        <h1>
            CRM Dashboard
        </h1>

        <p>
            Welcome back,
            <strong>
                <?php echo htmlspecialchars($userName); ?>
            </strong>
        </p>

    </div>


    <div>

        <span class="badge">

            <?php echo htmlspecialchars(
                ucfirst($userRole)
            ); ?>

        </span>

    </div>

</div>


<!--
|--------------------------------------------------------------------------
| Admin / Staff Information
|--------------------------------------------------------------------------
-->

<div class="alert">

    <?php if ($userRole === 'admin'): ?>

        <strong>
            Admin Dashboard
        </strong>

        — Aap poore CRM ka data dekh rahe hain.

    <?php else: ?>

        <strong>
            My Dashboard
        </strong>

        — Aapko sirf aapke assigned customers aur unke
        follow-ups dikh rahe hain.

    <?php endif; ?>

</div>


<!--
|--------------------------------------------------------------------------
| Customer Statistics
|--------------------------------------------------------------------------
-->

<section class="dashboard-section">

    <div class="section-heading">

        <div>

            <h2>
                Customer Overview
            </h2>

            <p>
                Customer pipeline ka quick summary
            </p>

        </div>

    </div>


    <div class="dashboard-card-grid">


        <!-- Total Customers -->

        <a
            href="customers.php"
            class="dashboard-card"
        >

            <div class="dashboard-card-icon icon-blue">
                👥
            </div>

            <div class="dashboard-card-content">

                <div class="dashboard-card-label">
                    Total Customers
                </div>

                <div class="dashboard-card-value">
                    <?php echo $totalCustomers; ?>
                </div>

            </div>

        </a>


        <!-- Leads -->

        <a
            href="customers.php?status=lead"
            class="dashboard-card"
        >

            <div class="dashboard-card-icon icon-orange">
                🎯
            </div>

            <div class="dashboard-card-content">

                <div class="dashboard-card-label">
                    Leads
                </div>

                <div class="dashboard-card-value">
                    <?php echo $totalLeads; ?>
                </div>

            </div>

        </a>


        <!-- Prospects -->

        <a
            href="customers.php?status=prospect"
            class="dashboard-card"
        >

            <div class="dashboard-card-icon icon-purple">
                🔎
            </div>

            <div class="dashboard-card-content">

                <div class="dashboard-card-label">
                    Prospects
                </div>

                <div class="dashboard-card-value">
                    <?php echo $totalProspects; ?>
                </div>

            </div>

        </a>


        <!-- Active Customers -->

        <a
            href="customers.php?status=customer"
            class="dashboard-card"
        >

            <div class="dashboard-card-icon icon-green">
                ✓
            </div>

            <div class="dashboard-card-content">

                <div class="dashboard-card-label">
                    Customers
                </div>

                <div class="dashboard-card-value">
                    <?php echo $totalActiveCustomers; ?>
                </div>

            </div>

        </a>


        <!-- Inactive -->

        <a
            href="customers.php?status=inactive"
            class="dashboard-card"
        >

            <div class="dashboard-card-icon icon-gray">
                ⏸
            </div>

            <div class="dashboard-card-content">

                <div class="dashboard-card-label">
                    Inactive
                </div>

                <div class="dashboard-card-value">
                    <?php echo $totalInactive; ?>
                </div>

            </div>

        </a>


    </div>

</section>


<!--
|--------------------------------------------------------------------------
| Follow-up Statistics
|--------------------------------------------------------------------------
-->

<section class="dashboard-section">

    <div class="section-heading">

        <div>

            <h2>
                Follow-up Overview
            </h2>

            <p>
                Follow-ups aur pending activities ka summary
            </p>

        </div>

    </div>


    <div class="dashboard-card-grid">


        <!-- Today's Follow-ups -->

        <a
            href="follow-ups.php?date_filter=today&status=pending"
            class="dashboard-card"
        >

            <div class="dashboard-card-icon icon-blue">
                📅
            </div>

            <div class="dashboard-card-content">

                <div class="dashboard-card-label">
                    Today's Follow-ups
                </div>

                <div class="dashboard-card-value">
                    <?php echo $todayFollowUpsCount; ?>
                </div>

            </div>

        </a>


        <!-- Upcoming -->

        <a
            href="follow-ups.php?date_filter=upcoming&status=pending"
            class="dashboard-card"
        >

            <div class="dashboard-card-icon icon-purple">
                🕐
            </div>

            <div class="dashboard-card-content">

                <div class="dashboard-card-label">
                    Upcoming
                </div>

                <div class="dashboard-card-value">
                    <?php echo $upcomingFollowUpsCount; ?>
                </div>

            </div>

        </a>


        <!-- Overdue -->

        <a
            href="follow-ups.php?date_filter=overdue&status=pending"
            class="dashboard-card"
        >

            <div class="dashboard-card-icon icon-red">
                ⚠
            </div>

            <div class="dashboard-card-content">

                <div class="dashboard-card-label">
                    Overdue
                </div>

                <div class="dashboard-card-value">
                    <?php echo $overdueFollowUpsCount; ?>
                </div>

            </div>

        </a>


        <!-- Completed -->

        <a
            href="follow-ups.php?status=completed"
            class="dashboard-card"
        >

            <div class="dashboard-card-icon icon-green">
                ✓
            </div>

            <div class="dashboard-card-content">

                <div class="dashboard-card-label">
                    Completed
                </div>

                <div class="dashboard-card-value">
                    <?php echo $completedFollowUpsCount; ?>
                </div>

            </div>

        </a>


    </div>

</section>


<!--
|--------------------------------------------------------------------------
| Today's Follow-ups
|--------------------------------------------------------------------------
-->

<div class="section">

    <div class="section-header">

        <div>

            <h2>
                Today's Follow-ups
            </h2>

            <p>
                Aaj ke pending follow-up tasks.
            </p>

        </div>


        <a
            href="follow-ups.php?date_filter=today&status=pending"
            class="btn"
        >
            View All
        </a>

    </div>


    <?php if (empty($todayFollowUps)): ?>

        <div class="empty-state">

            <strong>
                No follow-ups for today.
            </strong>

            <p>
                Aaj ke liye koi pending follow-up nahi hai.
            </p>

        </div>

    <?php else: ?>


        <div class="table-wrapper">

            <table class="data-table">

                <thead>

                    <tr>

                        <th>
                            Customer
                        </th>

                        <th>
                            Company
                        </th>

                        <th>
                            Date & Time
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php foreach ($todayFollowUps as $followUp): ?>

                        <tr>

                            <td>

                                <strong>
                                    <?php echo htmlspecialchars(
                                        $followUp['customer_name']
                                    ); ?>
                                </strong>

                            </td>


                            <td>

                                <?php if (!empty($followUp['company'])): ?>

                                    <?php echo htmlspecialchars(
                                        $followUp['company']
                                    ); ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php echo htmlspecialchars(
                                    $followUp['follow_up_date']
                                ); ?>

                            </td>


                            <td>

                                <span class="badge">

                                    <?php echo htmlspecialchars(
                                        ucfirst($followUp['type'])
                                    ); ?>

                                </span>

                            </td>


                            <td>

                                <a
                                    href="follow-up-edit.php?id=<?php echo urlencode($followUp['id']); ?>"
                                    class="btn btn-sm"
                                >
                                    Edit
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                </tbody>

            </table>

        </div>


    <?php endif; ?>

</div>


<!--
|--------------------------------------------------------------------------
| Overdue Follow-ups
|--------------------------------------------------------------------------
-->

<div class="section">

    <div class="section-header">

        <div>

            <h2>
                Overdue Follow-ups
            </h2>

            <p>
                Ye pending follow-ups apni due date cross kar chuke hain.
            </p>

        </div>


        <a
            href="follow-ups.php?date_filter=overdue&status=pending"
            class="btn btn-danger"
        >
            View Overdue
        </a>

    </div>


    <?php if (empty($overdueFollowUps)): ?>

        <div class="empty-state">

            <strong>
                No overdue follow-ups.
            </strong>

            <p>
                Currently koi overdue pending follow-up nahi hai.
            </p>

        </div>

    <?php else: ?>


        <div class="table-wrapper">

            <table class="data-table">

                <thead>

                    <tr>

                        <th>
                            Customer
                        </th>

                        <th>
                            Company
                        </th>

                        <th>
                            Due Date & Time
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php foreach ($overdueFollowUps as $followUp): ?>

                        <tr>

                            <td>

                                <strong>
                                    <?php echo htmlspecialchars(
                                        $followUp['customer_name']
                                    ); ?>
                                </strong>

                            </td>


                            <td>

                                <?php if (!empty($followUp['company'])): ?>

                                    <?php echo htmlspecialchars(
                                        $followUp['company']
                                    ); ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <span class="badge badge-inactive">

                                    <?php echo htmlspecialchars(
                                        $followUp['follow_up_date']
                                    ); ?>

                                </span>

                            </td>


                            <td>

                                <span class="badge">

                                    <?php echo htmlspecialchars(
                                        ucfirst($followUp['type'])
                                    ); ?>

                                </span>

                            </td>


                            <td>

                                <a
                                    href="follow-up-edit.php?id=<?php echo urlencode($followUp['id']); ?>"
                                    class="btn btn-sm"
                                >
                                    Edit
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                </tbody>

            </table>

        </div>


    <?php endif; ?>

</div>


<!--
|--------------------------------------------------------------------------
| Recent Customers
|--------------------------------------------------------------------------
-->

<div class="section">

    <div class="section-header">

        <div>

            <h2>
                Recent Customers
            </h2>

            <p>
                Recently added customers.
            </p>

        </div>


        <a
            href="customers.php"
            class="btn"
        >
            View All Customers
        </a>

    </div>


    <?php if (empty($recentCustomers)): ?>

        <div class="empty-state">

            <strong>
                No customers found.
            </strong>

            <p>
                Abhi tak koi customer add nahi hua hai.
            </p>

            <a
                href="customer-add.php"
                class="btn btn-primary"
            >
                + Add Customer
            </a>

        </div>

    <?php else: ?>


        <div class="table-wrapper">

            <table class="data-table">

                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Name
                        </th>

                        <th>
                            Company
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Created
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php foreach ($recentCustomers as $customer): ?>

                        <tr>


                            <td>

                                <?php echo htmlspecialchars(
                                    $customer['id']
                                ); ?>

                            </td>


                            <td>

                                <strong>

                                    <?php echo htmlspecialchars(
                                        $customer['name']
                                    ); ?>

                                </strong>

                            </td>


                            <td>

                                <?php if (!empty($customer['company'])): ?>

                                    <?php echo htmlspecialchars(
                                        $customer['company']
                                    ); ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>


                                <?php

                                $statusClass = 'badge';

                                switch ($customer['status']) {

                                    case 'lead':
                                        $statusClass .= ' badge-lead';
                                        break;

                                    case 'prospect':
                                        $statusClass .= ' badge-prospect';
                                        break;

                                    case 'customer':
                                        $statusClass .= ' badge-customer';
                                        break;

                                    case 'inactive':
                                        $statusClass .= ' badge-inactive';
                                        break;

                                    default:
                                        $statusClass .= ' badge-secondary';
                                }

                                ?>


                                <span class="<?php echo $statusClass; ?>">

                                    <?php echo htmlspecialchars(
                                        ucfirst($customer['status'])
                                    ); ?>

                                </span>


                            </td>


                            <td>

                                <?php echo htmlspecialchars(
                                    $customer['created_at']
                                ); ?>

                            </td>


                            <td>

                                <a
                                    href="customer-view.php?id=<?php echo urlencode($customer['id']); ?>"
                                    class="btn btn-sm"
                                >
                                    View
                                </a>

                            </td>


                        </tr>

                    <?php endforeach; ?>


                </tbody>

            </table>

        </div>


    <?php endif; ?>

</div>


<?php

/*
|--------------------------------------------------------------------------
| Common Footer
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../app/views/footer.php';

?>

