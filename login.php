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

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>

:root {
    --emerald: #064b38;
    --deep: #032f25;
    --green: #087653;
    --sage: #a8c5ad;
    --light-sage: #c7d9c8;
    --gold: #d5b16d;
    --text: #edf5ed;
    --muted: #a9c0b0;
    --field: #174f3e;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    padding: 30px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-family: 'DM Sans', sans-serif;

    background:
        radial-gradient(circle at 10% 15%, #376b50 0, transparent 35%),
        radial-gradient(circle at 90% 85%, #8a774c 0, transparent 25%),
        #092f25;

    overflow-x: hidden;
}

/* BACKGROUND ANIMATION */

.background-circle {
    position: fixed;
    border-radius: 50%;
    pointer-events: none;
    filter: blur(3px);
    animation: float 9s ease-in-out infinite;
}

.circle-one {
    width: 280px;
    height: 280px;
    background: rgba(139, 184, 148, 0.12);
    top: -100px;
    left: -70px;
}

.circle-two {
    width: 350px;
    height: 350px;
    background: rgba(213, 177, 109, 0.10);
    bottom: -150px;
    right: -100px;
    animation-delay: 2s;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-25px);
    }
}

/* MAIN CONTAINER */

.login-container {
    position: relative;
    z-index: 1;

    width: 1160px;
    max-width: 100%;
    min-height: 680px;

    display: grid;
    grid-template-columns: 1fr 1fr;

    background: var(--emerald);

    border: 1px solid rgba(213,177,109,0.25);
    border-radius: 24px;
    overflow: hidden;

    box-shadow: 0 30px 90px rgba(0,0,0,0.4);

    animation: entrance 1s ease both;
}

