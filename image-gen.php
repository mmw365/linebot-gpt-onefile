<?php
$accessToken = "LINE Channel Access Token";
$apiKey = "OpenAI API Key";

function send_json_with_auth_token($url, $message, $token) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charser=UTF-8',
        'Authorization: Bearer ' . $token
    ));
    curl_exec($ch);
    $response = json_decode(curl_exec($ch));
    curl_close($ch);
    return $response;
}

function send_reply_message($replyToken, $text, $url) {
    global $accessToken;
    $message = json_encode([
        "replyToken" => $replyToken,
        "messages" => [
            ["type" => "text", "text" => $text,],
            ["type" => "image", "originalContentUrl" => $url, "previewImageUrl" => $url,]
        ]
    ]);
    send_json_with_auth_token("https://api.line.me/v2/bot/message/reply", $message, $accessToken);
}

function send_chatgpt($text) {
    global $apiKey;
    $message = json_encode([
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role" => "system", "content" => "You translate Japanese into English"],
            ["role" => "user", "content" => $text],
        ],
    ]);
    $content = send_json_with_auth_token("https://api.openai.com/v1/chat/completions", $message, $apiKey);
    return $content->{'choices'}[0]->{'message'}->{'content'};
}

function send_dalle($prompt) {
    global $apiKey;
    $message = json_encode([
        "prompt" => $prompt,
        "n" => 1,
        "size" => "256x256",
    ]);
    $content = send_json_with_auth_token("https://api.openai.com/v1/images/generations", $message, $apiKey);
    return $content->{'data'}[0]->{'url'};
}
  
$inputJsonMsg = file_get_contents('php://input');
$inputObj = json_decode($inputJsonMsg);

if($inputObj->{"events"}[0]->{"type"} != "message"
        || $inputObj->{"events"}[0]->{"message"}->{"type"} != "text") {
    exit;
}

$replyToken = $inputObj->{"events"}[0]->{"replyToken"};
$text = $inputObj->{"events"}[0]->{"message"}->{"text"};
$userId = $inputObj->{"events"}[0]->{"source"}->{"userId"};
$entext = send_chatgpt($text);
$url = send_dalle($entext);

send_reply_message($replyToken, $entext, $url);
