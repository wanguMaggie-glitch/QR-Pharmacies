<?php
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';

// Identify logged-in user's role
$userRole = strtolower($_SESSION['role'] ?? '');
$isManager = ($userRole === 'manager');

// Count total medicines
$medicineResult = $conn->query(
    "SELECT COUNT(*) AS total FROM product"
);
$totalMedicines = $medicineResult
    ? $medicineResult->fetch_assoc()['total']
    : 0;

// Count categories
$categoryResult = $conn->query(
    "SELECT COUNT(*) AS total FROM category"
);
$totalCategories = $categoryResult
    ? $categoryResult->fetch_assoc()['total']
    : 0;

// Count low-stock medicines
$lowStockResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM product
    WHERE quantity <= reorder_level
");
$lowStock = $lowStockResult
    ? $lowStockResult->fetch_assoc()['total']
    : 0;

// Count expired medicines
$expiredResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM product
    WHERE expiry_date IS NOT NULL
    AND expiry_date < CURDATE()
");
$expiredMedicines = $expiredResult
    ? $expiredResult->fetch_assoc()['total']
    : 0;

// User details
$fullName = htmlspecialchars($_SESSION['full_name'] ?? 'User');
$displayRole = htmlspecialchars($_SESSION['role'] ?? 'User');

// Greeting based on time
$hour = (int) date('H');

