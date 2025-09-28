<?php
// This function sends a pre-approved template message via the WhatsApp Business API

// UPDATED: The function now accepts a template name as a parameter
function send_whatsapp_confirmation($recipient_phone, $template_data, $template_name) {
    // --- IMPORTANT: REPLACE WITH YOUR CREDENTIALS ---
    $api_key = 'YOUR_BSP_ACCESS_TOKEN'; // Your Access Token from the BSP
    $phone_number_id = 'YOUR_PHONE_NUMBER_ID'; // Your Phone Number ID from the BSP

    // The API endpoint provided by your BSP (this example is for Meta's direct API)
    $api_url = "https://graph.facebook.com/v19.0/{$phone_number_id}/messages";

    // Format the phone number (must include country code, no '+')
    $recipient_phone = preg_replace('/[^0-9]/', '', $recipient_phone);

    // Structure the data for the API request
    $data = [
        'messaging_product' => 'whatsapp',
        'to' => $recipient_phone,
        'type' => 'template',
        'template' => [
            'name' => $template_name, // Use the template name passed to the function
            'language' => [
                'code' => 'en_US' // Or 'en' for English
            ],
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => $template_data
                ]
            ]
        ]
    ];

    $payload = json_encode($data);
    $headers = [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ];

    // Use cURL to send the request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // In a real app, you would log the response for debugging
    error_log("WhatsApp API Response for {$template_name} to {$recipient_phone}: " . $response);

    if ($http_code == 200) {
        return true;
    } else {
        return false;
    }
}
?>
