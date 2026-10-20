<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/mail_config.php';

function sendOTPEmail($recipientEmail, $otp)
{
    global $config;

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $config['port'];

        $mail->setFrom($config['username'], 'QR PHARMACIES');
        $mail->addAddress($recipientEmail);

        $mail->isHTML(true);
        $mail->Subject = 'QR PHARMACIES Password Reset OTP';

        $mail->Body = "
            <h2>QR PHARMACIES</h2>
            <p>You requested to reset your password.</p>
            <p>Your verification code is:</p>
            <h1 style='letter-spacing: 5px;'>$otp</h1>
            <p>This code expires in 10 minutes.</p>
            <p>If you did not request this code, ignore this email.</p>
        ";

        $mail->AltBody =
            "Your QR PHARMACIES password reset code is $otp. " .
            "It expires in 10 minutes.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('OTP email failed: ' . $mail->ErrorInfo);
        return false;
    }
}