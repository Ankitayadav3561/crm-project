<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| Search & Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$type = $_GET['type'] ?? '';

$status = $_GET['status'] ?? '';

$dateFilter = $_GET['date_filter'] ?? '';

$assignedTo = $_GET['assigned_to'] ?? '';

$page = $_GET['page'] ?? 1;

if (!is_numeric($page) || (int) $page < 1) {
    $page = 1;
}

$page = (int) $page;

$perPage = 10;

$offset = ($page - 1) * $perPage;


/*
|--------------------------------------------------------------------------
| Allowed Filters
|--------------------------------------------------------------------------
*/

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

$allowedDateFilters = [
    'today',
    'upcoming',
    'overdue'
];

if (!in_array($type, $allowedTypes, true)) {
    $type = '';
}

if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

if (!in_array($dateFilter, $allowedDateFilters, true)) {
    $dateFilter = '';
}


/*
|--------------------------------------------------------------------------
| Admin - Active Users
|--------------------------------------------------------------------------
*/

$users = [];

if ($_SESSION['user_role'] === 'admin') {

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
}


/*
|--------------------------------------------------------------------------
| Build WHERE Conditions
|--------------------------------------------------------------------------
*/

$where = [];

$params = [];


/*
|--------------------------------------------------------------------------
| Staff Restriction
|--------------------------------------------------------------------------
*/

if ($_SESSION['user_role'] === 'staff') {

    $where[] = "customers.assigned_to = :user_id";

    $params[':user_id'] = $_SESSION['user_id'];
}


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $where[] = "(
        customers.name LIKE :search
        OR customers.company LIKE :search
        OR follow_ups.remarks LIKE :search
    )";

    $params[':search'] = '%' . $search . '%';
}


/*
|--------------------------------------------------------------------------
| Type Filter
|--------------------------------------------------------------------------
*/

if ($type !== '') {

    $where[] = "follow_ups.type = :type";

    $params[':type'] = $type;
}


/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

if ($status !== '') {

    $where[] = "follow_ups.status = :status";

    $params[':status'] = $status;
}


/*
|--------------------------------------------------------------------------
| Date Filter
|--------------------------------------------------------------------------
*/

if ($dateFilter === 'today') {

    $where[] = "
        DATE(follow_ups.follow_up_date) = CURDATE()
    ";

} elseif ($dateFilter === 'upcoming') {

    $where[] = "
        follow_ups.follow_up_date > NOW()
        AND DATE(follow_ups.follow_up_date) != CURDATE()
    ";

} elseif ($dateFilter === 'overdue') {

    $where[] = "
        follow_ups.follow_up_date < NOW()
        AND follow_ups.status = 'pending'
    ";
}


/*
|--------------------------------------------------------------------------
| Customer Assigned User Filter
|--------------------------------------------------------------------------
*/

if (
    $_SESSION['user_role'] === 'admin' &&
    $assignedTo !== ''
) {

    if ($assignedTo === '0') {

        $where[] = "customers.assigned_to IS NULL";

    } elseif (is_numeric($assignedTo)) {

        $where[] = "customers.assigned_to = :assigned_to";

        $params[':assigned_to'] = $assignedTo;
    }
}


/*
|--------------------------------------------------------------------------
| WHERE SQL
|--------------------------------------------------------------------------
*/

$whereSql = '';

if (!empty($where)) {

    $whereSql = 'WHERE ' . implode(
        ' AND ',
        $where
    );
}


/*
|--------------------------------------------------------------------------
| Total Follow-up Count
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*)
        FROM follow_ups
        INNER JOIN customers
            ON follow_ups.customer_id = customers.id
        $whereSql";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$totalFollowUps = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Total Pages
|--------------------------------------------------------------------------
*/

$totalPages = max(
    1,
    (int) ceil($totalFollowUps / $perPage)
);


/*
|--------------------------------------------------------------------------
| Page Correction
|--------------------------------------------------------------------------
*/

if ($page > $totalPages) {

    $page = $totalPages;

    $offset = ($page - 1) * $perPage;
}


