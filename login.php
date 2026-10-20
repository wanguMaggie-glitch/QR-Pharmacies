<?php
session_start();
require_once 'db_connect.php';

$error = '';
$username = '';
$email = '';
$selectedRole = 'manager';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $selectedRole = $_POST['role'] ?? 'manager';

    if (!in_array($selectedRole, ['manager', 'clerk'], true)) {
        $selectedRole = 'manager';
        $error = "Please select a valid account type.";
    } elseif ($username === '' || $email === '' || $password === '') {
        $error = "Please enter your username, email, and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $sql = "SELECT user_id, full_name, username, email,
                       password_hash, role
                FROM `user`
                WHERE username = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $error = "Unable to process your login. Please try again.";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (!password_verify($password, $user['password_hash'])) {
                    $error = "Incorrect password. Please try again.";
                } elseif (
                    strtolower(trim($user['role'])) !== $selectedRole
                ) {
                    $error = "The selected account does not match this user.";
                } elseif (
                    strcasecmp(trim($user['email'] ?? ''), $email) !== 0
                ) {
                    $error = "The email address does not match this account.";
                } else {
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = strtolower(trim($user['role']));

                    header("Location: index.php");
                    exit();
                }
            } else {
                $error = "Account not found. Please check your username.";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login | QR PHARMACIES</title>

<style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: #edf3f0;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .login-container {
        width: 1000px;
        max-width: 100%;
        min-height: 560px;
        display: flex;
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    /* Left side: pharmacist photograph */
    .picture-side {
        width: 50%;
        min-height: 560px;
        background: #dce9e2;
        overflow: hidden;
    }

    .picture-side img {
        width: 100%;
        height: 100%;
        min-height: 560px;
        object-fit: cover;
        display: block;
    }

    /* Right side: actual login form */
    .form-side {
        width: 50%;
        padding: 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .logo {
        color: #16785b;
        font-size: 27px;
        font-weight: bold;
        letter-spacing: 0.5px;
    }

    h2 {
        margin: 18px 0 8px;
        color: #222;
        font-size: 27px;
    }

    .subtitle {
        color: #777;
        font-size: 14px;
        margin-bottom: 22px;
        line-height: 1.5;
    }

    .user-options {
        display: flex;
        gap: 12px;
        margin-bottom: 22px;
    }

    .user-card {
        flex: 1;
        padding: 15px 8px;
        border: 1px solid #ccc;
        border-radius: 8px;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }

    .user-card:has(input:checked) {
        border: 2px solid #16785b;
        background: #effaf5;
    }

    .user-card input {
        accent-color: #16785b;
        margin: 0;
    }

    .user-card strong {
        font-size: 14px;
    }

    .user-icon {
        font-size: 21px;
    }

    .form-side form > label {
        display: block;
        margin-bottom: 7px;
        font-size: 14px;
        color: #333;
    }

    .form-input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 6px;
        margin-bottom: 16px;
        font-size: 14px;
    }

    .form-input:focus {
        outline: none;
        border-color: #16785b;
    }

    .password-box {
        position: relative;
    }

    .password-box input {
        padding-right: 65px;
    }

    .show-password {
        position: absolute;
        right: 10px;
        top: 9px;
        background: none;
        border: none;
        color: #16785b;
        cursor: pointer;
    }

    .login-button {
        width: 100%;
        padding: 13px;
        background: #16785b;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.2s;
    }

    .login-button:hover {
        background: #105c45;
    }

    .error {
        background: #fff0f0;
        color: #a32121;
        border: 1px solid #e6b5b5;
        padding: 11px;
        border-radius: 6px;
        margin-bottom: 16px;
        font-size: 13px;
    }

    .help-links {
        margin-top: 18px;
        display: flex;
        justify-content: space-between;
        gap: 10px;
        font-size: 13px;
    }

    a {
        color: #16785b;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }

    .bottom-text {
        margin-top: 25px;
        text-align: center;
        font-size: 12px;
        color: #888;
    }

    @media (max-width: 700px) {
        body {
            padding: 12px;
        }

        .login-container {
            flex-direction: column;
        }

        .picture-side {
            width: 100%;
            min-height: 220px;
            height: 220px;
        }

        .picture-side img {
            min-height: 220px;
            height: 220px;
        }

        .form-side {
            width: 100%;
            padding: 30px 22px;
        }
    }
</style>


</head>

<body>

<div class="login-container">

<div class="picture-side">
    <img
        src="pharmacist.jpg"
        alt="Pharmacist in a pharmacy"
    >
</div>

<div class="form-side">

    <div class="logo">QR PHARMACIES</div>

    <h2>Welcome back</h2>

    <p class="subtitle">
        Enter your account details to continue.
    </p>

    <?php if ($error !== ''): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">

        <div class="user-options">

            <label class="user-card">
                <input
                    type="radio"
                    name="role"
                    value="manager"
                    <?= $selectedRole === 'manager' ? 'checked' : '' ?>
                    onchange="changeAccount('manager')"
                >
                <span class="user-icon"></span>
                <strong>Manager</strong>
            </label>

            <label class="user-card">
                <input
                    type="radio"
                    name="role"
                    value="clerk"
                    <?= $selectedRole === 'clerk' ? 'checked' : '' ?>
                    onchange="changeAccount('clerk')"
                >
                <span class="user-icon"></span>
                <strong>Clerk</strong>
            </label>

        </div>

        <label for="username">Username</label>
        <input
            class="form-input"
            type="text"
            id="username"
            name="username"
            value="<?= htmlspecialchars(
                $username !== ''
                    ? $username
                    : ($selectedRole === 'manager'
                        ? 'mwanjiru'
                        : 'jkariuki')
            ) ?>"
            autocomplete="username"
            required
        >

        <label for="email">Email Address</label>
        <input
            class="form-input"
            type="email"
            id="email"
            name="email"
            value="<?= htmlspecialchars($email) ?>"
            placeholder="Enter your registered email"
            autocomplete="email"
            required
        >

        <label for="password">Password</label>

        <div class="password-box">
            <input
                class="form-input"
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                autocomplete="current-password"
                required
            >

            <button
                type="button"
                class="show-password"
                onclick="togglePassword()"
            >Show</button>
        </div>

        <button type="submit" class="login-button">
            Sign In
        </button>

    </form>

    <div class="help-links">
        <a href="forgot_password.php">Forgot password?</a>
        <a href="login.php">Use another account</a>
    </div>

    <div class="bottom-text">
        Pharmacy Stock Management System
    </div>

</div>


</div>

<script>
function changeAccount(role) {
    if (role === 'manager') {
        document.getElementById('username').value = 'mwanjiru';
    } else {
        document.getElementById('username').value = 'jkariuki';
    }

    document.getElementById('email').value = '';
    document.getElementById('password').value = '';
}

function togglePassword() {
    var password = document.getElementById('password');
    var button = document.querySelector('.show-password');

    if (password.type === 'password') {
        password.type = 'text';
        button.textContent = 'Hide';
    } else {
        password.type = 'password';
        button.textContent = 'Show';
    }
}
</script>

</body>
</html>
