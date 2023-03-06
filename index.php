<?php

function send_reply_message($replyToken, $responseText) {
    $accessToken = "LINEのアクセストークン";
    $responseMessage = [
        "type" => "text",
        "text" => $responseText
    ];
    $responseData = [
        "replyToken" => $replyToken,
        "messages" => [$responseMessage]
    ];
    
    $ch = curl_init("https://api.line.me/v2/bot/message/reply");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($responseData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charser=UTF-8',
        'Authorization: Bearer ' . $accessToken
    ));
    curl_exec($ch);
    curl_close($ch);
}

function send_chatgpt($inputText) {
    $apiKey = "OPENAIのAPIキー";
    $requestData = [
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role" => "system", "content" => "あなたはいつも50文字以内で答えてくれます。"],
            ["role" => "user", "content" => $inputText], 
        ]
    ];
    
    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charser=UTF-8',
        'Authorization: Bearer ' . $apiKey
    ));
    $content = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $content['choices'][0]['message']['content'];
}

$inputJsonMsg = file_get_contents('php://input');
$inputObj = json_decode($inputJsonMsg);

$type = $inputObj->{"events"}[0]->{"type"};
if($type != "message"){
    exit;
}

$messagType= $inputObj->{"events"}[0]->{"message"}->{"type"};
if($messagType != "text"){
    exit;
}

$replyToken = $inputObj->{"events"}[0]->{"replyToken"};
$text = $inputObj->{"events"}[0]->{"message"}->{"text"};
$responseText = send_chatgpt($text);

send_reply_message($replyToken, $responseText);