if ($hour < 12) {
    $greeting = "Good morning";
} elseif ($hour < 17) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | QR PHARMACIES</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: #e8f0eb;
            color: #183b2b;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: #103d2b;
            color: #e8f2eb;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .brand {
            font-size: 23px;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .brand-subtitle {
            color: #a8c8b3;
            font-size: 11px;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .menu-title {
            color: #9dbba7;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 0 0 15px 10px;
        }

        .nav-links {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .nav-links a {
            text-decoration: none;
            color: #d6e6db;
            padding: 13px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            transition: 0.3s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: #286447;
            color: #ffffff;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 30px;
        }

        .user-info {
            border-top: 1px solid #35634a;
            padding-top: 20px;
            margin-bottom: 15px;
        }

        .user-name {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .user-role {
            color: #a8c8b3;
            font-size: 11px;
            text-transform: capitalize;
        }

        .logout {
            display: block;
            text-decoration: none;
            color: #f1c9bd;
            font-size: 13px;
            padding: 12px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .logout:hover {
            background: #583b35;
            color: #ffffff;
        }

        /* Main content */
        .main-content {
            flex: 1;
            padding: 35px;
            min-width: 0;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            gap: 15px;
        }

        .page-heading h2 {
            font-size: 24px;
            font-weight: 700;
            color: #153d2b;
        }

        .page-heading p {
            color: #71877a;
            font-size: 12px;
            margin-top: 7px;
        }

        .date {
            color: #496957;
            font-size: 12px;
            font-weight: 600;
        }

        /* Welcome section */
        .welcome-section {
            background: linear-gradient(120deg, #164c34, #28714d);
            color: #ffffff;
            padding: 32px;
            border-radius: 14px;
            margin-bottom: 30px;
            animation: fadeIn 0.7s ease;
        }

        .welcome-section h1 {
            font-size: 25px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .welcome-section p {
            color: #d2e7d9;
            font-size: 13px;
            line-height: 1.7;
        }

        /* Section headings */
        .section-heading {
            font-size: 17px;
            font-weight: 700;
            color: #194a32;
            margin-bottom: 18px;
        }

        /* Statistics */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: #d4e5d9;
            padding: 23px;
            border-radius: 12px;
            border: 1px solid #c3d9ca;
            transition: 0.3s;
            animation: fadeIn 0.8s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            background: #c9dfd0;
        }

        .stat-card p {
            color: #547361;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 13px;
        }

        .stat-card h2 {
            font-size: 29px;
            font-weight: 800;
            color: #174b32;
        }

        .stat-card small {
            display: block;
            color: #71877a;
            font-size: 11px;
            margin-top: 8px;
        }

        /* Stock reminder */
        .reminder {
            background: #dce8d5;
            border-left: 5px solid #c49a43;
            padding: 20px 23px;
            border-radius: 10px;
            margin-bottom: 32px;
        }

        .reminder h3 {
            font-size: 14px;
            color: #315b3e;
            margin-bottom: 8px;
        }

        .reminder p {
            font-size: 12px;
            line-height: 1.7;
            color: #607766;
        }

        /* Quick access */
        .quick-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 17px;
        }

        .quick-card {
            display: block;
            text-decoration: none;
            background: #d4e5d9;
            border: 1px solid #c3d9ca;
            padding: 23px;
            border-radius: 12px;
            transition: 0.3s;
        }

        .quick-card:hover {
            transform: translateY(-4px);
            background: #c4ddcd;
            border-color: #8eb79b;
        }

        .quick-card h3 {
            color: #194b32;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 9px;
        }

        .quick-card p {
            color: #607766;
            font-size: 11px;
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            text-align: center;
            color: #71877a;
            font-size: 11px;
            margin-top: 40px;
            padding: 20px 0;
            border-top: 1px solid #cbdacf;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive layout */
        @media (max-width: 1050px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .quick-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 750px) {
            .layout {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 22px;
            }

            .brand-subtitle {
                margin-bottom: 25px;
            }

            .nav-links {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
            }

            .sidebar-bottom {
                margin-top: 20px;
            }

            .main-content {
                padding: 22px;
            }

            .top-bar {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .stats-grid,
            .quick-grid {
                grid-template-columns: 1fr;
            }

            .welcome-section {
                padding: 24px;
            }

            .welcome-section h1 {
                font-size: 21px;
            }
        }
    </style>
</head>

<body>

<div class="layout">

    <!-- Sidebar -->
    <aside class="sidebar">

        <div class="brand">QR PHARMACIES</div>
        <div class="brand-subtitle">Pharmacy Stock Management System</div>

        <div class="menu-title">Main Menu</div>

        <nav class="nav-links">

            <a href="index.php" class="active">Dashboard</a>

            <a href="medicine.php">Medicines</a>

            <a href="categories.php">Categories</a>

            <a href="suppliers.php">Suppliers</a>

            <a href="stock_transaction.php">Stock Transactions</a>

            <a href="transaction_history.php">Transaction History</a>

            <a href="alerts.php">Stock Alerts</a>

            <a href="stock_audit.php">Stock Audit</a>

            <a href="ocr.php">Scan Medicine</a>

            <?php if ($isManager): ?>
                <a href="users.php">Manage Users</a>
            <?php endif; ?>

        </nav>

        <div class="sidebar-bottom">

            <div class="user-info">
                <div class="user-name"><?php echo $fullName; ?></div>
                <div class="user-role"><?php echo $displayRole; ?></div>
            </div>

            <a href="logout.php" class="logout">Log Out</a>

        </div>

    </aside>


    <!-- Main dashboard -->
    <main class="main-content">

        <div class="top-bar">

            <div class="page-heading">
                <h2>Dashboard</h2>
                <p>Overview of your pharmacy stock and activities.</p>
            </div>

            <div class="date">
                <?php echo date('l, d F Y'); ?>
            </div>

        </div>


        <!-- Welcome section -->
        <section class="welcome-section">

            <h1>
                <?php echo $greeting; ?>, <?php echo $fullName; ?>!
            </h1>

            <p>
                Welcome to QR PHARMACIES. Manage your medicines,
                monitor stock levels and keep track of pharmacy activities
                from one place.
            </p>

        </section>


        <!-- Statistics -->
        <h3 class="section-heading">Stock Overview</h3>

        <div class="stats-grid">

            <div class="stat-card">
                <p>Total Medicines</p>
                <h2><?php echo $totalMedicines; ?></h2>
                <small>Medicines registered in the system</small>
            </div>

            <div class="stat-card">
                <p>Medicine Categories</p>
                <h2><?php echo $totalCategories; ?></h2>
                <small>Categories available</small>
            </div>

            <div class="stat-card">
                <p>Low-Stock Medicines</p>
                <h2><?php echo $lowStock; ?></h2>
                <small>Medicines requiring restocking</small>
            </div>

            <div class="stat-card">
                <p>Expired Medicines</p>
                <h2><?php echo $expiredMedicines; ?></h2>
                <small>Medicines past their expiry date</small>
            </div>

        </div>


        <!-- Stock reminder -->
        <section class="reminder">

            <h3>Stock Reminder</h3>

            <p>
                You currently have
                <strong><?php echo $lowStock; ?></strong>
                low-stock medicines and
                <strong><?php echo $expiredMedicines; ?></strong>
                expired medicines. Check the Stock Alerts page
                to review items that require attention.
            </p>

        </section>


        <!-- Quick access -->
        <h3 class="section-heading">Quick Access</h3>

        <div class="quick-grid">

            <a href="medicine.php" class="quick-card">
                <h3>Manage Medicines</h3>
                <p>View, add, update and manage medicines in your pharmacy.</p>
            </a>

            <a href="stock_transaction.php" class="quick-card">
                <h3>Stock Transactions</h3>
                <p>Record new stock received and medicines issued.</p>
            </a>

            <a href="alerts.php" class="quick-card">
                <h3>Stock Alerts</h3>
                <p>Check low-stock, expired and soon-to-expire medicines.</p>
            </a>

            <a href="transaction_history.php" class="quick-card">
                <h3>Transaction History</h3>
                <p>Review previous stock activities and transactions.</p>
            </a>

            <a href="stock_audit.php" class="quick-card">
                <h3>Stock Audit</h3>
                <p>Compare recorded stock with the physical stock available.</p>
            </a>

            <a href="ocr.php" class="quick-card">
                <h3>Scan Medicine</h3>
                <p>Use medicine scanning to capture product information.</p>
            </a>

        </div>


        <!-- Footer -->
        <footer class="footer">
            &copy; <?php echo date('Y'); ?> QR PHARMACIES.
            Pharmacy Stock Management System.
        </footer>

    </main>

</div>

</body>
</html>