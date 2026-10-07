
<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| Page Title
|--------------------------------------------------------------------------
*/

$pageTitle = 'Add Customer';

$message = '';


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


/*
|--------------------------------------------------------------------------
| Add Customer
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
    | Allowed Customer Status
    |--------------------------------------------------------------------------
    */

    $allowedStatuses = [
        'lead',
        'prospect',
        'customer',
        'inactive'
    ];


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $message = "Name is required.";

    } elseif (
        $email !== '' &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $message = "Please enter a valid email address.";

    } elseif (
        !in_array($status, $allowedStatuses, true)
    ) {

        $message = "Invalid customer status.";

    } elseif (
        $assignedTo !== '' &&
        !ctype_digit((string) $assignedTo)
    ) {

        $message = "Invalid assigned user.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | Validate Assigned User
        |--------------------------------------------------------------------------
        */

        if ($assignedTo !== '') {

            $sql = "SELECT id
                    FROM users
                    WHERE id = :id
                    AND status = 1
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':id' => (int) $assignedTo
            ]);

            $assignedUser = $stmt->fetch();

            if (!$assignedUser) {

                $message = "Selected user is not active or does not exist.";

            }
        }


        /*
        |--------------------------------------------------------------------------
        | Insert Customer
        |--------------------------------------------------------------------------
        */

        if ($message === '') {

            $sql = "INSERT INTO customers
                    (
                        name,
                        email,
                        phone,
                        company,
                        status,
                        source,
                        assigned_to,
                        notes
                    )
                    VALUES
                    (
                        :name,
                        :email,
                        :phone,
                        :company,
                        :status,
                        :source,
                        :assigned_to,
                        :notes
                    )";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':name' => $name,
                ':email' => $email !== '' ? $email : null,
                ':phone' => $phone !== '' ? $phone : null,
                ':company' => $company !== '' ? $company : null,
                ':status' => $status,
                ':source' => $source !== '' ? $source : null,
                ':assigned_to' => $assignedTo !== ''
                    ? (int) $assignedTo
                    : null,
                ':notes' => $notes !== '' ? $notes : null
            ]);


            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

            header("Location: customers.php");

            exit;
        }
    }
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
            Add Customer
        </h1>

        <p>
            New customer ko CRM mein add karein.
        </p>

    </div>


    <div>

        <a
            href="customers.php"
            class="btn"
        >
            ← Back to Customers
        </a>

    </div>

</div>


<!--
|--------------------------------------------------------------------------
| Error Message
|--------------------------------------------------------------------------
-->

<?php if ($message): ?>

    <div class="alert alert-danger">

        <strong>
            Please fix the following:
        </strong>

        <div style="margin-top: 5px;">
            <?php echo htmlspecialchars($message); ?>
        </div>

    </div>

<?php endif; ?>


<!--
|--------------------------------------------------------------------------
| Customer Form
|--------------------------------------------------------------------------
-->

