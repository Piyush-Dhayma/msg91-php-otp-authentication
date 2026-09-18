<?php

require_once __DIR__ . '/config.php';

// =====================================================
// CLEAN INDIAN PHONE NUMBER
// =====================================================

function cleanPhone($phone)
{
    $phone = preg_replace('/\D+/', '', (string) $phone);

    if (strlen($phone) === 10 && preg_match('/^[6-9]\d{9}$/', $phone)) {
        return '91' . $phone;
    }

    if (strlen($phone) === 12 && substr($phone, 0, 2) === '91') {
        $local = substr($phone, 2);

        if (preg_match('/^[6-9]\d{9}$/', $local)) {
            return $phone;
        }
    }

    return false;
}

// =====================================================
// INTERNAL MSG91 REQUEST HELPER
// =====================================================

function msg91Request($url, $method = 'GET')
{
    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'message' => 'PHP cURL extension is not enabled.'
        ];
    }

    $ch = curl_init($url);

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'authkey: ' . MSG91_AUTH_KEY
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ];

    if (strtoupper($method) === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = '';
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
    } else {
        $options[CURLOPT_HTTPGET] = true;
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError) {
        return [
            'success' => false,
            'message' => 'MSG91 connection error: ' . ($curlError ?: 'Unknown cURL error.'),
            'http_code' => $httpCode,
            'data' => null,
        ];
    }

    $result = json_decode($response, true);

    if (!is_array($result)) {
        return [
            'success' => false,
            'message' => 'MSG91 returned an invalid response.',
            'http_code' => $httpCode,
            'raw' => $response,
            'data' => null,
        ];
    }

    $apiSuccess =
        $httpCode >= 200 &&
        $httpCode < 300 &&
        strtolower((string) ($result['type'] ?? '')) === 'success';

    return [
        'success' => $apiSuccess,
        'message' => (string) ($result['message'] ?? ($apiSuccess ? 'Request completed successfully.' : 'MSG91 request failed.')),
        'http_code' => $httpCode,
        'data' => $result,
    ];
}

// =====================================================
// SEND OTP
// =====================================================

function sendOtp($mobile)
{
    if (
        MSG91_AUTH_KEY === '' ||
        MSG91_AUTH_KEY === 'PASTE_NEW_MSG91_AUTH_KEY_HERE' ||
        MSG91_TEMPLATE_ID === '' ||
        MSG91_TEMPLATE_ID === 'PASTE_MSG91_TEMPLATE_ID_HERE'
    ) {
        return [
            'success' => false,
            'message' => 'MSG91 Auth Key or Template ID is not configured.'
        ];
    }

    $query = http_build_query([
        'template_id' => MSG91_TEMPLATE_ID,
        'mobile' => $mobile,
        'authkey' => MSG91_AUTH_KEY,
        'otp_length' => MSG91_OTP_LENGTH,
        'otp_expiry' => MSG91_OTP_EXPIRY_MINUTES,
    ]);

    $url = 'https://control.msg91.com/api/v5/otp?' . $query;

    return msg91Request($url, 'POST');
}

// =====================================================
// VERIFY OTP
// =====================================================

function verifyOtp($mobile, $otp)
{
    $query = http_build_query([
        'otp' => $otp,
        'mobile' => $mobile,
    ]);

    $url = 'https://control.msg91.com/api/v5/otp/verify?' . $query;

    return msg91Request($url, 'GET');
}

// =====================================================
// RESEND / RETRY OTP
// =====================================================

function resendOtp($mobile, $retryType = 'text')
{
    $retryType = strtolower($retryType) === 'voice' ? 'voice' : 'text';

    $query = http_build_query([
        'authkey' => MSG91_AUTH_KEY,
        'retrytype' => $retryType,
        'mobile' => $mobile,
    ]);

    $url = 'https://control.msg91.com/api/v5/otp/retry?' . $query;

    return msg91Request($url, 'GET');
}
