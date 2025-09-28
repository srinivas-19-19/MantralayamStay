<?php
function send_fcm_notification($fcm_token, $title, $body) {
    // Make sure you have replaced this with your actual Server Key
    $server_key = 'AIzaSyC_pR4Ek8C4jnz5IVSS8-m-RDytUZfSK2o'; 
    $url = 'https://fcm.googleapis.com/fcm/send';

    $notification = [
        'title' => $title,
        'body' => $body,
        'sound' => 'default' // This triggers the default notification sound
    ];

    $fields = [
        'to' => $fcm_token,
        'notification' => $notification
    ];

    $headers = [
        'Authorization: key=' . $server_key,
        'Content-Type: application/json'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    
    $result = curl_exec($ch);
    
    // Check for cURL errors without stopping the script
    if ($result === FALSE) {
        error_log('FCM cURL Error: ' . curl_error($ch));
    }
    curl_close($ch);

    // Log the result for debugging purposes without showing it to the user
    error_log("FCM Server Response: " . $result);
}
?>