/*
|--------------------------------------------------------------------------
| Follow-up List
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            follow_ups.*,
            customers.name AS customer_name,
            customers.company,
            customers.assigned_to AS customer_assigned_to,
            users.name AS user_name
        FROM follow_ups

        INNER JOIN customers
            ON follow_ups.customer_id = customers.id

        LEFT JOIN users
            ON follow_ups.user_id = users.id

        $whereSql

        ORDER BY follow_ups.follow_up_date ASC

        LIMIT :limit
        OFFSET :offset";


$stmt = $pdo->prepare($sql);


/*
|--------------------------------------------------------------------------
| Bind Normal Parameters
|--------------------------------------------------------------------------
*/

foreach ($params as $key => $value) {

    $stmt->bindValue(
        $key,
        $value
    );
}


/*
|--------------------------------------------------------------------------
| Bind Pagination Parameters
|--------------------------------------------------------------------------
*/

$stmt->bindValue(
    ':limit',
    $perPage,
    PDO::PARAM_INT
);

$stmt->bindValue(
    ':offset',
    $offset,
    PDO::PARAM_INT
);


$stmt->execute();

$followUps = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Pagination URL
|--------------------------------------------------------------------------
*/

function pageUrl($page)
{
    $params = $_GET;

    $params['page'] = $page;

    return '?' . http_build_query($params);
}


/*
|--------------------------------------------------------------------------
| Page Title
|--------------------------------------------------------------------------
*/

$pageTitle = 'Follow-ups';

require_once __DIR__ . '/../app/views/header.php';

?>

<!-- Page Header -->

<div class="page-header">


<div>

    <h1>
        Follow-ups
    </h1>

    <p>
        Manage customer follow-ups, schedules and activities.
    </p>

</div>


<div class="page-header-actions">

    <a
        href="follow-up-add.php"
        class="btn btn-primary"
    >
        + Add Follow-up
    </a>

</div>


</div>

<!-- View Information -->

<div class="info-banner">


<div class="info-banner-icon">
    <?php echo $_SESSION['user_role'] === 'admin' ? 'A' : 'S'; ?>
</div>

<div>

    <?php if ($_SESSION['user_role'] === 'admin'): ?>

        <strong>
            Admin View
        </strong>

        <p>
            You can view and manage follow-ups for all customers.
        </p>

    <?php else: ?>

        <strong>
            Staff View
        </strong>

        <p>
            You can view follow-ups only for your assigned customers.
        </p>

    <?php endif; ?>

</div>


</div>

<!-- Search & Filter -->

<div class="filter-card">


<div class="filter-card-header">

    <div>

        <h2>
            Search & Filter
        </h2>

        <p>
            Find follow-ups by customer, type, status or date.
        </p>

    </div>

</div>