@keyframes entrance {
    from {
        opacity: 0;
        transform: translateY(25px) scale(0.98);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* IMAGE SIDE */

.picture-side {
    position: relative;
    min-height: 680px;
    overflow: hidden;
    background: #123e2e;
}

.picture-side img {
    width: 100%;
    height: 100%;
    min-height: 680px;
    object-fit: cover;
    display: block;

    transition: transform 9s ease;
}

.picture-side:hover img {
    transform: scale(1.07);
}

.picture-overlay {
    position: absolute;
    inset: 0;

    background: linear-gradient(
        135deg,
        rgba(3,47,37,0.05),
        rgba(3,47,37,0.28)
    );
}

/* FORM SIDE */

.form-side {
    position: relative;
    padding: 48px 58px;

    display: flex;
    flex-direction: column;
    justify-content: center;

    background:
        radial-gradient(
            circle at 100% 0%,
            rgba(213,177,109,0.13),
            transparent 35%
        ),
        linear-gradient(145deg, #14533e, #073b2e 75%);

    overflow: hidden;
}

.form-side::before {
    content: "";
    position: absolute;

    width: 280px;
    height: 280px;

    border: 1px solid rgba(213,177,109,0.12);
    border-radius: 50%;

    right: -150px;
    top: -130px;

    pointer-events: none;
}

.form-side::after {
    content: "";
    position: absolute;

    width: 200px;
    height: 200px;

    border: 1px solid rgba(168,197,173,0.10);
    border-radius: 50%;

    left: -120px;
    bottom: -110px;

    pointer-events: none;
}

/* LOGO */

.logo {
    position: relative;

    font-family: 'Outfit', sans-serif;
    font-size: 27px;
    font-weight: 800;
    letter-spacing: 1px;

    color: #e1c184;
}

.logo span {
    color: #dceadd;
}

.logo-accent {
    width: 48px;
    height: 4px;
    border-radius: 10px;

    background: var(--gold);
    margin-top: 10px;
}

/* HEADING */

.heading {
    margin-top: 38px;
    margin-bottom: 27px;

    animation: slideUp 0.8s 0.15s both;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(18px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.heading h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 36px;

    color: #edf5ed;

    margin: 0 0 10px;
}

.heading p {
    font-size: 13px;
    color: #b5cdbb;
    line-height: 1.7;
    margin: 0;
}

/* ACCOUNT SELECTION */

.account-heading {
    font-size: 10px;
    font-weight: 800;
    color: var(--gold);
    letter-spacing: 1.7px;
    margin-bottom: 12px;
}

.user-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 13px;
    margin-bottom: 25px;
}

.user-card {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;

    padding: 17px 12px;

    border: 1px solid rgba(168,197,173,0.28);
    border-radius: 12px;

    background: rgba(3,47,37,0.55);

    cursor: pointer;

    transition: all 0.3s ease;
}

.user-card:hover {
    border-color: var(--gold);
    background: rgba(168,197,173,0.13);
    transform: translateY(-3px);
}

.user-card:has(input:checked) {
    border: 1.5px solid var(--gold);
    background: rgba(213,177,109,0.13);
    box-shadow: 0 5px 20px rgba(0,0,0,0.12);
}

.user-card input {
    accent-color: var(--gold);
    width: 15px;
    height: 15px;
    margin: 0;
}

.user-card strong {
    color: #e2eee3;
    font-size: 13px;
}

/* FORM FIELDS */

.form-side form > label {
    display: block;
    margin-bottom: 8px;

    color: #c5d9c8;
    font-size: 11px;
    font-weight: 700;
}

.input-wrap {
    position: relative;
    margin-bottom: 18px;
}

.input-wrap input {
    width: 100%;
    padding: 15px;

    border: 1px solid rgba(168,197,173,0.25);
    border-radius: 10px;

    background: #0b4433;
    color: #edf5ed;

    font-family: 'DM Sans', sans-serif;
    font-size: 12px;

    transition: all 0.3s ease;
}

.input-wrap input:focus {
    outline: none;

    border-color: var(--gold);
    background: #104b38;

    box-shadow: 0 0 0 3px rgba(213,177,109,0.13);
}

.input-wrap input::placeholder {
    color: #91ad9a;
}

.password-wrap input {
    padding-right: 65px;
}

/* SHOW PASSWORD */

.show-password {
    position: absolute;
    top: 50%;
    right: 14px;

    transform: translateY(-50%);

    border: none;
    background: none;

    color: var(--gold);
    font-size: 10px;
    font-weight: 800;

    cursor: pointer;
}

/* SIGN IN BUTTON */

.login-button {
    position: relative;
    overflow: hidden;

    width: 100%;
    padding: 16px;
    margin-top: 5px;

    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 10px;

    background: linear-gradient(110deg, #d7b779, #b99451);
    color: #123b2c;

    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 1.3px;

    cursor: pointer;

    box-shadow: 0 8px 22px rgba(0,0,0,0.2);

    transition: all 0.3s ease;
}

.login-button::before {
    content: "";

    position: absolute;
    top: 0;
    left: -100%;

    width: 60%;
    height: 100%;

    background: linear-gradient(
        110deg,
        transparent,
        rgba(255,255,255,0.35),
        transparent
    );

    transition: left 0.6s ease;
}

.login-button:hover::before {
    left: 130%;
}

.login-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 13px 28px rgba(0,0,0,0.25);
}

/* ERROR MESSAGE */

.error {
    padding: 12px 14px;
    margin-bottom: 17px;

    border: 1px solid rgba(255,130,120,0.4);
    border-radius: 9px;

    background: rgba(130,35,35,0.25);
    color: #ffd1cb;

    font-size: 11px;
    line-height: 1.6;
}

/* LINKS */

.help-links {
    display: flex;
    justify-content: space-between;
    gap: 12px;

    margin-top: 21px;

    font-size: 11px;
}

.help-links a {
    color: #d8bd83;
    font-weight: 700;
    text-decoration: none;

    transition: color 0.2s ease;
}

.help-links a:hover {
    color: #ffffff;
}

/* FOOTER */

.bottom-text {
    margin-top: 32px;
    padding-top: 17px;

    border-top: 1px solid rgba(168,197,173,0.2);

    text-align: center;
    color: #91ad9a;

    font-size: 10px;
    letter-spacing: 0.4px;
}

/* RESPONSIVE DESIGN */

@media (max-width: 900px) {

    body {
        padding: 15px;
    }

    .login-container {
        max-width: 550px;
        grid-template-columns: 1fr;
    }

    .picture-side {
        min-height: 260px;
        height: 260px;
    }

    .picture-side img {
        min-height: 260px;
        height: 260px;
    }

    .form-side {
        padding: 40px 35px;
    }

    .heading {
        margin-top: 28px;
    }
}

@media (max-width: 430px) {

    .form-side {
        padding: 32px 22px;
    }

    .heading h2 {
        font-size: 30px;
    }

    .user-options {
        gap: 8px;
    }

    .user-card {
        padding: 14px 7px;
    }
}

/* REDUCED MOTION ACCESSIBILITY */

@media (prefers-reduced-motion: reduce) {

    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}

</style>

</head>

<body>

<div class="background-circle circle-one"></div>
<div class="background-circle circle-two"></div>

<div class="login-container">

    <!-- IMAGE SIDE -->

    <div class="picture-side">

        <img src="pharmacist.jpg" alt="Pharmacist in a pharmacy">

        <div class="picture-overlay"></div>

    </div>


    <!-- LOGIN FORM SIDE -->

    <div class="form-side">

        <div class="logo">
            QR <span>PHARMACIES</span>
        </div>

        <div class="logo-accent"></div>


        <div class="heading">

            <h2>Welcome back.</h2>

            <p>
                Sign in to access your pharmacy management dashboard.
            </p>

        </div>


        <?php if ($error !== ''): ?>

            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <form action="login.php" method="POST">

            <div class="account-heading">
                SELECT YOUR ACCOUNT
            </div>


            <div class="user-options">

                <label class="user-card">

                    <input
                        type="radio"
                        name="role"
                        value="manager"
                        <?= $selectedRole === 'manager' ? 'checked' : '' ?>
                        onchange="changeAccount('manager')"
                    >

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

                    <strong>Clerk</strong>

                </label>

            </div>


            <label for="username">Username</label>

            <div class="input-wrap">

                <input
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
                    placeholder="Enter your username"
                    autocomplete="username"
                    required
                >

            </div>


            <label for="email">Email Address</label>

            <div class="input-wrap">

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email) ?>"
                    placeholder="Enter your registered email"
                    autocomplete="email"
                    required
                >

            </div>


            <label for="password">Password</label>

            <div class="input-wrap password-wrap">

                <input
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
                >SHOW</button>

            </div>


            <button type="submit" class="login-button">
                SIGN IN
            </button>

        </form>


      <div class="help-links">

    <a href="forgot_password.php">Forgot password?</a>

</div>  


        <div class="bottom-text">
            QR PHARMACIES · PHARMACY STOCK MANAGEMENT SYSTEM
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
        button.textContent = 'HIDE';

    } else {

        password.type = 'password';
        button.textContent = 'SHOW';

    }

}

</script>

</body>
</html>