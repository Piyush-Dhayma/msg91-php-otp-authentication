<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$phone = trim($_POST['phone'] ?? '');
$mobile = cleanPhone($phone);

if (!$mobile) {
    $_SESSION['error'] = 'Please enter a valid 10 digit Indian mobile number.';
    header('Location: index.php');
    exit;
}

if (
    isset($_SESSION['otp_sent_time']) &&
    time() - (int)$_SESSION['otp_sent_time'] < 30
) {
    $_SESSION['error'] = 'Please wait 30 seconds before requesting another OTP.';
    header('Location: index.php');
    exit;
}

$result = sendOtp($mobile);

if (!$result['success']) {
    $_SESSION['error'] =
        'MSG91 error: ' . $result['message'] .
        (isset($result['http_code']) ? ' (HTTP ' . $result['http_code'] . ')' : '');
    header('Location: index.php');
    exit;
}

$_SESSION['otp_mobile'] = $mobile;
$_SESSION['otp_sent_time'] = time();
$_SESSION['otp_attempts'] = 0;
$_SESSION['success'] = 'OTP sent successfully through MSG91.';

header('Location: verify.php');
exit;
