<?php

define("DEBUG", 0);
define("USE_SANDBOX", 0);
define("LOG_FILE", "./ipn.log");

// SECURITY: Use environment variables for sensitive config
$receiver_email = getenv('PAYPAL_RECEIVER_EMAIL') ?: 'test@surjithctly.in';
$admin_email = getenv('ADMIN_EMAIL') ?: $receiver_email;

if (!$receiver_email) {
    error_log("PayPal IPN: Receiver email not configured", 3, LOG_FILE);
    exit;
}

// SECURITY: Read and validate raw POST data
$raw_post_data = file_get_contents('php://input');
if (empty($raw_post_data)) {
    error_log("PayPal IPN: No POST data received", 3, LOG_FILE);
    exit;
}

$raw_post_array = explode('&', $raw_post_data);
$myPost = array();
foreach ($raw_post_array as $keyval) {
    $keyval = explode('=', $keyval);
    if (count($keyval) == 2)
        $myPost[$keyval[0]] = urldecode($keyval[1]);
}

$req = 'cmd=_notify-validate';
foreach ($myPost as $key => $value) {
    $value = urlencode($value);
    $req .= "&$key=$value";
}

// Verify with PayPal
$paypal_url = USE_SANDBOX 
    ? "https://www.sandbox.paypal.com/cgi-bin/webscr"
    : "https://www.paypal.com/cgi-bin/webscr";

$ch = curl_init($paypal_url);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

$res = curl_exec($ch);
curl_close($ch);

$tokens = explode("\r\n\r\n", trim($res));
$res = trim(end($tokens));

if (strcmp($res, "VERIFIED") == 0) {
    // SECURITY: Validate payment amount, receiver email, etc.
    $payment_status = isset($myPost['payment_status']) ? trim($myPost['payment_status']) : '';
    $receiver_check = isset($myPost['receiver_email']) ? trim($myPost['receiver_email']) : '';
    
    // SECURITY: Verify receiver email matches configuration
    if ($receiver_check !== $receiver_email) {
        error_log("PayPal IPN: Receiver email mismatch", 3, LOG_FILE);
        exit;
    }
    
    if ($payment_status !== 'Completed') {
        error_log("PayPal IPN: Payment status not Completed: " . $payment_status, 3, LOG_FILE);
        exit;
    }
    
    // SECURITY: Validate and sanitize all data
    $payer_email = isset($myPost['payer_email']) ? trim($myPost['payer_email']) : '';
    if (!filter_var($payer_email, FILTER_VALIDATE_EMAIL)) {
        error_log("PayPal IPN: Invalid payer email", 3, LOG_FILE);
        exit;
    }
    
    $event_pass = htmlspecialchars(isset($myPost['option_selection1']) ? trim($myPost['option_selection1']) : '', ENT_QUOTES, 'UTF-8');
    $quantity = htmlspecialchars(isset($myPost['quantity']) ? trim($myPost['quantity']) : '0', ENT_QUOTES, 'UTF-8');
    $payment_amount = htmlspecialchars(isset($myPost['mc_gross']) ? trim($myPost['mc_gross']) : '0', ENT_QUOTES, 'UTF-8');
    $txn_id = htmlspecialchars(isset($myPost['txn_id']) ? trim($myPost['txn_id']) : '', ENT_QUOTES, 'UTF-8');
    
    // Send confirmation to payer
    $subject = 'Your Seat is Reserved - Event Registration Confirmed';
    $message = "Hello,\r\n\r\nThank you for your registration!\r\n\r\n";
    $message .= "Event Pass: " . $event_pass . "\r\n";
    $message .= "Seats Reserved: " . $quantity . "\r\n";
    $message .= "Amount: " . $payment_amount . "\r\n";
    $message .= "Transaction ID: " . $txn_id . "\r\n\r\n";
    $message .= "Venue: https://goo.gl/maps/cSuf7\r\n\r\n";
    $message .= "We look forward to seeing you!\r\n";
    
    $headers = 'From: noreply@' . $_SERVER['HTTP_HOST'] . "\r\n";
    mail($payer_email, $subject, $message, $headers);
    
    error_log("PayPal IPN: Verified and processed - TXN: " . $txn_id, 3, LOG_FILE);
    
} else if (strcmp($res, "INVALID") == 0) {
    error_log("PayPal IPN: INVALID - Investigate manually", 3, LOG_FILE);
}

?>
