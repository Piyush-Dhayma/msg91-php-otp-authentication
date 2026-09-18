<?php

// =====================================================
// DATABASE CONFIGURATION
// =====================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'otp_login');
define('DB_USER', 'root');
define('DB_PASS', '');

// =====================================================
// MSG91 CONFIGURATION
// =====================================================
// Create an OTP template in MSG91 and copy its Template ID.
// Prefer environment variables on production hosting.

define('MSG91_AUTH_KEY', getenv('MSG91_AUTH_KEY') ?: '568861A0Z1iEXU6a9fabe5P1');
define('MSG91_TEMPLATE_ID', getenv('MSG91_TEMPLATE_ID') ?: '6a9fb46c6a4512ff5d064c32');

// OTP settings
define('MSG91_OTP_LENGTH', 6);
define('MSG91_OTP_EXPIRY_MINUTES', 5);

// =====================================================
// SESSION CONFIGURATION
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');

    // On HTTPS production hosting, enable secure cookies.
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }

    session_start();
}
