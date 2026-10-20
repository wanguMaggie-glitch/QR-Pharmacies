

<?php
session_start();
require_once 'db_connect.php';

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| Verify the password-reset session
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['verified_reset_user_id']) ||
    !isset($_SESSION['verified_reset_otp_id']) ||
    !isset($_SESSION['verified_reset_expires'])
) {
    header('Location: forgot_password.php');
    exit;
}

if (time() > (int) $_SESSION['verified_reset_expires']) {

    unset(
        $_SESSION['verified_reset_user_id'],
        $_SESSION['verified_reset_otp_id'],
        $_SESSION['verified_reset_expires']
    );

    header('Location: forgot_password.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Process the new password
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {

        $error = 'Your new password must contain at least 8 characters.';

    } elseif ($password !== $confirm_password) {

        $error = 'The passwords do not match.';

    } else {

        $user_id = (int) $_SESSION['verified_reset_user_id'];
        $otp_id = (int) $_SESSION['verified_reset_otp_id'];

        // Generate a secure hash for the new password.
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        if ($password_hash === false) {

            $error = 'Could not process the new password. Please try again.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | Confirm that the OTP was used and belongs to this user
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare(
                "SELECT id, expires_at
                 FROM password_reset_otps
                 WHERE id = ?
                   AND user_id = ?
                   AND used_at IS NOT NULL
                 LIMIT 1"
            );

            if (!$stmt) {

                $error = 'A database error occurred. Please try again.';

            } else {

                $stmt->bind_param('ii', $otp_id, $user_id);
                $stmt->execute();

                $result = $stmt->get_result();
                $valid_reset = $result->fetch_assoc();

                $stmt->close();

                // Check OTP existence and expiry using PHP's clock.
                if (
                    !$valid_reset ||
                    strtotime($valid_reset['expires_at']) <= time()
                ) {

                    $error = 'Your verification session is invalid or expired. Please request a new code.';

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Update the user's password
                    |--------------------------------------------------------------------------
                    */

                    $stmt = $conn->prepare(
                        "UPDATE user
                         SET password_hash = ?
                         WHERE user_id = ?"
                    );

                    if (!$stmt) {

                        $error = 'A database error occurred. Please try again.';

                    } else {

                        $stmt->bind_param(
                            'si',
                            $password_hash,
                            $user_id
                        );

                        if ($stmt->execute()) {

                            if ($stmt->affected_rows === 1) {

                                $stmt->close();

                                // Remove password-reset session information.
                                unset(
                                    $_SESSION['verified_reset_user_id'],
                                    $_SESSION['verified_reset_otp_id'],
                                    $_SESSION['verified_reset_expires'],
                                    $_SESSION['reset_otp_id'],
                                    $_SESSION['reset_user_id'],
                                    $_SESSION['reset_otp_expires']
                                );

                                session_regenerate_id(true);

                                $success = 'Your password has been reset successfully. You can now log in using your new password.';

                            } else {

                                $stmt->close();

                                // Check whether the user still exists.
                                $check = $conn->prepare(
                                    "SELECT user_id
                                     FROM user
                                     WHERE user_id = ?
                                     LIMIT 1"
                                );

                                if ($check) {

                                    $check->bind_param('i', $user_id);
                                    $check->execute();

                                    $check_result = $check->get_result();
                                    $user_exists = $check_result->fetch_assoc();

                                    $check->close();

                                    if ($user_exists) {

                                        // The password may already have had
                                        // the same hash value stored.
                                        $success = 'Your password has been updated. You can now log in using your new password.';

                                        unset(
                                            $_SESSION['verified_reset_user_id'],
                                            $_SESSION['verified_reset_otp_id'],
                                            $_SESSION['verified_reset_expires'],
                                            $_SESSION['reset_otp_id'],
                                            $_SESSION['reset_user_id'],
                                            $_SESSION['reset_otp_expires']
                                        );

                                        session_regenerate_id(true);

                                    } else {

                                        $error = 'The account could not be found. Please contact your system manager.';
                                    }

                                } else {

                                    $error = 'Could not verify the account. Please try again.';
                                }
                            }

                        } else {

                            $stmt->close();

                            $error = 'The password could not be updated. Please try again.';
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reset Password | QR PHARMACIES</title>

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
            color: #087f8c;
            text-align: center;
            margin-top: 0;
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
            background: #087f8c;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #066873;
        }

        .message {
            margin-top: 15px;
            padding: 12px;
            border-radius: 6px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .error {
            color: #a40000;
            background: #ffe8e8;
        }

        .success {
            color: #155724;
            background: #d4edda;
        }

        .login-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #087f8c;
            text-decoration: none;
        }

        .login-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="container">

        <h1>Reset Password</h1>

        <p>
            Choose a new password for your QR PHARMACIES account.
            Your new password must contain at least eight characters.
        </p>

        <?php if ($error !== ''): ?>

            <div class="message error">
                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

        <?php endif; ?>

        <?php if ($success !== ''): ?>

            <div class="message success">
                <?= htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

            <a class="login-link" href="login.php">
                Return to Login
            </a>

        <?php else: ?>

            <form method="POST" action="">

                <label for="password">
                    New Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >

                <label for="confirm_password">
                    Confirm New Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >

                <button type="submit">
                    Reset Password
                </button>

            </form>

        <?php endif; ?>

    </div>

</body>

</html>

