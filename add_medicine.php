<?php
require_once 'db_connect.php';

$message = "";
$messageType = "";

$categories = $conn->query(
    "SELECT category_id, category_name
     FROM category
     ORDER BY category_name"
);

$suppliers = $conn->query(
    "SELECT supplier_id, supplier_name
     FROM supplier
     ORDER BY supplier_name"
);

if (!$categories || !$suppliers) {
    die("Unable to load categories or suppliers. Check your database tables.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $supplier_id = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT);
    $drug_name = trim($_POST['drug_name'] ?? '');
    $batch_no = trim($_POST['batch_no'] ?? '');
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $unit_price = filter_input(INPUT_POST, 'unit_price', FILTER_VALIDATE_FLOAT);
    $reorder_level = filter_input(INPUT_POST, 'reorder_level', FILTER_VALIDATE_INT);
    $expiry_date = trim($_POST['expiry_date'] ?? '');

    if (
        !$category_id ||
        !$supplier_id ||
        $drug_name === '' ||
        $batch_no === '' ||
        $quantity === false ||
        $quantity === null ||
        $quantity < 0 ||
        $unit_price === false ||
        $unit_price === null ||
        $unit_price < 0 ||
        $reorder_level === false ||
        $reorder_level === null ||
        $reorder_level < 0 ||
        $expiry_date === ''
    ) {
        $message = "Please complete all fields with valid information.";
        $messageType = "error";
    } else {

        $stmt = $conn->prepare(
            "INSERT INTO product
            (category_id, supplier_id, drug_name, batch_no,
             quantity, unit_price, reorder_level, expiry_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "iissidis",
            $category_id,
            $supplier_id,
            $drug_name,
            $batch_no,
            $quantity,
            $unit_price,
            $reorder_level,
            $expiry_date
        );

        if ($stmt->execute()) {
            $message = "Medicine added successfully!";
            $messageType = "success";
        } else {
            $message = "Unable to add medicine: " . $stmt->error;
            $messageType = "error";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Medicine | Wanjiru Chemists</title>

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

        .container {
            max-width: 800px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .form-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.07);
        }

        .form-card h2 {
            margin-bottom: 10px;
        }

        .intro {
            color: #64748b;
            margin-bottom: 25px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            font-size: 15px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: 2px solid #99d5cb;
            border-color: #087f6e;
        }

        .buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .button {
            border: none;
            background: #087f6e;
            color: white;
            padding: 13px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 15px;
        }

        .button:hover {
            background: #056457;
        }

        .secondary {
            background: #64748b;
        }

        .message {
            padding: 14px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #64748b;
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>Wanjiru Chemists</h1>
    <p>Pharmacy Stock Management System</p>
</header>

<main class="container">

    <section class="form-card">
        <h2>Add New Medicine</h2>
        <p class="intro">Enter the medicine details below.</p>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="add_medicine.php">

            <div class="form-grid">

                <div class="form-group">
                    <label for="drug_name">Medicine Name</label>
                    <input type="text" id="drug_name" name="drug_name"
                           required maxlength="255"
                           value="<?= htmlspecialchars($_POST['drug_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select category</option>

                        <?php while ($category = $categories->fetch_assoc()): ?>
                            <option value="<?= $category['category_id'] ?>"
                                <?= (($_POST['category_id'] ?? '') == $category['category_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['category_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="supplier_id">Supplier</label>
                    <select id="supplier_id" name="supplier_id" required>
                        <option value="">Select supplier</option>

                        <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                            <option value="<?= $supplier['supplier_id'] ?>"
                                <?= (($_POST['supplier_id'] ?? '') == $supplier['supplier_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($supplier['supplier_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="batch_no">Batch Number</label>
                    <input type="text" id="batch_no" name="batch_no"
                           required maxlength="100"
                           value="<?= htmlspecialchars($_POST['batch_no'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity in Stock</label>
                    <input type="number" id="quantity" name="quantity"
                           min="0" required
                           value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="unit_price">Unit Price (KSh)</label>
                    <input type="number" id="unit_price" name="unit_price"
                           min="0" step="0.01" required
                           value="<?= htmlspecialchars($_POST['unit_price'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="reorder_level">Reorder Level</label>
                    <input type="number" id="reorder_level" name="reorder_level"
                           min="0" required
                           value="<?= htmlspecialchars($_POST['reorder_level'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="expiry_date">Expiry Date</label>
                    <input type="date" id="expiry_date" name="expiry_date"
                           required
                           value="<?= htmlspecialchars($_POST['expiry_date'] ?? '') ?>">
                </div>

            </div>

            <div class="buttons">
                <button type="submit" class="button">Save Medicine</button>
                <a href="medicine.php" class="button secondary">View Medicines</a>
                <a href="index.php" class="button secondary">Dashboard</a>
            </div>

        </form>
    </section>

</main>

<footer>
    &copy; <?= date("Y") ?> Wanjiru Chemists Stock Management System
</footer>

</body>
</html>