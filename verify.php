<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['otp_mobile'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    $mobile = $_SESSION['otp_mobile'];

    if (!preg_match('/^[0-9]{4,9}$/', $otp)) {
        $_SESSION['error'] = 'Please enter a valid OTP.';
        header('Location: verify.php');
        exit;
    }

    if (!isset($_SESSION['otp_attempts'])) {
        $_SESSION['otp_attempts'] = 0;
    }

    if ($_SESSION['otp_attempts'] >= 5) {
        unset($_SESSION['otp_mobile'], $_SESSION['otp_sent_time'], $_SESSION['otp_attempts']);
        $_SESSION['error'] = 'Too many incorrect attempts. Please request a new OTP.';
        header('Location: index.php');
        exit;
    }

    $_SESSION['otp_attempts']++;

    $result = verifyOtp($mobile, $otp);

    if (!$result['success']) {
        $_SESSION['error'] =
            'MSG91 verification failed: ' .
            ($result['message'] ?: 'Invalid or expired OTP.') .
            (isset($result['http_code']) ? ' (HTTP ' . $result['http_code'] . ')' : '');
        header('Location: verify.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
    $stmt->execute([$mobile]);
    $user = $stmt->fetch();

    if (!$user) {
        $stmt = $pdo->prepare('INSERT INTO users (phone, is_verified) VALUES (?, 1)');
        $stmt->execute([$mobile]);
        $userId = (int) $pdo->lastInsertId();
    } else {
        $userId = (int) $user['id'];

        $stmt = $pdo->prepare('UPDATE users SET is_verified = 1 WHERE id = ?');
        $stmt->execute([$userId]);
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = $userId;
    $_SESSION['user_phone'] = $mobile;

    unset($_SESSION['otp_mobile'], $_SESSION['otp_sent_time'], $_SESSION['otp_attempts']);

    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Verify OTP</h2>
        <p class="subtitle">
            OTP sent to <strong>+<?= htmlspecialchars($_SESSION['otp_mobile']) ?></strong>
        </p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form action="verify.php" method="POST">
            <label>Enter OTP</label>
            <input
                type="text"
                name="otp"
                placeholder="Enter OTP"
                maxlength="9"
                pattern="[0-9]{4,9}"
                inputmode="numeric"
                autocomplete="one-time-code"
                required
            >
            <button type="submit">Verify OTP</button>
        </form>

        <div class="resend">
            <form action="resend_otp.php" method="POST">
                <button type="submit" class="secondary">Resend OTP</button>
            </form>
        </div>

        <p class="change-number"><a href="index.php">Change mobile number</a></p>
    </div>
</div>
</body>
</html>
