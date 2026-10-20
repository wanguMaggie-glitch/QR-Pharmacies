<?php
session_start();
require_once 'db_connect.php';
require_once __DIR__ . '/vendor/autoload.php';

use thiagoalessio\TesseractOCR\TesseractOCR;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';
$extractedText = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['medicine_image'])) {

    if ($_FILES['medicine_image']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please select an image and try again.";
    } else {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($_FILES['medicine_image']['tmp_name']);

        if (!in_array($mimeType, $allowedTypes, true)) {
            $error = "Please upload a JPG, PNG, or WEBP image.";
        } else {
            $imagePath = $_FILES['medicine_image']['tmp_name'];

            try {
                $ocr = new TesseractOCR($imagePath);
                $ocr->executable('C:\Program Files\Tesseract-OCR\tesseract.exe');
                $ocr->lang('eng');

                $extractedText = trim($ocr->run());

                if ($extractedText === '') {
                    $error = "No text was detected. Try a clearer image.";
                } else {
                    $message = "Text extracted successfully! Review the results below.";
                }
            } catch (Throwable $e) {
                $error = "OCR failed. Check that Tesseract and English language data are installed.";
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
    <title>Medicine OCR | QR PHARMACIES</title>

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
            max-width: 900px;
            margin: auto;
        }

        .header {
            background: #087f5b;
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .header h1 {
            margin: 0 0 8px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }

        h2 {
            color: #087f5b;
            margin-top: 0;
        }

        input[type="file"] {
            width: 100%;
            padding: 14px;
            margin: 15px 0;
            border: 1px solid #ccd8d3;
            border-radius: 6px;
            background: #fff;
        }

        button {
            background: #087f5b;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
        }

        button:hover {
            background: #066647;
        }

        .message {
            background: #d3f9d8;
            color: #216e39;
            padding: 14px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .error {
            background: #ffe3e3;
            color: #a61e1e;
            padding: 14px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        textarea {
            width: 100%;
            min-height: 250px;
            padding: 15px;
            border: 1px solid #ccd8d3;
            border-radius: 6px;
            font-size: 15px;
            line-height: 1.6;
            resize: vertical;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #087f5b;
            text-decoration: none;
            font-weight: bold;
        }

        .help-text {
            color: #687a73;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            body {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <a class="back-link" href="index.php">&larr; Back to Dashboard</a>

    <div class="header">
        <h1>QR PHARMACIES</h1>
        <p>Optical Character Recognition (OCR)</p>
    </div>

    <div class="card">
        <h2>Scan Medicine Information</h2>

        <p>
            Take a photograph of a medicine label or delivery note,
            or upload an existing image to extract its text.
        </p>

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

        <form method="POST" enctype="multipart/form-data">

            <label for="medicine_image">
                Choose an image or take a photograph:
            </label>

            <input
                type="file"
                id="medicine_image"
                name="medicine_image"
                accept="image/jpeg,image/png,image/webp"
                capture="environment"
                required
            >

            <p class="help-text">
                Supported formats: JPG, PNG and WEBP.
            </p>

            <button type="submit">Extract Text</button>

        </form>
    </div>

    <?php if ($extractedText !== ''): ?>
        <div class="card">
            <h2>Extracted Text</h2>

            <p>
                Review the text below. OCR may make mistakes, so verify
                medicine names, batch numbers, quantities and expiry dates.
            </p>

            <textarea readonly><?= htmlspecialchars($extractedText) ?></textarea>
        </div>
    <?php endif; ?>

</div>

</body>
</html>