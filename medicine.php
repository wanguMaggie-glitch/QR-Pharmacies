<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'db_connect.php';

$sql = "SELECT
            product.product_id,
            product.drug_name,
            product.batch_no,
            product.quantity,
            product.unit_price,
            product.reorder_level,
            product.expiry_date,
            category.category_name
        FROM product
        LEFT JOIN category
        ON product.category_id = category.category_id
        ORDER BY product.drug_name ASC";

$result = $conn->query($sql);

if (!$result) {
    die("Error retrieving medicines: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Medicines | QR PHARMACIES</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            color: #263238;
        }

        header {
            background: #087f6e;
            color: white;
            padding: 22px 30px;
        }

        header h1 {
            margin-bottom: 8px;
        }

        .container {
            padding: 30px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .button {
            display: inline-block;
            background: #087f6e;
            color: white;
            padding: 12px 18px;
            text-decoration: none;
            border-radius: 5px;
        }

        .button:hover {
            background: #056457;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        th, td {
            padding: 14px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #e0f2f1;
            color: #164e45;
        }

        tr:hover {
            background: #f8fafc;
        }

        .low-stock {
            color: #b45309;
            font-weight: bold;
        }

        .expired {
            color: #b91c1c;
            font-weight: bold;
        }

        .available {
            color: #047857;
            font-weight: bold;
        }

        .empty {
            padding: 25px;
            text-align: center;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #64748b;
        }

        @media (max-width: 600px) {
            .container {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>QR Chemists</h1>
    <p>Pharmacy Stock Management System</p>
</header>

<main class="container">

    <div class="top-bar">
        <div>
            <h2>Medicine Inventory</h2>
            <p>View and monitor medicines in stock.</p>
        </div>

        <a href="index.php" class="button">Back to Dashboard</a>
    </div>

    <div class="table-container">

        <?php if ($result->num_rows > 0): ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Medicine</th>
                        <th>Category</th>
                        <th>Batch Number</th>
                        <th>Quantity</th>
                        <th>Unit Price (KSh)</th>
                        <th>Expiry Date</th>
                        <th>Stock Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php while ($medicine = $result->fetch_assoc()): ?>

                    <?php
                    $isExpired = !empty($medicine['expiry_date'])
                        && $medicine['expiry_date'] < date('Y-m-d');

                    $isLowStock = $medicine['quantity']
                        <= $medicine['reorder_level'];
                    ?>

                    <tr>
                        <td>
                            <?= htmlspecialchars($medicine['product_id']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($medicine['drug_name']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($medicine['category_name'] ?? 'Uncategorized') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($medicine['batch_no'] ?? '') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($medicine['quantity']) ?>
                        </td>

                        <td>
                            <?= number_format((float)$medicine['unit_price'], 2) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($medicine['expiry_date'] ?? 'Not specified') ?>
                        </td>

                        <td>
                        <td>
    <?php if ($isExpired): ?>
        <span class="expired">Expired</span>
    <?php elseif ($isLowStock): ?>
        <span class="low-stock">Low Stock</span>
    <?php else: ?>
        <span class="available">Available</span>
    <?php endif; ?>
</td>

<td>
    <a href="edit_medicine.php?id=<?= urlencode($medicine['product_id']) ?>"
       class="button">Edit</a>

```
<form action="delete_medicine.php" method="POST"
      style="display:inline;"
      onsubmit="return confirm('Are you sure you want to delete this medicine record? This action cannot be undone.');">

    <input type="hidden" name="id"
           value="<?= htmlspecialchars($medicine['product_id']) ?>">

    <button type="submit"
            style="background:#dc2626;color:white;padding:12px 18px;border:none;border-radius:5px;cursor:pointer;">
        Delete
    </button>
</form>
```

</td>

                    </tr>

                <?php endwhile; ?>

                </tbody>
            </table>

        <?php else: ?>

            <div class="empty">
                <p>No medicines have been added to the inventory yet.</p>
            </div>

        <?php endif; ?>

    </div>

</main>

<footer>
    &copy; <?= date("Y") ?> Wanjiru Chemists Stock Management System
</footer>

</body>
</html>