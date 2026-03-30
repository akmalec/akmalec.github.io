<?php

/**
 * Mailchimp Email Subscription Handler - SECURE VERSION
 * 
 * This script safely handles email subscriptions to Mailchimp
 * Configuration is loaded from .env file for security
 */

// SECURITY: Enable error reporting in development only
if (getenv('ENVIRONMENT') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// SECURITY: Strict types
declare(strict_types=1);

// Load environment configuration
require_once __DIR__ . '/../config/load-env.php';

// Get API credentials from environment
$apiKey = getenv('MAILCHIMP_API_KEY');
$listId = getenv('MAILCHIMP_LIST_ID');

// SECURITY: Validate configuration is loaded
if (!$apiKey || !$listId) {
    error_log("Mailchimp configuration missing from environment", 3, __DIR__ . '/errors.log');
    http_response_code(500);
    echo 'error';
    exit;
}

$double_optin = true;  // Require email verification
$send_welcome = true;  // Send welcome email to new subscribers

// SECURITY: Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'error';
    exit;
}

// SECURITY: Verify CSRF token (if implemented)
// Optional: Implement CSRF protection token validation here

// SECURITY: Get and validate email
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// SECURITY: Strict email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo 'error';
    exit;
}

// SECURITY: Sanitize and limit email length (RFC 5321)
$email = substr($email, 0, 254);
$email = str_replace(["\r", "\n", "\0"], '', $email);

// SECURITY: Optional - Get and validate first/last names
$fname = '';
$lname = '';

if (isset($_POST['fname'])) {
    $fname = trim($_POST['fname']);
    $fname = substr($fname, 0, 50);
    $fname = str_replace(["\r", "\n", "\0"], '', $fname);
    $fname = htmlspecialchars($fname, ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['lname'])) {
    $lname = trim($_POST['lname']);
    $lname = substr($lname, 0, 50);
    $lname = str_replace(["\r", "\n", "\0"], '', $lname);
    $lname = htmlspecialchars($lname, ENT_QUOTES, 'UTF-8');
}

// SECURITY: Extract datacenter from API key
$apiKeyParts = explode('-', $apiKey);
if (count($apiKeyParts) < 2) {
    error_log("Invalid Mailchimp API key format", 3, __DIR__ . '/errors.log');
    http_response_code(500);
    echo 'error';
    exit;
}

$datacenter = $apiKeyParts[1];

// SECURITY: Build API URL safely
$post_url = 'https://' . preg_replace('/[^a-z0-9]/', '', $datacenter) . '.api.mailchimp.com/2.0/lists/subscribe.json';

// Build safe query array for Mailchimp API
$post_query_array = array(
    "apikey" => $apiKey,
    "id" => $listId,
    "email" => array(
        "email" => $email,
        "euid" => "",
        "leid" => ""
    ),
    "double_optin" => $double_optin,
    "send_welcome" => $send_welcome,
    "merge_vars" => array(
        'FNAME' => $fname,
        'LNAME' => $lname
    ),
    "update_existing" => false  // Don't update if already subscribed
);

$post_query_string = http_build_query($post_query_array);

// Submit to Mailchimp API
try {
    $ch = curl_init();
    
    if ($ch === false) {
        throw new Exception('Failed to initialize cURL');
    }
    
    curl_setopt($ch, CURLOPT_URL, $post_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_query_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    
    $curl_out = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if (!$curl_out) {
        throw new Exception('Empty response from Mailchimp API');
    }
    
    // Decode Mailchimp response
    $data = json_decode($curl_out, true);
    
    if (!is_array($data)) {
        throw new Exception('Invalid JSON response from Mailchimp');
    }
    
    // SECURITY: Check for errors from Mailchimp
    if (isset($data['error'])) {
        error_log("Mailchimp error: " . $data['error'], 3, __DIR__ . '/errors.log');
        http_response_code(400);
        echo 'error';
        exit;
    }
    
    // Success!
    http_response_code(200);
    echo 'success';
    exit;
    
} catch (Exception $e) {
    error_log("Subscribe error: " . $e->getMessage(), 3, __DIR__ . '/errors.log');
    http_response_code(500);
    echo 'error';
    exit;
}

?>
