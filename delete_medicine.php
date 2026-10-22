
<?php
session_start();

// Check whether the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Only the manager can delete medicines
if (
    !isset($_SESSION['role']) ||
    strtolower($_SESSION['role']) !== 'manager'
) {
    http_response_code(403);
    die("Access denied. Only the manager can delete medicines.");
}

require_once 'db_connect.php';

// Allow deletion only through a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request. Please use the Delete button in the inventory.");
}

// Validate the medicine ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    die("Invalid medicine ID.");
}

$product_id = (int) $_POST['id'];

// Delete the medicine
$sql = "DELETE FROM product WHERE product_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Unable to prepare deletion: " . $conn->error);
}

$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        header("Location: medicine.php?deleted=1");
        exit;
    }

    $stmt->close();
    die("Medicine not found.");
}

$error = $stmt->error;
$stmt->close();

if (stripos($error, 'foreign key constraint') !== false) {
    die("This medicine cannot be deleted because other records depend on it. Preserve its history and update its stock status instead.");
}

die("Unable to delete medicine: " . htmlspecialchars($error));
?>

