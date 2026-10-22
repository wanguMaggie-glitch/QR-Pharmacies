<?php
session_start();
require_once 'db_connect.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Identify the user's role
$userRole = strtolower($_SESSION['role'] ?? '');
$isManager = ($userRole === 'manager');

// Messages
$message = '';
$error = '';

// Only the manager can add or delete suppliers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isManager) {
    http_response_code(403);
    die("Access denied. Only the manager can manage suppliers.");
}

// Add a new supplier - manager only
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['add_supplier'])
    && $isManager) {

    $supplier_name = trim($_POST['supplier_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($supplier_name === '' || $phone === '' || $address === '') {
        $error = "Please fill in all the fields.";
    } else {
        $check = $conn->prepare(
            "SELECT supplier_id FROM supplier WHERE supplier_name = ?"
        );

        $check->bind_param("s", $supplier_name);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "This supplier already exists.";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO supplier (supplier_name, phone, address)
                 VALUES (?, ?, ?)"
            );

            $stmt->bind_param("sss", $supplier_name, $phone, $address);

            if ($stmt->execute()) {
                $stmt->close();
                $check->close();

                header("Location: suppliers.php?success=added");
                exit();
            } else {
                $error = "Unable to add supplier. Please try again.";
            }

            $stmt->close();
        }

        $check->close();
    }
}

// Delete a supplier - manager only
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['delete_supplier'])
    && $isManager) {

    $supplier_id = (int) ($_POST['supplier_id'] ?? 0);

    if ($supplier_id > 0) {
        $stmt = $conn->prepare(
            "DELETE FROM supplier WHERE supplier_id = ?"
        );

        $stmt->bind_param("i", $supplier_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $stmt->close();

            header("Location: suppliers.php?success=deleted");
            exit();
        } else {
            $error = "Supplier could not be deleted. It may be linked to existing medicines.";
        }

        $stmt->close();
    } else {
        $error = "Invalid supplier selected.";
    }
}

// Display success messages
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $message = "Supplier added successfully!";
    } elseif ($_GET['success'] === 'deleted') {
        $message = "Supplier deleted successfully!";
    }
}

// Retrieve suppliers
$suppliers = $conn->query(
    "SELECT supplier_id, supplier_name, phone, address
     FROM supplier
     ORDER BY supplier_name ASC"
);

if (!$suppliers) {
    die("Error retrieving suppliers: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Suppliers | QR PHARMACIES</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f2f6f5;
            color: #243b35;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .header {
            background: #087f5b;
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .header h1 {
            margin: 0 0 8px;
        }

        .header p {
            margin: 0;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #087f5b;
            text-decoration: none;
            font-weight: bold;
        }

        .card {
            background: white;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .card h2 {
            margin-top: 0;
            color: #087f5b;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccd8d3;
            border-radius: 6px;
            font-size: 15px;
        }

        input:focus {
            outline: 2px solid #087f5b;
            border-color: transparent;
        }

        button {
            border: none;
            padding: 11px 17px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .add-button {
            margin-top: 20px;
            background: #087f5b;
            color: white;
            font-weight: bold;
        }

        .add-button:hover {
            background: #066647;
        }

        .delete-button {
            background: #c92a2a;
            color: white;
        }

        .delete-button:hover {
            background: #a61e1e;
        }

        .message {
            padding: 13px;
            border-radius: 6px;
            margin-bottom: 20px;
            background: #d3f9d8;
            color: #216e39;
        }

        .error {
            padding: 13px;
            border-radius: 6px;
            margin-bottom: 20px;
            background: #ffe3e3;
            color: #a61e1e;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #e5ece8;
        }

        th {
            background: #e6f4ef;
            color: #087f5b;
        }

        tr:hover {
            background: #f8fbf9;
        }

        .empty {
            text-align: center;
            padding: 25px;
            color: #687a73;
        }

        .role-note {
            background: #e6f4ef;
            color: #087f5b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        @media (max-width: 700px) {
            body {
                padding: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <a class="back-link" href="index.php">
        &larr; Back to Dashboard
    </a>

    <div class="header">
        <h1>QR PHARMACIES</h1>
        <p>Manage Suppliers</p>
    </div>

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

    <?php if (!$isManager): ?>
        <div class="role-note">
            You are logged in as a Clerk. You can view supplier
            information but cannot add or delete suppliers.
        </div>
    <?php endif; ?>

    <?php if ($isManager): ?>
        <div class="card">
            <h2>Add New Supplier</h2>

            <form method="POST" action="suppliers.php">

                <div class="form-grid">

                    <div>
                        <label for="supplier_name">Supplier Name</label>
                        <input
                            type="text"
                            id="supplier_name"
                            name="supplier_name"
                            required
                        >
                    </div>

                    <div>
                        <label for="phone">Phone Number</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            required
                        >
                    </div>

                    <div>
                        <label for="address">Address</label>
                        <input
                            type="text"
                            id="address"
                            name="address"
                            required
                        >
                    </div>

                </div>

                <button
                    class="add-button"
                    type="submit"
                    name="add_supplier"
                >
                    Add Supplier
                </button>

            </form>
        </div>
    <?php endif; ?>

    <div class="card">

        <h2>Registered Suppliers</h2>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Supplier Name</th>
                        <th>Phone Number</th>
                        <th>Address</th>

                        <?php if ($isManager): ?>
                            <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($suppliers->num_rows > 0): ?>

                        <?php while ($row = $suppliers->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?= (int) $row['supplier_id'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['supplier_name']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['phone']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['address']) ?>
                                </td>

                                <?php if ($isManager): ?>
                                    <td>

                                        <form
                                            method="POST"
                                            action="suppliers.php"
                                            onsubmit="return confirm('Are you sure you want to delete this supplier?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="supplier_id"
                                                value="<?= (int) $row['supplier_id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="delete_supplier"
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
                            <td
                                colspan="<?= $isManager ? 5 : 4 ?>"
                                class="empty"
                            >
                                No suppliers have been registered yet.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>
    </div>

</div>

</body>
</html>