<?php
session_start();
require_once 'db_connect.php';

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter your username and a valid email address.";
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, username, recovery_email
             FROM `user`
             WHERE username = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = "Unable to process your request. Please try again.";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (
                    !empty($user['recovery_email']) &&
                    strcasecmp(trim($user['recovery_email']), $email) === 0
                ) {
                    /*
                     * Email and OTP delivery will be configured in
                     * the next step. Do not display a fake OTP here.
                     */
                    $message = "Your recovery email matches. Email delivery has not been configured yet.";
                } else {
                    $error = "The details do not match the registered recovery information.";
                }
            } else {
                $error = "The details do not match a registered account.";
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
    <title>Forgot Password | QR PHARMACIES</title>
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
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 430px;
            max-width: 100%;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.12);
        }

        h1 {
            color: #16785b;
            font-size: 25px;
            margin-top: 0;
        }

        p {
            color: #666;
            line-height: 1.6;
            font-size: 14px;
        }

        label {
            display: block;
            margin: 18px 0 7px;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            background: #16785b;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
        }

        button:hover {
            background: #105c45;
        }

        .error, .message {
            padding: 12px;
            border-radius: 6px;
            margin: 15px 0;
            font-size: 14px;
        }

        .error {
            background: #fff0f0;
            color: #a32121;
        }

        .message {
            background: #effaf5;
            color: #16785b;
        }

        a {
            display: inline-block;
            margin-top: 20px;
            color: #16785b;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>QR PHARMACIES</h1>
    <h2>Forgot Password?</h2>

    <p>
        Enter your username and registered recovery email to begin
        recovering your account.
    </p>

    <?php if ($error !== ''): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($message !== ''): ?>
        <div class="message">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="forgot_password.php">
        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            required
            autocomplete="username"
        >

        <label for="email">Recovery Email</label>
        <input
            type="email"
            id="email"
            name="email"
            required
            autocomplete="email"
        >

        <button type="submit">Continue</button>
    </form>

    <a href="login.php">← Back to Login</a>
</div>

</body>
</html>