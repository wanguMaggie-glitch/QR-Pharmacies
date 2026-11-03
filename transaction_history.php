<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';

// Retrieve transaction history
$sql = "
    SELECT
        st.transaction_id,
        p.drug_name,
        st.transaction_type,
        st.quantity,
        u.full_name,
        st.transaction_date
    FROM stock_transaction st
    INNER JOIN product p
        ON st.product_id = p.product_id
    LEFT JOIN `user` u
        ON st.user_id = u.user_id
    ORDER BY st.transaction_date DESC,
             st.transaction_id DESC
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Transaction History | QR PHARMACIES</title>

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
            margin: 0 0 8px;
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

        .table-container {
            overflow-x: auto;
            margin-top: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th, td {
            padding: 14px;
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

        .receipt {
            color: #176b45;
            font-weight: bold;
        }

        .sale {
            color: #c0392b;
            font-weight: bold;
        }

        .adjustment {
            color: #d88a12;
            font-weight: bold;
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

        .empty {
            padding: 20px;
            text-align: center;
            color: #69776d;
        }

        footer {
            text-align: center;
            padding: 22px;
            background: #104d32;
            color: white;
            margin-top: 40px;
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
    <p>Stock Transaction Management</p>
</header>

<div class="container">

    <h2>Stock Transaction History</h2>

    <p>
        View all medicines received and issued from the pharmacy.
    </p>

    <div class="table-container">

        <table>

            <thead>
                <tr>
                    <th>No.</th>
                    <th>Medicine Name</th>
                    <th>Transaction Type</th>
                    <th>Quantity</th>
                    <th>Recorded By</th>
                    <th>Date and Time</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($result && $result->num_rows > 0): ?>

                <?php $number = 1; ?>

                <?php while ($row = $result->fetch_assoc()): ?>

                    <?php
                    $type = strtoupper($row['transaction_type']);

                    $typeClass = strtolower($type);

                    $typeLabel = [
                        'RECEIPT' => 'Stock Received',
                        'SALE' => 'Stock Issued',
                        'ADJUSTMENT' => 'Stock Adjustment'
                    ];

                    $displayType = $typeLabel[$type] ?? $type;
                    ?>

                    <tr>

                        <td><?= $number++ ?></td>

                        <td>
                            <?= htmlspecialchars($row['drug_name']) ?>
                        </td>

                        <td class="<?= htmlspecialchars($typeClass) ?>">
                            <?= htmlspecialchars($displayType) ?>
                        </td>

                        <td>
                            <?= (int) $row['quantity'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['full_name'] ?? 'Unknown user') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['transaction_date']) ?>
                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="6" class="empty">
                        No stock transactions have been recorded yet.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

    <a href="stock_transaction.php" class="back">
        Record New Transaction
    </a>

    <a href="index.php" class="back">
        Back to Dashboard
    </a>

</div>

<footer>
    &copy; <?= date("Y") ?> QR PHARMACIES Stock Management System
</footer>

</body>
</html>