<form method="GET">

    <div class="filter-grid follow-up-filter-grid">


        <!-- Search -->

        <div class="form-group">

            <label>
                Search
            </label>

            <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Customer, company, remarks..."
            >

        </div>


        <!-- Type -->

        <div class="form-group">

            <label>
                Type
            </label>

            <select name="type">

                <option value="">
                    All Types
                </option>

                <option
                    value="call"
                    <?php echo $type === 'call' ? 'selected' : ''; ?>
                >
                    Call
                </option>

                <option
                    value="email"
                    <?php echo $type === 'email' ? 'selected' : ''; ?>
                >
                    Email
                </option>

                <option
                    value="meeting"
                    <?php echo $type === 'meeting' ? 'selected' : ''; ?>
                >
                    Meeting
                </option>

                <option
                    value="other"
                    <?php echo $type === 'other' ? 'selected' : ''; ?>
                >
                    Other
                </option>

            </select>

        </div>


        <!-- Status -->

        <div class="form-group">

            <label>
                Status
            </label>

            <select name="status">

                <option value="">
                    All Statuses
                </option>

                <option
                    value="pending"
                    <?php echo $status === 'pending' ? 'selected' : ''; ?>
                >
                    Pending
                </option>

                <option
                    value="completed"
                    <?php echo $status === 'completed' ? 'selected' : ''; ?>
                >
                    Completed
                </option>

                <option
                    value="cancelled"
                    <?php echo $status === 'cancelled' ? 'selected' : ''; ?>
                >
                    Cancelled
                </option>

            </select>

        </div>


        <!-- Date -->

        <div class="form-group">

            <label>
                Date
            </label>

            <select name="date_filter">

                <option value="">
                    All Dates
                </option>

                <option
                    value="today"
                    <?php echo $dateFilter === 'today' ? 'selected' : ''; ?>
                >
                    Today
                </option>

                <option
                    value="upcoming"
                    <?php echo $dateFilter === 'upcoming' ? 'selected' : ''; ?>
                >
                    Upcoming
                </option>

                <option
                    value="overdue"
                    <?php echo $dateFilter === 'overdue' ? 'selected' : ''; ?>
                >
                    Overdue
                </option>

            </select>

        </div>


        <?php if ($_SESSION['user_role'] === 'admin'): ?>

            <!-- Assigned User -->

            <div class="form-group">

                <label>
                    Customer Assigned To
                </label>

                <select name="assigned_to">

                    <option value="">
                        All Users
                    </option>

                    <option
                        value="0"
                        <?php echo $assignedTo === '0' ? 'selected' : ''; ?>
                    >
                        Not Assigned
                    </option>


                    <?php foreach ($users as $user): ?>

                        <option
                            value="<?php echo htmlspecialchars($user['id']); ?>"
                            <?php
                            echo (
                                (string) $assignedTo ===
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

            </div>

        <?php endif; ?>


    </div>


    <div class="filter-actions">

        <button
            type="submit"
            class="btn btn-primary"
        >
            Search
        </button>

        <a
            href="follow-ups.php"
            class="btn btn-secondary"
        >
            Clear Filters
        </a>

    </div>

</form>


</div>

<!-- Result Summary -->

<div class="list-summary">


<div>

    Showing

    <strong>
        <?php echo $totalFollowUps; ?>
    </strong>

    follow-up(s)

    <?php if ($totalFollowUps > 0): ?>

        <span class="summary-separator">
            •
        </span>

        Page

        <strong>
            <?php echo $page; ?>
        </strong>

        of

        <strong>
            <?php echo $totalPages; ?>
        </strong>

    <?php endif; ?>

</div>


</div>

<!-- Follow-up Table -->

<div class="table-card">


<div class="table-wrapper">

    <table class="data-table follow-up-table">

        <thead>

            <tr>

                <th>
                    Customer
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
                    Created By
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


            <?php if (empty($followUps)): ?>

                <tr>

                    <td
                        colspan="7"
                        class="empty-table-cell"
                    >

                        <div class="empty-state">

                            <div class="empty-state-icon">
                                ✓
                            </div>

                            <h3>
                                No Follow-ups Found
                            </h3>

                            <p>
                                No follow-ups match your current filters.
                            </p>

                            <a
                                href="follow-up-add.php"
                                class="btn btn-primary"
                            >
                                + Add Follow-up
                            </a>

                        </div>

                    </td>

                </tr>


            <?php else: ?>


                <?php foreach ($followUps as $followUp): ?>

                    <?php

                    $followUpDate = strtotime(
                        $followUp['follow_up_date']
                    );

                    $isOverdue =
                        $followUp['status'] === 'pending' &&
                        $followUpDate < time();

                    ?>


                    <tr
                        class="<?php echo $isOverdue ? 'row-overdue' : ''; ?>"
                    >


                        <!-- Customer -->

                        <td>

                            <div class="customer-cell">

                                <a
                                    href="customer-view.php?id=<?php echo (int) $followUp['customer_id']; ?>"
                                    class="customer-name"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $followUp['customer_name']
                                    );
                                    ?>

                                </a>


                                <?php if (!empty($followUp['company'])): ?>

                                    <span class="customer-company">

                                        <?php
                                        echo htmlspecialchars(
                                            $followUp['company']
                                        );
                                        ?>

                                    </span>

                                <?php endif; ?>

                            </div>

                        </td>


                        <!-- Date -->

                        <td>

                            <div class="date-cell">

                                <span class="date-main">

                                    <?php
                                    echo date(
                                        'd M Y',
                                        $followUpDate
                                    );
                                    ?>

                                </span>

                                <span class="date-time">

                                    <?php
                                    echo date(
                                        'h:i A',
                                        $followUpDate
                                    );
                                    ?>

                                </span>

                                <?php if ($isOverdue): ?>

                                    <span class="overdue-label">
                                        Overdue
                                    </span>

                                <?php endif; ?>

                            </div>

                        </td>


                        <!-- Type -->

                        <td>

                            <?php

                            $typeClass = 'badge-type-other';

                            if ($followUp['type'] === 'call') {
                                $typeClass = 'badge-type-call';
                            } elseif ($followUp['type'] === 'email') {
                                $typeClass = 'badge-type-email';
                            } elseif ($followUp['type'] === 'meeting') {
                                $typeClass = 'badge-type-meeting';
                            }

                            ?>

                            <span
                                class="status-badge <?php echo $typeClass; ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    ucfirst($followUp['type'])
                                );
                                ?>

                            </span>

                        </td>


                        <!-- Status -->

                        <td>

                            <?php

                            $statusClass = 'badge-status-pending';

                            if ($followUp['status'] === 'completed') {
                                $statusClass = 'badge-status-completed';
                            } elseif ($followUp['status'] === 'cancelled') {
                                $statusClass = 'badge-status-cancelled';
                            }

                            ?>

                            <span
                                class="status-badge <?php echo $statusClass; ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    ucfirst($followUp['status'])
                                );
                                ?>

                            </span>

                        </td>


                        <!-- Created By -->

                        <td>

                            <span class="created-by">

                                <?php
                                echo htmlspecialchars(
                                    $followUp['user_name']
                                    ?? 'Unknown'
                                );
                                ?>

                            </span>

                        </td>


                        <!-- Remarks -->

                        <td class="remarks-cell">

                            <?php if (!empty($followUp['remarks'])): ?>

                                <span
                                    title="<?php echo htmlspecialchars($followUp['remarks']); ?>"
                                >

                                    <?php
                                    $remarks = trim(
                                        $followUp['remarks']
                                    );

                                    echo htmlspecialchars(
                                        strlen($remarks) > 60
                                            ? substr($remarks, 0, 60) . '...'
                                            : $remarks
                                    );
                                    ?>

                                </span>

                            <?php else: ?>

                                <span class="text-muted">
                                    No remarks
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- Actions -->

                        <td>

                            <div class="table-actions">

                                <a
                                    href="follow-up-edit.php?id=<?php echo (int) $followUp['id']; ?>"
                                    class="btn btn-small btn-secondary"
                                >
                                    Edit
                                </a>


                                <?php if ($_SESSION['user_role'] === 'admin'): ?>

                                    <form
                                        method="POST"
                                        action="follow-up-delete.php"
                                        onsubmit="return confirm('Are you sure you want to delete this follow-up?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?php echo (int) $followUp['id']; ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-small btn-danger"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                <?php endif; ?>

                            </div>

                        </td>


                    </tr>

                <?php endforeach; ?>


            <?php endif; ?>


        </tbody>

    </table>

