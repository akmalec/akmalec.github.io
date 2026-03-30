<?php

/*
 * ------------------------------------
 * Mailchimp Email Subscription - SECURE
 * ------------------------------------
 */

// SECURITY: Use environment variables, NOT hardcoded keys!
$apiKey = getenv('MAILCHIMP_API_KEY');
$listId = getenv('MAILCHIMP_LIST_ID');

if (!$apiKey || !$listId) {
    echo 'error';
    exit;
}

$double_optin = true;
$send_welcome = true;

// SECURITY: Use POST only, validate input
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'error';
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// SECURITY: Strict email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo 'error';
    exit;
}

// SECURITY: Sanitize and limit length
$email = substr($email, 0, 254);
$email = str_replace(["\r", "\n"], '', $email);

$fname = isset($_POST['fname']) ? trim(substr($_POST['fname'], 0, 50)) : '';
$lname = isset($_POST['lname']) ? trim(substr($_POST['lname'], 0, 50)) : '';

// SECURITY: Remove newlines from names
$fname = str_replace(["\r", "\n"], '', $fname);
$lname = str_replace(["\r", "\n"], '', $lname);

if (!empty($email)) {
    $datacenter = explode('-', $apiKey);
    if (count($datacenter) < 2) {
        echo 'error';
        exit;
    }
    
    $post_url = 'https://' . $datacenter[1] . '.api.mailchimp.com/2.0/lists/subscribe.json?';

    // SECURITY: Build safe query array
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
        )
    );

    $post_query_string = http_build_query($post_query_array);

    // SECURITY: Use cURL with proper options
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $post_url);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_query_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $curl_out = curl_exec($ch);
    curl_close($ch);
    
    if (!$curl_out) {
        echo 'error';
        exit;
    }
    
    $data = json_decode($curl_out);
    if (isset($data->error)) {
        echo 'error';
    } else {
        echo 'success';
    }
} else {
    echo 'error';
}

?>
