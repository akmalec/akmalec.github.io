<?php

/*
 * ------------------------------------
 * Registration Form Configuration - SECURE
 * ------------------------------------
 */
 
$to = "test@surjithctly.in";
$subject_txt = "New Event Registration";

/*
 * ------------------------------------
 * END CONFIGURATION
 * ------------------------------------
 */

// SECURITY: Use POST only, NOT REQUEST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'error';
    exit;
}

// SECURITY: Validate and sanitize all inputs
$first_name = isset($_POST["first_name"]) ? trim($_POST["first_name"]) : '';
$last_name = isset($_POST["last_name"]) ? trim($_POST["last_name"]) : '';
$email = isset($_POST["email"]) ? trim($_POST["email"]) : '';
$pass = isset($_POST["pass"]) ? trim($_POST["pass"]) : '';
$seats = isset($_POST["seats"]) ? trim($_POST["seats"]) : '';

// SECURITY: Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo 'error';
    exit;
}

// SECURITY: Limit input lengths
$first_name = substr($first_name, 0, 50);
$last_name = substr($last_name, 0, 50);
$pass = substr($pass, 0, 100);
$seats = substr($seats, 0, 10);

// SECURITY: Remove newlines and carriage returns from name and email
$first_name = str_replace(["\r", "\n"], '', $first_name);
$last_name = str_replace(["\r", "\n"], '', $last_name);
$email = str_replace(["\r", "\n"], '', $email);

// SECURITY: Sanitize all values for HTML emails
$name = htmlspecialchars($first_name . ' ' . $last_name, ENT_QUOTES, 'UTF-8');
$email_safe = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$pass_safe = htmlspecialchars($pass, ENT_QUOTES, 'UTF-8');
$seats_safe = htmlspecialchars($seats, ENT_QUOTES, 'UTF-8');

// SECURITY: Validate seats is numeric
if (!is_numeric($seats) || intval($seats) < 1) {
    echo 'error';
    exit;
}

// SECURITY: Use strict email validation for headers
$email_header = filter_var($email, FILTER_VALIDATE_EMAIL);
if (!$email_header) {
    echo 'error';
    exit;
}

if (isset($email) && isset($first_name)) {
    $website = "https://" . $_SERVER['HTTP_HOST'];
    
    // SECURITY: Use proper mail headers without user input directly
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "Reply-To: " . $email_header . "\r\n";
    
    // SECURITY: Safe message construction - NO user data in headers
    $message = "<h3>New Event Registration</h3>\r\n";
    $message .= "<p><strong>Attendee Information:</strong></p>\r\n";
    $message .= "<ul>\r\n";
    $message .= "<li><strong>Name:</strong> " . $name . "</li>\r\n";
    $message .= "<li><strong>Email:</strong> " . $email_safe . "</li>\r\n";
    $message .= "<li><strong>Pass:</strong> " . $pass_safe . "</li>\r\n";
    $message .= "<li><strong>Seats:</strong> " . $seats_safe . "</li>\r\n";
    $message .= "</ul>\r\n";
    $message .= "<p>This registration was received from: " . htmlspecialchars($website, ENT_QUOTES, 'UTF-8') . "</p>\r\n";
    $message .= "<hr><p><small>This is an automated message. Do not reply.</small></p>\r\n";
    
    // SECURITY: Sanitize subject (NO user data in subject line)
    $subject = $subject_txt . " - Registration ID: " . date('YmdHis');
    
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
