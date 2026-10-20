<?php
session_start();
require_once 'db_connect.php';

$error = '';

if (
    !isset($_SESSION['reset_otp_id']) ||
    !isset($_SESSION['reset_user_id']) ||
    !isset($_SESSION['reset_otp_expires'])
) {
    header('Location: forgot_password.php');
    exit;
}

if (time() > $_SESSION['reset_otp_expires']) {
    unset(
        $_SESSION['reset_otp_id'],
        $_SESSION['reset_user_id'],
        $_SESSION['reset_otp_expires']
    );

    $error = 'Your verification code has expired. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {

    $otp = trim($_POST['otp'] ?? '');

    if (!preg_match('/^\d{6}$/', $otp)) {
        $error = 'Enter a valid six-digit verification code.';
    } else {

        $otp_id = (int) $_SESSION['reset_otp_id'];
        $user_id = (int) $_SESSION['reset_user_id'];

        $stmt = $conn->prepare(
            "SELECT otp_hash, expires_at, attempts, used_at
             FROM password_reset_otps
             WHERE id = ? AND user_id = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = 'A system error occurred. Please try again.';
        } else {
            $stmt->bind_param('ii', $otp_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $record = $result->fetch_assoc();
            $stmt->close();

            if (
                !$record ||
                $record['used_at'] !== null ||
                strtotime($record['expires_at']) <= time() ||
                (int) $record['attempts'] >= 5
            ) {
                $error = 'The code is invalid, expired, or has reached its attempt limit. Request a new code.';
            } else {

                // Count every verification attempt.
                $stmt = $conn->prepare(
                    "UPDATE password_reset_otps
                     SET attempts = attempts + 1
                     WHERE id = ?
                       AND user_id = ?
                       AND used_at IS NULL
                       AND attempts < 5"
                );

                if (!$stmt) {
                    $error = 'A system error occurred. Please try again.';
                } else {
                    $stmt->bind_param('ii', $otp_id, $user_id);
                    $stmt->execute();
                    $updated = $stmt->affected_rows;
                    $stmt->close();

                    if ($updated !== 1) {
                        $error = 'The code is invalid or has reached its attempt limit.';
                    } elseif (password_verify($otp, $record['otp_hash'])) {

                        // Mark the OTP as used before proceeding.
                        $stmt = $conn->prepare(
                            "UPDATE password_reset_otps
                             SET used_at = NOW()
                             WHERE id = ?
                               AND user_id = ?
                               AND used_at IS NULL"
                        );

                        if (!$stmt) {
                            $error = 'A system error occurred. Please try again.';
                        } else {
                            $stmt->bind_param('ii', $otp_id, $user_id);
                            $stmt->execute();
                            $used = $stmt->affected_rows;
                            $stmt->close();

                            if ($used === 1) {
                                session_regenerate_id(true);

                                unset(
                                    $_SESSION['reset_otp_id'],
                                    $_SESSION['reset_otp_expires']
                                );

                                $_SESSION['verified_reset_user_id'] = $user_id;
                                $_SESSION['verified_reset_otp_id'] = $otp_id;
                                $_SESSION['verified_reset_expires'] = time() + 600;

                                header('Location: reset_password.php');
                                exit;
                            } else {
                                $error = 'This code has already been used. Request a new one.';
                            }
                        }

                    } else {
                        $error = 'Incorrect verification code. Please try again.';
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

    <title>Verify OTP | QR PHARMACIES</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
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
            color: #087f8c;
            text-align: center;
        }

        p {
            color: #555;
            line-height: 1.6;
        }

        label {
            display: block;
            margin-top: 20px;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #ccd5dc;
            border-radius: 6px;
            font-size: 20px;
            text-align: center;
            letter-spacing: 6px;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            border: none;
            border-radius: 6px;
            background: #087f8c;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #066873;
        }

        .error {
            padding: 12px;
            border-radius: 6px;
            background: #ffe8e8;
            color: #a40000;
            line-height: 1.5;
        }

        a {
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
        <h1>Verify Your Code</h1>

        <p>
            Enter the six-digit OTP sent to the shared recovery email.
            The code expires after 10 minutes.
        </p>

        <?php if ($error !== ''): ?>
            <div class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['reset_otp_id'])): ?>
            <form method="POST" action="">
                <label for="otp">Six-Digit OTP</label>

                <input
                    type="text"
                    id="otp"
                    name="otp"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    minlength="6"
                    maxlength="6"
                    autocomplete="one-time-code"
                    placeholder="000000"
                    required
                >

                <button type="submit">Verify Code</button>
            </form>
        <?php endif; ?>

        <a href="forgot_password.php">Request a New Code</a>
        <a href="login.php">Back to Login</a>
    </div>
</body>
</html>