<div class="form-card">

    <div class="form-card-header">

        <div>

            <h2>
                Customer Information
            </h2>

            <p>
                Customer ki basic information enter karein.
            </p>

        </div>

    </div>


    <form method="POST">


        <!--
        |--------------------------------------------------------------------------
        | Basic Information
        |--------------------------------------------------------------------------
        -->

        <div class="form-section">

            <div class="form-section-title">
                Basic Information
            </div>

            <div class="form-grid">


                <!-- Name -->

                <div class="form-group">

                    <label for="name">
                        Customer Name
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?php echo htmlspecialchars(
                            $_POST['name'] ?? ''
                        ); ?>"
                        placeholder="Enter customer name"
                        autocomplete="name"
                        required
                    >

                    <small class="form-help">
                        Customer ka full name enter karein.
                    </small>

                </div>


                <!-- Company -->

                <div class="form-group">

                    <label for="company">
                        Company
                    </label>

                    <input
                        type="text"
                        id="company"
                        name="company"
                        value="<?php echo htmlspecialchars(
                            $_POST['company'] ?? ''
                        ); ?>"
                        placeholder="Enter company name"
                        autocomplete="organization"
                    >

                </div>


                <!-- Email -->

                <div class="form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars(
                            $_POST['email'] ?? ''
                        ); ?>"
                        placeholder="customer@example.com"
                        autocomplete="email"
                    >

                </div>


                <!-- Phone -->

                <div class="form-group">

                    <label for="phone">
                        Phone
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="<?php echo htmlspecialchars(
                            $_POST['phone'] ?? ''
                        ); ?>"
                        placeholder="+91 98765 43210"
                        autocomplete="tel"
                    >

                </div>


            </div>

        </div>


        <!--
        |--------------------------------------------------------------------------
        | CRM Information
        |--------------------------------------------------------------------------
        -->

        <div class="form-section">

            <div class="form-section-title">
                CRM Information
            </div>

            <div class="form-grid">


                <!-- Status -->

                <div class="form-group">

                    <label for="status">
                        Customer Status
                    </label>

                    <select
                        id="status"
                        name="status"
                    >

                        <option
                            value="lead"
                            <?php echo (
                                ($_POST['status'] ?? 'lead') === 'lead'
                            )
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Lead
                        </option>

                        <option
                            value="prospect"
                            <?php echo (
                                ($_POST['status'] ?? '') === 'prospect'
                            )
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Prospect
                        </option>

                        <option
                            value="customer"
                            <?php echo (
                                ($_POST['status'] ?? '') === 'customer'
                            )
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Customer
                        </option>

                        <option
                            value="inactive"
                            <?php echo (
                                ($_POST['status'] ?? '') === 'inactive'
                            )
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Inactive
                        </option>

                    </select>

                    <small class="form-help">
                        Customer ki current pipeline stage select karein.
                    </small>

                </div>


                <!-- Source -->

                <div class="form-group">

                    <label for="source">
                        Lead Source
                    </label>

                    <input
                        type="text"
                        id="source"
                        name="source"
                        value="<?php echo htmlspecialchars(
                            $_POST['source'] ?? ''
                        ); ?>"
                        placeholder="Website, Facebook, Referral..."
                    >

                    <small class="form-help">
                        Customer aapke CRM tak kaise pahucha?
                    </small>

                </div>


                <!-- Assigned To -->

                <div class="form-group">

                    <label for="assigned_to">
                        Assigned To
                    </label>

                    <select
                        id="assigned_to"
                        name="assigned_to"
                    >

                        <option value="">
                            -- Not Assigned --
                        </option>


                        <?php foreach ($users as $user): ?>

                            <option
                                value="<?php echo htmlspecialchars(
                                    $user['id']
                                ); ?>"
                                <?php echo (
                                    ($_POST['assigned_to'] ?? '')
                                    == $user['id']
                                )
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php echo htmlspecialchars(
                                    $user['name']
                                ); ?>

                                -
                                <?php echo htmlspecialchars(
                                    ucfirst($user['role'])
                                ); ?>

                            </option>

                        <?php endforeach; ?>


                    </select>

                    <small class="form-help">
                        Customer ko kisi active team member ko assign karein.
                    </small>

                </div>


            </div>

        </div>


        <!--
        |--------------------------------------------------------------------------
        | Notes
        |--------------------------------------------------------------------------
        -->

        <div class="form-section">

            <div class="form-section-title">
                Additional Information
            </div>


            <div class="form-group">

                <label for="notes">
                    Notes
                </label>

                <textarea
                    id="notes"
                    name="notes"
                    rows="6"
                    placeholder="Customer ke baare mein additional notes..."
                ><?php echo htmlspecialchars(
                    $_POST['notes'] ?? ''
                ); ?></textarea>

                <small class="form-help">
                    Important details, requirements ya additional information
                    yahan add kar sakte hain.
                </small>

            </div>

        </div>


        <!--
        |--------------------------------------------------------------------------
        | Form Actions
        |--------------------------------------------------------------------------
        -->

        <div class="form-actions">

            <a
                href="customers.php"
                class="btn"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Save Customer
            </button>

        </div>


    </form>

</div>


<?php

/*
|--------------------------------------------------------------------------
| Common Footer
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../app/views/footer.php';

?>

