
<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| Page Title
|--------------------------------------------------------------------------
*/

$pageTitle = 'Customers';


/*
|--------------------------------------------------------------------------
| Search & Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$status = $_GET['status'] ?? '';

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
| Allowed Statuses
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    'lead',
    'prospect',
    'customer',
    'inactive'
];

if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
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
        customers.name LIKE :search_name
        OR customers.email LIKE :search_email
        OR customers.phone LIKE :search_phone
        OR customers.company LIKE :search_company
    )";

    $like = '%' . $search . '%';

    $params[':search_name']    = $like;
    $params[':search_email']   = $like;
    $params[':search_phone']   = $like;
    $params[':search_company'] = $like;
}

/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

if ($status !== '') {

    $where[] = "customers.status = :status";

    $params[':status'] = $status;
}


/*
|--------------------------------------------------------------------------
| Assigned User Filter
|--------------------------------------------------------------------------
*/

if (
    $_SESSION['user_role'] === 'admin' &&
    $assignedTo !== ''
) {

    if ($assignedTo === '0') {

        // Not Assigned customers
        $where[] = "customers.assigned_to IS NULL";

    } elseif (is_numeric($assignedTo)) {

        // Specific user ke assigned customers
        $where[] = "customers.assigned_to = :assigned_to";

        $params[':assigned_to'] = (int) $assignedTo;
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
| Total Customer Count
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*)
        FROM customers
        $whereSql";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$totalCustomers = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Total Pages
|--------------------------------------------------------------------------
*/

$totalPages = max(
    1,
    (int) ceil($totalCustomers / $perPage)
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
| Customer List
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            customers.*,
            users.name AS assigned_user_name
        FROM customers
        LEFT JOIN users
            ON customers.assigned_to = users.id
        $whereSql
        ORDER BY customers.id DESC
        LIMIT :limit
        OFFSET :offset";

$stmt = $pdo->prepare($sql);


/*
|--------------------------------------------------------------------------
| Normal Parameters
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
| Pagination Parameters
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

$customers = $stmt->fetchAll();


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
| Common Header
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../app/views/header.php';

?>


<!--
|--------------------------------------------------------------------------
| Page Header
|--------------------------------------------------------------------------
-->

<div class="page-header">

    <div>

        <h1>
            Customers
        </h1>

        <p class="text-muted">
            Manage your customer records and assignments.
        </p>

    </div>


    <div class="actions">

        <a
            href="customer-add.php"
            class="btn btn-primary"
        >
            + Add Customer
        </a>

    </div>

</div>


<!--
|--------------------------------------------------------------------------
| Access Information
|--------------------------------------------------------------------------
-->

<div class="alert">

    <?php if ($_SESSION['user_role'] === 'admin'): ?>

        <strong>
            Admin View:
        </strong>

        You can view and manage all customers.

    <?php else: ?>

        <strong>
            Staff View:
        </strong>

        You can view and manage only your assigned customers.

    <?php endif; ?>

</div>


<!--
|--------------------------------------------------------------------------
| Search & Filter
|--------------------------------------------------------------------------
-->

<div class="section">

    <div class="section-header">

        <div>

            <h2>
                Search & Filter
            </h2>

            <p>
                Find customers quickly using search and filters.
            </p>

        </div>

    </div>


    <form method="GET">

        <div class="filter-grid">


            <!-- Search -->

            <div class="form-group">

                <label for="search">
                    Search
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Name, email, phone, company..."
                >

            </div>


            <!-- Status -->

            <div class="form-group">

                <label for="status">
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                >

                    <option value="">
                        All Statuses
                    </option>

                    <option
                        value="lead"
                        <?php echo $status === 'lead'
                            ? 'selected'
                            : ''; ?>
                    >
                        Lead
                    </option>

                    <option
                        value="prospect"
                        <?php echo $status === 'prospect'
                            ? 'selected'
                            : ''; ?>
                    >
                        Prospect
                    </option>

                    <option
                        value="customer"
                        <?php echo $status === 'customer'
                            ? 'selected'
                            : ''; ?>
                    >
                        Customer
                    </option>

                    <option
                        value="inactive"
                        <?php echo $status === 'inactive'
                            ? 'selected'
                            : ''; ?>
                    >
                        Inactive
                    </option>

                </select>

            </div>


            <!-- Assigned User -->

            <?php if ($_SESSION['user_role'] === 'admin'): ?>

                <div class="form-group">

                    <label for="assigned_to">
                        Assigned To
                    </label>

                    <select
                        id="assigned_to"
                        name="assigned_to"
                    >

                        <option value="">
                            All Users
                        </option>

                        <option
                            value="0"
                            <?php echo $assignedTo === '0'
                                ? 'selected'
                                : ''; ?>
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


        <div class="actions">

            <button
                type="submit"
                class="btn btn-primary"
            >
                Search
            </button>


            <a
                href="customers.php"
                class="btn"
            >
                Clear Filters
            </a>

        </div>

    </form>

</div>


<!--
|--------------------------------------------------------------------------
| Result Information
|--------------------------------------------------------------------------
-->

<div class="section">

    <div class="page-header">

        <div>

            <h2>
                Customer List
            </h2>

            <p>

                Showing

                <strong>
                    <?php echo $totalCustomers; ?>
                </strong>

                customer(s).

                <?php if ($totalCustomers > 0): ?>

                    &nbsp; | &nbsp;

                    Page

                    <strong>
                        <?php echo $page; ?>
                    </strong>

                    of

                    <strong>
                        <?php echo $totalPages; ?>
                    </strong>

                <?php endif; ?>

            </p>

        </div>

    </div>


    <!--
    |--------------------------------------------------------------------------
    | Customer Table
    |--------------------------------------------------------------------------
    -->

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
                        Email
                    </th>

                    <th>
                        Phone
                    </th>

                    <th>
                        Company
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Source
                    </th>

                    <th>
                        Assigned To
                    </th>

                    <th>
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php if (empty($customers)): ?>

                    <tr>

                        <td
                            colspan="9"
                            class="empty-state"
                        >

                            <strong>
                                No customers found.
                            </strong>

                            <br>

                            Try changing your search or filters.

                        </td>

                    </tr>

                <?php else: ?>


                    <?php foreach ($customers as $customer): ?>

                        <tr>


                            <!-- ID -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $customer['id']
                                );
                                ?>

                            </td>


                            <!-- Name -->

                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $customer['name']
                                    );
                                    ?>

                                </strong>

                            </td>


                            <!-- Email -->

                            <td>

                                <?php

                                if (!empty($customer['email'])):

                                ?>

                                    <a
                                        href="mailto:<?php echo htmlspecialchars($customer['email']); ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $customer['email']
                                        );
                                        ?>

                                    </a>

                                <?php else: ?>

                                    <span class="text-muted">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Phone -->

                            <td>

                                <?php

                                if (!empty($customer['phone'])):

                                ?>

                                    <?php
                                    echo htmlspecialchars(
                                        $customer['phone']
                                    );
                                    ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Company -->

                            <td>

                                <?php

                                if (!empty($customer['company'])):

                                ?>

                                    <?php
                                    echo htmlspecialchars(
                                        $customer['company']
                                    );
                                    ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Status -->

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

                                    <?php
                                    echo htmlspecialchars(
                                        ucfirst($customer['status'])
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- Source -->

                            <td>

                                <?php

                                if (!empty($customer['source'])):

                                ?>

                                    <?php
                                    echo htmlspecialchars(
                                        $customer['source']
                                    );
                                    ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Assigned To -->

                            <td>

                                <?php

                                if (!empty($customer['assigned_user_name'])):

                                ?>

                                    <?php
                                    echo htmlspecialchars(
                                        $customer['assigned_user_name']
                                    );
                                    ?>

                                <?php else: ?>

                                    <span class="badge badge-inactive">
                                        Not Assigned
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Actions -->

                            <td>

                                <div class="actions">


                                    <a
                                        href="customer-view.php?id=<?php echo urlencode($customer['id']); ?>"
                                        class="btn btn-sm"
                                    >
                                        View
                                    </a>


                                    <a
                                        href="customer-edit.php?id=<?php echo urlencode($customer['id']); ?>"
                                        class="btn btn-sm"
                                    >
                                        Edit
                                    </a>


                                    <?php if ($_SESSION['user_role'] === 'admin'): ?>


                                        <form
                                            method="POST"
                                            action="customer-delete.php"
                                            style="display: inline;"
                                            onsubmit="return confirm('Are you sure you want to delete this customer?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?php echo htmlspecialchars($customer['id']); ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="btn btn-danger btn-sm"
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


    <!--
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    -->

    <?php if ($totalPages > 1): ?>

        <div class="pagination">


            <?php if ($page > 1): ?>

                <a
                    href="<?php echo htmlspecialchars(pageUrl($page - 1)); ?>"
                    class="pagination-link"
                >
                    &laquo; Previous
                </a>

            <?php endif; ?>


            <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                <?php if ($i == $page): ?>

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


            <?php if ($page < $totalPages): ?>

                <a
                    href="<?php echo htmlspecialchars(pageUrl($page + 1)); ?>"
                    class="pagination-link"
                >
                    Next &raquo;
                </a>

            <?php endif; ?>


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

