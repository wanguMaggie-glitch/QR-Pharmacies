
<?php
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Only the manager can access this page
if (
    !isset($_SESSION['role']) ||
    strtolower($_SESSION['role']) !== 'manager'
) {
    http_response_code(403);
    die("Access denied. Only the manager can manage user accounts.");
}

require_once 'db_connect.php';

$message = '';
$error = '';

// Register a new user
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['add_user'])) {

    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = strtolower(trim($_POST['role'] ?? ''));
    $recovery_email = trim($_POST['recovery_email'] ?? '');

    if (
        $full_name === '' ||
        $username === '' ||
        $password === '' ||
        $recovery_email === '' ||
        !in_array($role, ['manager', 'clerk'], true)
    ) {
        $error = "Please fill in all fields and select a valid role.";
    } elseif (!filter_var($recovery_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid recovery email address.";
    } elseif (strlen($password) < 8) {
        $error = "The password must contain at least 8 characters.";
    } else {

        // Check whether the username already exists
        $check = $conn->prepare(
            "SELECT user_id FROM user WHERE username = ? LIMIT 1"
        );

        if (!$check) {
            $error = "Unable to check the username. Please try again.";
        } else {
            $check->bind_param("s", $username);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $error = "That username already exists. Choose another.";
            } else {

                // Hash the password before storing it
                $password_hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Save the new user and recovery email
                $stmt = $conn->prepare(
                    "INSERT INTO user
                    (full_name, username, password_hash, role, recovery_email)
                    VALUES (?, ?, ?, ?, ?)"
                );

                if (!$stmt) {
                    $error = "Unable to prepare user registration.";
                } else {
                    $stmt->bind_param(
                        "sssss",
                        $full_name,
                        $username,
                        $password_hash,
                        $role,
                        $recovery_email
                    );

                    if ($stmt->execute()) {
                        $stmt->close();
                        $check->close();

                        header("Location: users.php?success=added");
                        exit();
                    } else {
                        $error = "Unable to register the user. Please try again.";
                    }

                    $stmt->close();
                }
            }

            $check->close();
        }
    }
}

// Display success message
if (isset($_GET['success']) && $_GET['success'] === 'added') {
    $message = "User registered successfully!";
}

// Retrieve registered users
$users = $conn->query(
    "SELECT user_id, full_name, username, recovery_email, role, created_at
     FROM user
     ORDER BY user_id DESC"
);

if (!$users) {
    die("Error retrieving users: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Users | QR PHARMACIES</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f2f6f5;
            color: #243b35;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #087f5b;
            text-decoration: none;
            font-weight: bold;
        }

        .header {
            background: linear-gradient(135deg, #087f5b, #064b36);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .header h1 {
            margin: 0 0 8px;
        }

        .header p {
            margin: 0;
        }

        .card {
            background: white;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .card h2 {
            margin-top: 0;
            color: #087f5b;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccd8d3;
            border-radius: 6px;
            font-size: 15px;
        }

        input:focus, select:focus {
            outline: 2px solid #087f5b;
            border-color: transparent;
        }

        .add-button {
            margin-top: 20px;
            border: none;
            padding: 12px 18px;
            border-radius: 6px;
            background: #087f5b;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .add-button:hover {
            background: #066647;
        }

        .message, .error {
            padding: 13px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .message {
            background: #d3f9d8;
            color: #216e39;
        }

        .error {
            background: #ffe3e3;
            color: #a61e1e;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #e5ece8;
        }

        th {
            background: #e6f4ef;
            color: #087f5b;
        }

        tr:hover {
            background: #f8fbf9;
        }

        .role-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #e1f3e8;
            color: #087f5b;
            font-weight: bold;
            text-transform: capitalize;
        }

        .empty {
            text-align: center;
            padding: 25px;
            color: #687a73;
        }

        @media (max-width: 650px) {
            body {
                padding: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <a class="back-link" href="index.php">
        &larr; Back to Dashboard
    </a>

    <div class="header">
        <h1>QR PHARMACIES</h1>
        <p>User Account Management</p>
    </div>

    <?php if ($message !== ''): ?>
        <div class="message">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card">

        <h2>Register New User</h2>

        <form method="POST" action="users.php">

            <div class="form-grid">

                <div>
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name"
                           name="full_name" required>
                </div>

                <div>
                    <label for="username">Username</label>
                    <input type="text" id="username"
                           name="username" required>
                </div>

                <div>
                    <label for="recovery_email">Recovery Email</label>
                    <input type="email" id="recovery_email"
                           name="recovery_email"
                           placeholder="user@example.com" required>
                </div>

                <div>
                    <label for="password">Password</label>
                    <input type="password" id="password"
                           name="password" minlength="8"
                           autocomplete="new-password" required>
                </div>

                <div>
                    <label for="role">User Role</label>
                    <select id="role" name="role" required>
                        <option value="">Select role</option>
                        <option value="manager">Manager</option>
                        <option value="clerk">Clerk</option>
                    </select>
                </div>

            </div>

            <button type="submit" name="add_user" class="add-button">
                Register User
            </button>

        </form>

    </div>

    <div class="card">

        <h2>Registered Users</h2>

        <div class="table-wrapper">

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Recovery Email</th>
                        <th>Role</th>
                        <th>Date Created</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($users->num_rows > 0): ?>

                    <?php while ($row = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int) $row['user_id'] ?></td>

                            <td>
                                <?= htmlspecialchars($row['full_name'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['username'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['recovery_email'] ?? '') ?>
                            </td>

                            <td>
                                <span class="role-badge">
                                    <?= htmlspecialchars($row['role'] ?? '') ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['created_at'] ?? '') ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="empty">
                            No users have been registered.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>

        </div>

    </div>

</div>

</body>
</html>

