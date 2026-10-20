
<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid medicine ID.");
}

$product_id = (int) $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $drug_name = trim($_POST['drug_name']);
    $category_id = (int) $_POST['category_id'];
    $supplier_id = (int) $_POST['supplier_id'];
    $batch_no = trim($_POST['batch_no']);
    $quantity = (int) $_POST['quantity'];
    $unit_price = (float) $_POST['unit_price'];
    $reorder_level = (int) $_POST['reorder_level'];
    $expiry_date = $_POST['expiry_date'];

    $sql = "UPDATE product
            SET drug_name = ?,
                category_id = ?,
                supplier_id = ?,
                batch_no = ?,
                quantity = ?,
                unit_price = ?,
                reorder_level = ?,
                expiry_date = ?
            WHERE product_id = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error preparing update: " . $conn->error);
    }

    $stmt->bind_param(
        "siisidisi",
        $drug_name,
        $category_id,
        $supplier_id,
        $batch_no,
        $quantity,
        $unit_price,
        $reorder_level,
        $expiry_date,
        $product_id
    );

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: medicine.php?updated=1");
        exit;
    } else {
        echo "Error updating medicine: " . $stmt->error;
    }

    $stmt->close();
}

$sql = "SELECT * FROM product WHERE product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();
$medicine = $result->fetch_assoc();
$stmt->close();

if (!$medicine) {
    die("Medicine not found.");
}

$categories = $conn->query("SELECT category_id FROM category");
$suppliers = $conn->query("SELECT supplier_id FROM supplier");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Medicine</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f5f7;
            padding: 30px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px #ccc;
        }

        h2 {
            color: #176b50;
            text-align: center;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            background: #176b50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #10523c;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Medicine</h2>

    <form method="POST">
        <label>Drug Name</label>
        <input type="text" name="drug_name"
               value="<?php echo htmlspecialchars($medicine['drug_name']); ?>"
               required>

        <label>Category ID</label>
        <select name="category_id" required>
            <?php while ($row = $categories->fetch_assoc()): ?>
                <option value="<?php echo $row['category_id']; ?>"
                    <?php echo ($row['category_id'] == $medicine['category_id']) ? 'selected' : ''; ?>>
                    Category <?php echo $row['category_id']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Supplier ID</label>
        <select name="supplier_id" required>
            <?php while ($row = $suppliers->fetch_assoc()): ?>
                <option value="<?php echo $row['supplier_id']; ?>"
                    <?php echo ($row['supplier_id'] == $medicine['supplier_id']) ? 'selected' : ''; ?>>
                    Supplier <?php echo $row['supplier_id']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Batch Number</label>
        <input type="text" name="batch_no"
               value="<?php echo htmlspecialchars($medicine['batch_no']); ?>"
               required>

        <label>Quantity</label>
        <input type="number" name="quantity" min="0"
               value="<?php echo $medicine['quantity']; ?>" required>

        <label>Unit Price</label>
        <input type="number" name="unit_price" min="0" step="0.01"
               value="<?php echo $medicine['unit_price']; ?>" required>

        <label>Reorder Level</label>
        <input type="number" name="reorder_level" min="0"
               value="<?php echo $medicine['reorder_level']; ?>" required>

        <label>Expiry Date</label>
        <input type="date" name="expiry_date"
               value="<?php echo $medicine['expiry_date']; ?>" required>

        <button type="submit">Update Medicine</button>
    </form>

    <a class="back" href="medicine.php">Back to Medicine Inventory</a>
</div>

</body>
</html>


