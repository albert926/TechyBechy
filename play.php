<?php
header('Content-Type: application/json');

// SECRET KEY — better: put in env var or config outside webroot
$secretKey = getenv('TURNSTILE_SECRET_KEY') ?: '0x4AAAAAABrnFvvB4raeHlqItWRMr5GKQIA';

// Require token
if (empty($_POST['token'])) {
    echo json_encode(['success' => false, 'error' => 'no_token']);
    exit;
}

$token = $_POST['token'];
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? null;

// Build request
$postFields = [
    'secret'   => $secretKey,
    'response' => $token,
];
if ($remoteIp) {
    $postFields['remoteip'] = $remoteIp; // optional but recommended
}

$endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

// Prefer cURL
if (function_exists('curl_init')) {
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($postFields),
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        echo json_encode(['success' => false, 'error' => 'curl_error', 'detail' => $err]);
        exit;
    }
} else {
    // Fallback
    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($postFields),
            'timeout' => 10,
        ],
    ]);
    $response = @file_get_contents($endpoint, false, $context);
    if ($response === false) {
        echo json_encode(['success' => false, 'error' => 'http_fopen_error']);
        exit;
    }
}

$data = json_decode($response, true);

// Optional hardening (uncomment and set your domain):
// $expectedHost = 'techybechy.alberttalkstech.com';
// if (!empty($data['success']) && (!isset($data['hostname']) || $data['hostname'] !== $expectedHost)) {
//     echo json_encode(['success' => false, 'error' => 'bad_hostname']);
//     exit;
// }

if (!empty($data['success'])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'error'   => $data['error-codes'] ?? ['unknown_error'],
        'raw'     => $data
    ]);
}
