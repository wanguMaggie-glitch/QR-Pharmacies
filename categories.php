php
<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Identify the user's role
$userRole = strtolower($_SESSION['role'] ?? '');
$isManager = ($userRole === 'manager');

require_once 'db_connect.php';

$message = '';
$error = '';

// Only the manager can add categories
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {

    if (!$isManager) {
        http_response_code(403);
        die('Access denied. Only the manager can add categories.');
    }

    $category_name = trim($_POST['category_name'] ?? '');

    if ($category_name === '') {
        $error = 'Please enter a category name.';
    } else {
        $check = $conn->prepare(
            "SELECT category_id FROM category WHERE category_name = ?"
        );

        if (!$check) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $check->bind_param("s", $category_name);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = 'This category already exists.';
                $check->close();
            } else {
                $check->close();

                $stmt = $conn->prepare(
                    "INSERT INTO category (category_name) VALUES (?)"
                );

                if ($stmt) {
                    $stmt->bind_param("s", $category_name);

                    if ($stmt->execute()) {
                        $stmt->close();
                        header('Location: categories.php?added=1');
                        exit;
                    } else {
                        $error = 'Unable to add category: ' . $stmt->error;
                        $stmt->close();
                    }
                } else {
                    $error = 'Database error: ' . $conn->error;
                }
            }
        }
    }
}

// Only the manager can delete categories
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {

    if (!$isManager) {
        http_response_code(403);
        die('Access denied. Only the manager can delete categories.');
    }

    $category_id = (int)($_POST['category_id'] ?? 0);

    if ($category_id > 0) {
        $stmt = $conn->prepare(
            "DELETE FROM category WHERE category_id = ?"
        );

        if ($stmt) {
            $stmt->bind_param("i", $category_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $stmt->close();
                    header('Location: categories.php?deleted=1');
                    exit;
                } else {
                    $error = 'Category not found.';
                }
            } else {
                $error = 'Unable to delete this category. It may be linked to existing medicines.';
            }

            $stmt->close();
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    } else {
        $error = 'Invalid category selected.';
    }
}

// Display feedback messages
if (isset($_GET['added'])) {
    $message = 'Category added successfully.';
}

if (isset($_GET['deleted'])) {
    $message = 'Category deleted successfully.';
}

// Retrieve categories for both manager and clerk
$categories = $conn->query(
    "SELECT category_id, category_name
     FROM category
     ORDER BY category_name ASC"
);

if (!$categories) {
    die('Error retrieving categories: ' . htmlspecialchars($conn->error));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Categories | QR PHARMACIES</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            color: #263238;
        }

        header {
            background: #087f5b;
            color: white;
            padding: 24px 6%;
        }

        header h1 {
            margin: 0;
            font-size: 28px;
        }

        header p {
            margin: 7px 0 0;
            font-size: 15px;
        }

        .container {
            width: 90%;
            max-width: 1050px;
            margin: 30px auto;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 22px;
            color: #087f5b;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .panel {
            background: white;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #087f5b;
            font-size: 21px;
        }

        .form-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        input[type="text"] {
            flex: 1;
            min-width: 220px;
            padding: 12px;
            border: 1px solid #ccd5d2;
            border-radius: 6px;
            font-size: 15px;
        }

        input[type="text"]:focus {
            outline: 2px solid #b2f2d8;
            border-color: #087f5b;
        }

        button {
            border: none;
            border-radius: 6px;
            padding: 12px 18px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }

        .add-button {
            background: #087f5b;
            color: white;
        }

        .add-button:hover {
            background: #066849;
        }

        .delete-button {
            background: #c92a2a;
            color: white;
        }

        .delete-button:hover {
            background: #a61e1e;
        }

        .message {
            background: #d3f9d8;
            color: #176b35;
            padding: 13px;
            border-radius: 6px;
            margin-bottom: 18px;
        }

        .error {
            background: #ffe3e3;
            color: #a51111;
            padding: 13px;
            border-radius: 6px;
            margin-bottom: 18px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px;
            border-bottom: 1px solid #e5e9e7;
            text-align: left;
        }

        th {
            background: #edf7f2;
            color: #087f5b;
        }

        tbody tr:hover {
            background: #f8fbf9;
        }

        .empty {
            text-align: center;
            padding: 25px;
            color: #66736e;
        }

        footer {
            text-align: center;
            color: #66736e;
            padding: 20px;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            .container {
                width: 94%;
            }

            .panel {
                padding: 18px;
            }

            .form-row {
                flex-direction: column;
            }

            .form-row button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>QR PHARMACIES</h1>
    <p>Pharmacy Stock Management System</p>
</header>

<main class="container">

    <a class="back-link" href="index.php">&larr; Back to Dashboard</a>

    <?php if ($message !== ''): ?>
        <div class="message">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($isManager): ?>
        <section class="panel">
            <h2>Add New Category</h2>

            <form method="POST" action="categories.php">
                <div class="form-row">
                    <input
                        type="text"
                        name="category_name"
                        placeholder="Enter category name"
                        maxlength="100"
                        required
                    >

                    <button
                        class="add-button"
                        type="submit"
                        name="add_category"
                    >
                        Add Category
                    </button>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <section class="panel">
        <h2>Existing Categories</h2>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Category ID</th>
                        <th>Category Name</th>

                        <?php if ($isManager): ?>
                            <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>
                <?php if ($categories->num_rows > 0): ?>

                    <?php while ($category = $categories->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?= (int)$category['category_id'] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($category['category_name']) ?>
                            </td>

                            <?php if ($isManager): ?>
                                <td>
                                    <form
                                        method="POST"
                                        action="categories.php"
                                        onsubmit="return confirm('Are you sure you want to delete this category?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="category_id"
                                            value="<?= (int)$category['category_id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="delete_category"
                                            class="delete-button"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="<?= $isManager ? 3 : 2 ?>" class="empty">
                            No categories have been added yet.
                        </td>
                    </tr>

                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<footer>
    QR PHARMACIES &copy; <?= date('Y') ?>
    | Pharmacy Stock Management System
</footer>

</body>
</html>

