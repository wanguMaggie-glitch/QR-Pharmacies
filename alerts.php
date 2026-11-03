<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';

$sql = "
    SELECT
        product_id,
        drug_name,
        batch_no,
        quantity,
        reorder_level,
        expiry_date,
        CASE
            WHEN expiry_date < CURDATE() THEN 'Expired'
            WHEN quantity = 0 THEN 'Out of Stock'
            WHEN expiry_date BETWEEN CURDATE()
                 AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                 THEN 'Expiring Soon'
            WHEN quantity <= reorder_level THEN 'Low Stock'
        END AS alert_status
    FROM product
    WHERE
        quantity <= reorder_level
        OR expiry_date < CURDATE()
        OR expiry_date BETWEEN CURDATE()
            AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY
        CASE
            WHEN expiry_date < CURDATE() THEN 1
            WHEN quantity = 0 THEN 2
            WHEN expiry_date BETWEEN CURDATE()
                 AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                 THEN 3
            ELSE 4
        END,
        drug_name ASC
";

$result = $conn->query($sql);

if (!$result) {
    die("Unable to retrieve alerts. Please check the database.");
}

$totalAlerts = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Stock Alerts | QR PHARMACIES</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f7f4;
            color: #26352c;
        }

        header {
            background: linear-gradient(135deg, #176b45, #104d32);
            color: white;
            padding: 25px 7%;
        }

        header h1 {
            margin-bottom: 8px;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 35px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }

        h2 {
            color: #176b45;
        }

        .summary {
            background: #fff3d6;
            border-left: 5px solid #e39a23;
            padding: 18px;
            margin: 20px 0;
            border-radius: 6px;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 25px;
        }

        table {
            width: 100%;
            min-width: 750px;
            border-collapse: collapse;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #e0e8e2;
        }

        th {
            background: #176b45;
            color: white;
        }

        tr:hover {
            background: #f3f7f4;
        }

        .status {
            font-weight: bold;
        }

        .expired {
            color: #c0392b;
        }

        .out-of-stock {
            color: #c0392b;
        }

        .low-stock {
            color: #d88a12;
        }

        .expiring-soon {
            color: #b7791f;
        }

        .empty {
            text-align: center;
            padding: 25px;
            color: #176b45;
        }

        .back {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 18px;
            background: #176b45;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .back:hover {
            background: #104d32;
        }

        @media(max-width: 600px) {
            .container {
                width: 94%;
                padding: 15px;
            }
        }
    </style>

</head>

<body>

<header>
    <h1>QR PHARMACIES</h1>
    <p>Medicine Stock and Expiry Alerts</p>
</header>

<div class="container">

    <h2>Stock Alerts</h2>

    <p>
        Monitor medicines that require restocking or expiry attention.
    </p>

    <div class="summary">
        <strong>Total Alerts: <?= (int) $totalAlerts ?></strong>
    </div>

    <div class="table-container">

        <table>

            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>Batch Number</th>
                    <th>Available Quantity</th>
                    <th>Reorder Level</th>
                    <th>Expiry Date</th>
                    <th>Alert Status</th>
                </tr>
            </thead>

            <tbody>

                <?php if ($totalAlerts > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <?php
                        $statusClass = strtolower(
                            str_replace(' ', '-', $row['alert_status'])
                        );
                        ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($row['drug_name']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['batch_no'] ?? '') ?>
                            </td>

                            <td>
                                <?= (int) $row['quantity'] ?>
                            </td>

                            <td>
                                <?= (int) $row['reorder_level'] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['expiry_date'] ?? 'Not specified') ?>
                            </td>

                            <td class="status <?= htmlspecialchars($statusClass) ?>">
                                <?= htmlspecialchars($row['alert_status']) ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="empty">
                            No stock or expiry alerts at the moment.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <a href="index.php" class="back">Back to Dashboard</a>

    <a href="medicine.php" class="back">View Medicines</a>

</div>

</body>
</html>