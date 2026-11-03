<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db_connect.php";

$message = "";
$message_type = "";

// Save stock audit
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_id = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);
    $physical_quantity = filter_input(INPUT_POST, "physical_quantity", FILTER_VALIDATE_INT);

    if (!$product_id || $physical_quantity === false || $physical_quantity === null || $physical_quantity < 0) {
        $message = "Please select a medicine and enter a valid physical quantity.";
        $message_type = "error";
    } else {

        $stmt = $conn->prepare("SELECT quantity FROM product WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $message = "The selected medicine could not be found.";
            $message_type = "error";
        } else {

            $medicine = $result->fetch_assoc();

            $system_quantity = (int)$medicine['quantity'];
            $physical_quantity = (int)$physical_quantity;
            $difference = $physical_quantity - $system_quantity;
            $user_id = $_SESSION['user_id'];

            $insert = $conn->prepare("
                INSERT INTO stock_audit
                (product_id, user_id, system_quantity, physical_quantity, difference)
                VALUES (?, ?, ?, ?, ?)
            ");

            $insert->bind_param(
                "iiiii",
                $product_id,
                $user_id,
                $system_quantity,
                $physical_quantity,
                $difference
            );

            if ($insert->execute()) {
                $message = "Stock audit recorded successfully.";
                $message_type = "success";
            } else {
                $message = "Unable to save the stock audit. Please try again.";
                $message_type = "error";
            }

            $insert->close();
        }

        $stmt->close();
    }
}

// Get medicines
$products = $conn->query("
    SELECT product_id, drug_name, quantity
    FROM product
    ORDER BY drug_name ASC
");

// Get audit history
$audits = $conn->query("
    SELECT
        sa.audit_id,
        p.drug_name,
        sa.system_quantity,
        sa.physical_quantity,
        sa.difference,
        u.full_name,
        sa.audit_date
    FROM stock_audit sa
    JOIN product p ON sa.product_id = p.product_id
    LEFT JOIN user u ON sa.user_id = u.user_id
    ORDER BY sa.audit_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Audit | QR PHARMACIES</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f3f7f4;
            margin: 0;
            padding: 25px;
            color: #333;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        h1 {
            color: #176b45;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 25px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }

        label {
            display: block;
            margin: 15px 0 7px;
            font-weight: bold;
        }

        select, input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        button, .back-button {
            display: inline-block;
            background: #176b45;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            margin-top: 20px;
            cursor: pointer;
            text-decoration: none;
            font-size: 15px;
        }

        button:hover, .back-button:hover {
            background: #104d32;
        }

        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .success {
            background: #dff5e7;
            color: #176b45;
        }

        .error {
            background: #ffe5e5;
            color: #b42318;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 13px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #176b45;
            color: white;
        }

        .positive {
            color: #176b45;
            font-weight: bold;
        }

        .negative {
            color: #c0392b;
            font-weight: bold;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .card {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Stock Audit</h1>
    <p class="subtitle">Compare recorded medicine quantities with physical stock.</p>

    <div class="card">

        <h2>Record New Stock Audit</h2>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label>Select Medicine</label>

            <select name="product_id" required>
                <option value="">-- Select Medicine --</option>

                <?php while ($product = $products->fetch_assoc()): ?>
                    <option value="<?php echo $product['product_id']; ?>">
                        <?php echo htmlspecialchars($product['drug_name']); ?>
                        (System quantity: <?php echo $product['quantity']; ?>)
                    </option>
                <?php endwhile; ?>

            </select>

            <label>Physical Quantity Counted</label>

            <input
                type="number"
                name="physical_quantity"
                min="0"
                required
                placeholder="Enter the quantity counted on the shelves"
            >

            <button type="submit">Save Stock Audit</button>

        </form>

    </div>

    <div class="card">

        <h2>Stock Audit History</h2>

        <div class="table-wrapper">

            <table>
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>System Quantity</th>
                        <th>Physical Quantity</th>
                        <th>Difference</th>
                        <th>Audited By</th>
                        <th>Audit Date</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($audits && $audits->num_rows > 0): ?>

                    <?php while ($audit = $audits->fetch_assoc()): ?>

                        <tr>
                            <td><?php echo htmlspecialchars($audit['drug_name']); ?></td>
                            <td><?php echo $audit['system_quantity']; ?></td>
                            <td><?php echo $audit['physical_quantity']; ?></td>

                            <td class="<?php echo $audit['difference'] < 0 ? 'negative' : 'positive'; ?>">
                                <?php
                                echo ($audit['difference'] > 0 ? '+' : '') . $audit['difference'];
                                ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($audit['full_name'] ?? 'Unknown'); ?>
                            </td>

                            <td><?php echo htmlspecialchars($audit['audit_date']); ?></td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6">No stock audits have been recorded yet.</td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>

        </div>

        <a href="index.php" class="back-button">Back to Dashboard</a>

    </div>

</div>

</body>
</html>