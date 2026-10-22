
<?php
session_start();
require_once 'db_connect.php';
require_once 'send_otp.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $recovery_email = strtolower(trim($_POST['recovery_email'] ?? ''));

    if ($username === '' || $recovery_email === '') {
        $error = 'Please enter your username and recovery email.';

    } elseif (!filter_var($recovery_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';

    } else {

        // Find the user and their saved recovery email.
        $stmt = $conn->prepare(
            "SELECT user_id, username, recovery_email
             FROM `user`
             WHERE username = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = 'A system error occurred. Please try again.';

        } else {
            $stmt->bind_param('s', $username);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $stmt->close();

            if (!$user) {
                $error = 'The username or recovery email does not match.';

            } elseif (
                empty($user['recovery_email']) ||
                strtolower(trim($user['recovery_email'])) !== $recovery_email
            ) {
                $error = 'The recovery email does not match.';

            } else {

                // Invalidate previous unused OTPs for this account.
                $stmt = $conn->prepare(
                    "UPDATE password_reset_otps
                     SET used_at = NOW()
                     WHERE user_id = ? AND used_at IS NULL"
                );

                if (!$stmt) {
                    $error = 'A system error occurred. Please try again.';

                } else {
                    $stmt->bind_param('i', $user['user_id']);
                    $stmt->execute();
                    $stmt->close();

                    // Generate a secure six-digit verification code.
                    $otp = (string) random_int(100000, 999999);
                    $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
                    $expires_at = date(
                        'Y-m-d H:i:s',
                        time() + 600
                    );

                    $stmt = $conn->prepare(
                        "INSERT INTO password_reset_otps
                         (user_id, otp_hash, expires_at)
                         VALUES (?, ?, ?)"
                    );

                    if (!$stmt) {
                        $error = 'A system error occurred. Please try again.';

                    } else {
                        $stmt->bind_param(
                            'iss',
                            $user['user_id'],
                            $otp_hash,
                            $expires_at
                        );

                        if (!$stmt->execute()) {
                            $error = 'Could not create the verification code.';
                            $stmt->close();

                        } else {
                            $otp_id = $conn->insert_id;
                            $stmt->close();

                            // Send the code to this user's saved email.
                            if (sendOTPEmail($user['recovery_email'], $otp)) {

                                $_SESSION['reset_otp_id'] = $otp_id;
                                $_SESSION['reset_user_id'] =
                                    (int) $user['user_id'];
                                $_SESSION['reset_otp_expires'] =
                                    time() + 600;

                                header('Location: verify_otp.php');
                                exit();

                            } else {

                                // Invalidate the OTP if sending fails.
                                $stmt = $conn->prepare(
                                    "UPDATE password_reset_otps
                                     SET used_at = NOW()
                                     WHERE id = ?"
                                );

                                if ($stmt) {
                                    $stmt->bind_param('i', $otp_id);
                                    $stmt->execute();
                                    $stmt->close();
                                }

                                $error = 'The email could not be sent. Check the mail configuration and try again.';
                            }
                        }
                    }
                }
            }
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #eef4f7;
        }

        .container {
            width: 100%;
            max-width: 430px;
            padding: 32px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 22px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin-top: 0;
            color: #087f8c;
            text-align: center;
        }

        p {
            color: #555;
            line-height: 1.6;
        }

        label {
            display: block;
            margin-top: 18px;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccd5dc;
            border-radius: 6px;
            font-size: 15px;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            border: none;
            border-radius: 6px;
            color: white;
            background: #087f8c;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #066873;
        }

        .message {
            padding: 12px;
            border-radius: 6px;
            margin-top: 15px;
            overflow-wrap: anywhere;
        }

        .error {
            background: #ffe8e8;
            color: #a40000;
        }

        .back {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #087f8c;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>QR PHARMACIES</h1>

    <p>
        Enter your username and the recovery email registered
        to your account. We will send you a verification code
        that expires in 10 minutes.
    </p>

    <?php if ($error !== ''): ?>
        <div class="message error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">

        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            required
            autocomplete="username"
            value="<?= htmlspecialchars(
                $_POST['username'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <label for="recovery_email">Recovery Email</label>
        <input
            type="email"
            id="recovery_email"
            name="recovery_email"
            required
            autocomplete="email"
            value="<?= htmlspecialchars(
                $_POST['recovery_email'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <button type="submit">Send Verification Code</button>

    </form>

    <a class="back" href="login.php">Back to Login</a>

</div>

</body>
</html>

