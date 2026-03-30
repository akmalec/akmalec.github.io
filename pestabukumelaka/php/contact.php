<?php

/*
 * ------------------------------------
 * Contact Form Configuration - SECURE
 * ------------------------------------
 */
 
$to    = "test@surjithctly.in";
$subject_txt = "Event Website Contact Form Submission";

/*
 * ------------------------------------
 * END CONFIGURATION
 * ------------------------------------
 */

// SECURITY: Use POST only, not REQUEST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'error';
    exit;
}

// SECURITY: Validate and sanitize all inputs
$first_name = isset($_POST["first_name"]) ? trim($_POST["first_name"]) : '';
$last_name = isset($_POST["last_name"]) ? trim($_POST["last_name"]) : '';
$email = isset($_POST["email"]) ? trim($_POST["email"]) : '';
$phone = isset($_POST["phone"]) ? trim($_POST["phone"]) : '';
$msg = isset($_POST["message"]) ? trim($_POST["message"]) : '';

// SECURITY: Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo 'error';
    exit;
}

// SECURITY: Validate phone (basic)
if (!preg_match('/^[0-9\s\-\+\(\)]{10,}$/', $phone)) {
    echo 'error';
    exit;
}

// SECURITY: Limit input lengths
$first_name = substr($first_name, 0, 50);
$last_name = substr($last_name, 0, 50);
$msg = substr($msg, 0, 5000);

// SECURITY: Remove newlines and carriage returns from name and email
$first_name = str_replace(["\r", "\n"], '', $first_name);
$last_name = str_replace(["\r", "\n"], '', $last_name);
$email = str_replace(["\r", "\n"], '', $email);

// SECURITY: Sanitize message for HTML emails
$msg_escaped = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
$name = htmlspecialchars($first_name . ' ' . $last_name, ENT_QUOTES, 'UTF-8');

// SECURITY: Use strict email validation for headers
$email_header = filter_var($email, FILTER_VALIDATE_EMAIL);
if (!$email_header) {
    echo 'error';
    exit;
}

if (isset($email) && isset($first_name)) {
    $website = "https://" . $_SERVER['HTTP_HOST']; // HTTPS only
    
    // SECURITY: Use proper mail headers without user input directly
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "Reply-To: " . $email_header . "\r\n";
    
    // SECURITY: Safe message construction
    $message = "From: {$name}\r\n";
    $message .= "Email: {$email_header}\r\n";
    $message .= "Phone: " . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . "\r\n";
    $message .= "Message:\r\n{$msg_escaped}";
    
    // SECURITY: Sanitize subject
    $subject = "Event Website Contact: " . $first_name;
    
    // SECURITY: Use mail() safely
    $mail = mail($to, $subject, $message, $headers);
    
    if ($mail) {
        echo 'success';
    } else {
        echo 'error';
    }
} else {
    echo 'error';
}

?>
