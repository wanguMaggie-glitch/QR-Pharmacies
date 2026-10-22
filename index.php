
<?php
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';

// Identify the logged-in user's role
$userRole = strtolower($_SESSION['role'] ?? '');
$isManager = ($userRole === 'manager');

// Count all medicines
$medicineQuery = "SELECT COUNT(*) AS total FROM product";
$medicineResult = $conn->query($medicineQuery);

$totalMedicines = $medicineResult
    ? $medicineResult->fetch_assoc()['total']
    : 0;

// Count all categories
$categoryQuery = "SELECT COUNT(*) AS total FROM category";
$categoryResult = $conn->query($categoryQuery);

$totalCategories = $categoryResult
    ? $categoryResult->fetch_assoc()['total']
    : 0;

// Count medicines that need restocking
$lowStockQuery = "
    SELECT COUNT(*) AS total
    FROM product
    WHERE quantity <= reorder_level
";

$lowStockResult = $conn->query($lowStockQuery);

$lowStock = $lowStockResult
    ? $lowStockResult->fetch_assoc()['total']
    : 0;

// Count expired medicines
$expiredQuery = "
    SELECT COUNT(*) AS total
    FROM product
    WHERE expiry_date IS NOT NULL
    AND expiry_date < CURDATE()
";

$expiredResult = $conn->query($expiredQuery);

