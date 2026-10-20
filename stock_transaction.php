<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'db_connect.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $transaction_type = $_POST['transaction_type'] ?? '';
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if (!$product_id || !in_array($transaction_type, ['IN', 'OUT'], true)
        || !$quantity || $quantity < 1) {
        $message = "Please select a medicine and enter a valid quantity.";
        $messageType = 'error';
    } else {
        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare(
                "SELECT quantity FROM product WHERE product_id = ? FOR UPDATE"
            );
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();

            if (!$product) {
                throw new Exception("Medicine not found.");
            }

            $currentQuantity = (int) $product['quantity'];

            if ($transaction_type === 'OUT' && $quantity > $currentQuantity) {
                throw new Exception("Insufficient stock. Available quantity: "
                    . $currentQuantity);
            }

            if ($transaction_type === 'IN') {
                $update = $conn->prepare(
                    "UPDATE product SET quantity = quantity + ? WHERE product_id = ?"
                );
            } else {
                $update = $conn->prepare(
                    "UPDATE product SET quantity = quantity - ? WHERE product_id = ?"
                );
            }

            $update->bind_param("ii", $quantity, $product_id);
            $update->execute();
            $update->close();

            $user_id = null;

            $insert = $conn->prepare(
                "INSERT INTO stock_transaction
                (product_id, user_id, transaction_type, quantity, transaction_date)
                VALUES (?, ?, ?, ?, NOW())"
            );

            $insert->bind_param(
                "iisi",
                $product_id,
                $user_id,
                $transaction_type,
                $quantity
            );

            $insert->execute();
            $insert->close();

            $conn->commit();

            $message = "Stock transaction recorded successfully!";
            $messageType = 'success';
        } catch (Throwable $e) {
            $conn->rollback();
            $message = "Transaction failed: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$products = $conn->query(
    "SELECT product_id, drug_name, quantity
     FROM product
     ORDER BY drug_name ASC"
);
?>

<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Transactions | QR
         Chemists</title>

```
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
        background: #087f6e;
        color: white;
        padding: 22px 30px;
    }

    .container {
        max-width: 650px;
        margin: 35px auto;
        padding: 25px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
    }

    label {
        display: block;
        margin-top: 18px;
        font-weight: bold;
    }

    select, input {
        width: 100%;
        padding: 12px;
        margin-top: 7px;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
    }

    button, .back {
        display: inline-block;
        margin-top: 22px;
        padding: 12px 18px;
        border: none;
        border-radius: 5px;
        background: #087f6e;
        color: white;
        text-decoration: none;
        cursor: pointer;
    }

    .message {
        margin-top: 20px;
        padding: 12px;
        border-radius: 5px;
    }

    .success {
        background: #dcfce7;
        color: #166534;
    }

    .error {
        background: #fee2e2;
        color: #991b1b;
    }
</style>


</head>
<body>

<header>
    <h1>QR Chemists</h1>
    <p>Stock Transaction Management</p>
</header>

<div class="container">
    <h2>Record Stock Transaction</h2>
    <p>Record medicines received or issued from the pharmacy.</p>


<?php if ($message !== ''): ?>
    <div class="message <?= htmlspecialchars($messageType) ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<form method="POST">
    <label for="product_id">Select Medicine</label>
    <select name="product_id" id="product_id" required>
        <option value="">-- Choose Medicine --</option>

        <?php while ($product = $products->fetch_assoc()): ?>
            <option value="<?= (int) $product['product_id'] ?>">
                <?= htmlspecialchars($product['drug_name']) ?>
                (Stock: <?= (int) $product['quantity'] ?>)
            </option>
        <?php endwhile; ?>
    </select>

    <label for="transaction_type">Transaction Type</label>
    <select name="transaction_type" id="transaction_type" required>
        <option value="">-- Select Type --</option>
        <option value="IN">Stock Received (IN)</option>
        <option value="OUT">Stock Issued (OUT)</option>
    </select>

    <label for="quantity">Quantity</label>
    <input type="number" name="quantity" id="quantity"
           min="1" step="1" required>

    <button type="submit">Save Transaction</button>
</form>

<a class="back" href="medicine.php">Back to Medicine Inventory</a>
<a class="back" href="index.php">Dashboard</a>


</div>

</body>
</html>
