<?php

require_once __DIR__ . '/config.php';


// Already logged in
if (isset($_SESSION['user_id'])) {

    header('Location: dashboard.php');

    exit;
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

    <title>Login with OTP</title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

</head>

<body>


<div class="container">

    <div class="card">

        <h2>Login / Signup</h2>

        <p class="subtitle">
            Enter your mobile number to continue.
        </p>


        <?php if (isset($_SESSION['error'])): ?>

            <div class="error">

                <?= htmlspecialchars(
                    $_SESSION['error']
                ) ?>

            </div>

            <?php unset($_SESSION['error']); ?>

        <?php endif; ?>


        <form
            action="send_otp.php"
            method="POST"
        >

            <label>
                Mobile Number
            </label>


            <div class="phone-input">

                <span>+91</span>

                <input
                    type="text"
                    name="phone"
                    placeholder="9876543210"
                    maxlength="10"
                    pattern="[0-9]{10}"
                    inputmode="numeric"
                    required
                >

            </div>


            <button type="submit">
                Send OTP
            </button>

        </form>

    </div>

</div>


</body>

</html>