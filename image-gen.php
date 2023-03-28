<?php

$accessToken = "LINEのアクセストークン";
$apiKey = "OPENAIのAPIキー";

function send_reply_message($replyToken, $text, $url) {
    global $accessToken;
    $response = json_encode([
        "replyToken" => $replyToken,
        "messages" => [[
            "type" => "text",
            "text" => $text,
        ],
        [
            "type" => "image",
            "originalContentUrl" => $url,
            "previewImageUrl" => $url,
        ]]
    ]);
    
    $ch = curl_init("https://api.line.me/v2/bot/message/reply");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charser=UTF-8',
        'Authorization: Bearer ' . $accessToken
    ));
    curl_exec($ch);
    curl_close($ch);
}

function send_chatgpt($text) {
    global $apiKey;
    $message = [
        ["role" => "system", "content" => "You translate Japanese into English"],
        ["role" => "user", "content" => $text],
    ];
    $request = json_encode([
        "model" => "gpt-3.5-turbo",
        "messages" => $message,
    ]);
    
    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charser=UTF-8',
        'Authorization: Bearer ' . $apiKey
    ));
    $content = json_decode(curl_exec($ch));
    curl_close($ch);
    return $content->{'choices'}[0]->{'message'}->{'content'};
}

function send_dalle($prompt) {
    global $apiKey;
    $request = json_encode([
        "prompt" => $prompt,
        "n" => 1,
        "size" => "256x256",
    ]);
    
    $ch = curl_init("https://api.openai.com/v1/images/generations");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charser=UTF-8',
        'Authorization: Bearer ' . $apiKey
    ));
    $content = json_decode(curl_exec($ch));
    curl_close($ch);
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
