<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['otp_mobile'])) {
    header('Location: index.php');
    exit;
}

$mobile = $_SESSION['otp_mobile'];

if (
    isset($_SESSION['otp_sent_time']) &&
    time() - (int)$_SESSION['otp_sent_time'] < 30
) {
    $_SESSION['error'] = 'Please wait 30 seconds before requesting another OTP.';
    header('Location: verify.php');
    exit;
}

$result = resendOtp($mobile, 'text');

if (!$result['success']) {
    $_SESSION['error'] =
        'MSG91 resend error: ' . $result['message'] .
        (isset($result['http_code']) ? ' (HTTP ' . $result['http_code'] . ')' : '');
    header('Location: verify.php');
    exit;
}

$_SESSION['otp_sent_time'] = time();
$_SESSION['otp_attempts'] = 0;
$_SESSION['success'] = 'OTP resent successfully through MSG91.';

header('Location: verify.php');
exit;