</div>
```

</div>

<!-- Pagination -->

<?php if ($totalPages > 1): ?>


<div class="pagination-wrapper">

    <div class="pagination">

        <?php if ($page > 1): ?>

            <a
                href="<?php echo htmlspecialchars(pageUrl($page - 1)); ?>"
                class="pagination-link"
            >
                &laquo; Previous
            </a>

        <?php endif; ?>


        <?php

        $startPage = max(1, $page - 2);

        $endPage = min(
            $totalPages,
            $page + 2
        );

        ?>


        <?php if ($startPage > 1): ?>

            <a
                href="<?php echo htmlspecialchars(pageUrl(1)); ?>"
                class="pagination-link"
            >
                1
            </a>

            <?php if ($startPage > 2): ?>

                <span class="pagination-dots">
                    ...
                </span>

            <?php endif; ?>

        <?php endif; ?>


        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>

            <?php if ($i === $page): ?>

                <span class="pagination-link active">
                    <?php echo $i; ?>
                </span>

            <?php else: ?>

                <a
                    href="<?php echo htmlspecialchars(pageUrl($i)); ?>"
                    class="pagination-link"
                >
                    <?php echo $i; ?>
                </a>

            <?php endif; ?>

        <?php endfor; ?>


        <?php if ($endPage < $totalPages): ?>

            <?php if ($endPage < $totalPages - 1): ?>

                <span class="pagination-dots">
                    
                </span>

            <?php endif; ?>

            <a
                href="<?php echo htmlspecialchars(pageUrl($totalPages)); ?>"
                class="pagination-link"
            >
                <?php echo $totalPages; ?>
            </a>

        <?php endif; ?>


        <?php if ($page < $totalPages): ?>

            <a
                href="<?php echo htmlspecialchars(pageUrl($page + 1)); ?>"
                class="pagination-link"
            >
                Next &raquo;
            </a>

        <?php endif; ?>

    </div>

</div>


<?php endif; ?>

<?php

require_once __DIR__ . '/../app/views/footer.php';

?>