$expiredMedicines = $expiredResult
    ? $expiredResult->fetch_assoc()['total']
    : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>QR PHARMACIES | Dashboard</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f3f7f4;
            color: #26352c;
            min-height: 100vh;
        }

        /* Dashboard heading */
        .header {
            background: linear-gradient(135deg, #176b45, #104d32);
            color: white;
            padding: 30px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .header-text {
            flex: 1;
            text-align: left;
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .header p {
            font-size: 16px;
            color: #e0f2e8;
        }

        /* Pharmacist picture */
        .header-image {
            width: 150px;
            height: 110px;
            object-fit: cover;
            border-radius: 12px;
            border: 3px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            flex-shrink: 0;
        }

        /* User information */
        .user-bar {
            background-color: white;
            padding: 15px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid #e0e8e2;
        }

        .user-bar p {
            font-size: 14px;
            color: #46574b;
        }

        .role {
            background-color: #e1f3e8;
            color: #176b45;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: capitalize;
        }

        /* Main dashboard content */
        .container {
            width: 86%;
            max-width: 1200px;
            margin: 35px auto;
        }

        .welcome {
            margin-bottom: 25px;
        }

        .welcome h2 {
            color: #176b45;
            font-size: 26px;
            margin-bottom: 8px;
        }

        .welcome p {
            color: #69776d;
            line-height: 1.6;
        }

        /* Statistics cards */
        .cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .card {
            background-color: white;
            padding: 25px 20px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
            border-left: 5px solid #176b45;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 7px 18px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            font-size: 15px;
            color: #68766c;
            margin-bottom: 15px;
        }

        .number {
            font-size: 34px;
            font-weight: bold;
            color: #176b45;
        }

        .card:nth-child(2) {
            border-left-color: #2980b9;
        }

        .card:nth-child(2) .number {
            color: #2980b9;
        }

        .card:nth-child(3) {
            border-left-color: #e39a23;
        }

        .card:nth-child(3) .number {
            color: #d88a12;
        }

        .card:nth-child(4) {
            border-left-color: #d9534f;
        }

        .card:nth-child(4) .number {
            color: #d9534f;
        }

        /* Navigation links */
        .navigation {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .navigation a {
            display: block;
            text-align: center;
            text-decoration: none;
            background-color: white;
            color: #176b45;
            padding: 18px 12px;
            border: 1px solid #dce9df;
            border-radius: 9px;
            font-weight: bold;
            transition: background-color 0.2s, color 0.2s;
        }

        .navigation a:hover {
            background-color: #176b45;
            color: white;
        }

        .navigation a:last-child {
            color: #c0392b;
            border-color: #f0d7d5;
        }

        .navigation a:last-child:hover {
            background-color: #c0392b;
            color: white;
        }

        /* Manager link */
        .navigation .manager-link {
            background-color: #e1f3e8;
            border-color: #b5dfc5;
        }

        .navigation .manager-link:hover {
            background-color: #176b45;
            color: white;
        }

        /* Stock reminder */
        .notice {
            padding: 20px;
            background-color: #e4f4e9;
            border-left: 5px solid #176b45;
            border-radius: 8px;
            line-height: 1.7;
            margin-bottom: 30px;
        }

        .notice strong {
            color: #104d32;
        }

        .notice.warning {
            background-color: #fff3d6;
            border-left-color: #e39a23;
        }

        .notice.warning strong {
            color: #9a6208;
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 25px 15px;
            background-color: #104d32;
            color: #e0f2e8;
            font-size: 14px;
            margin-top: 40px;
        }

        /* Responsive layout */
        @media (max-width: 900px) {
            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .navigation {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .container {
                width: 90%;
            }
        }

        @media (max-width: 600px) {
            .header {
                padding: 22px 5%;
                gap: 12px;
            }

            .header h1 {
                font-size: 22px;
            }

            .header p {
                font-size: 13px;
            }

            .header-image {
                width: 90px;
                height: 80px;
            }

            .user-bar {
                padding: 15px 5%;
                flex-wrap: wrap;
            }

            .cards {
                grid-template-columns: 1fr;
            }

            .navigation {
                grid-template-columns: 1fr;
            }

            .container {
                width: 90%;
                margin: 25px auto;
            }

            .welcome h2 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

    <!-- Dashboard heading and pharmacist picture -->
    <header class="header">

        <div class="header-text">
            <h1>QR PHARMACIES</h1>
            <p>Pharmacy Stock Management System</p>
        </div>

        <img
            src="pharmacist.jpg"
            alt="Pharmacist in a pharmacy"
            class="header-image"
            onerror="this.style.display='none';"
        >

    </header>

    <!-- Logged-in user details -->
    <div class="user-bar">

        <p>
            Welcome,
            <strong>
                <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>!
            </strong>
        </p>

        <p>
            Your role:
            <span class="role">
                <?= htmlspecialchars($_SESSION['role'] ?? 'User') ?>
            </span>
        </p>

    </div>

    <!-- Main dashboard -->
    <main class="container">

        <section class="welcome">
            <h2>Dashboard</h2>

            <p>
                Welcome to QR PHARMACIES. Here is an overview
                of your pharmacy stock.
            </p>
        </section>

        <!-- Dashboard statistics -->
        <section class="cards">

            <div class="card">
                <h3>Total Medicines</h3>
                <p class="number">
                    <?= (int) $totalMedicines ?>
                </p>
            </div>

            <div class="card">
                <h3>Medicine Categories</h3>
                <p class="number">
                    <?= (int) $totalCategories ?>
                </p>
            </div>

            <div class="card">
                <h3>Low-Stock Medicines</h3>
                <p class="number">
                    <?= (int) $lowStock ?>
                </p>
            </div>

            <div class="card">
                <h3>Expired Medicines</h3>
                <p class="number">
                    <?= (int) $expiredMedicines ?>
                </p>
            </div>

        </section>

        <!-- Dashboard navigation -->
        <nav class="navigation">

            <a href="medicine.php">View Medicines</a>

            <a href="categories.php">Manage Categories</a>

            <a href="suppliers.php">Manage Suppliers</a>

            <a href="stock_transaction.php">Stock Transactions</a>

            <a href="ocr.php">OCR - Scan Medicine</a>

            <?php if ($isManager): ?>
                <a href="users.php" class="manager-link">
                    Manage Users
                </a>
            <?php endif; ?>

            <a href="logout.php">Log Out</a>

        </nav>

        <!-- Stock reminder -->
        <div class="notice <?= $lowStock > 0 ? 'warning' : '' ?>">

            <strong>Stock Reminder:</strong>

            <?php
            if ($lowStock > 0) {
                echo "You have " . (int) $lowStock .
                    " medicine(s) that need restocking.";
            } else {
                echo "No medicines currently require restocking.";
            }
            ?>

        </div>

    </main>

    <!-- Footer -->
    <footer>
        &copy; <?= date("Y") ?>
        QR PHARMACIES Stock Management System
    </footer>

</body>
</html>


