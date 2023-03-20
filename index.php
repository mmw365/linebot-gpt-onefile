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

function send_chatgpt($message) {
    $apiKey = "OPENAIのAPIキー";
    $requestData = [
        "model" => "gpt-3.5-turbo",
        "messages" => $message,
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
    $content = json_decode(curl_exec($ch));
    curl_close($ch);
    return $content->{'choices'}[0]->{'message'}->{'content'};
}

function make_request_message($inputText, $userId) {
    $filename = $userId . '.txt';
    $message = [];
    $message[] = ["role" => "system", "content" => "あなたは100文字程度で分かりやすく答えてくれます。"];
    if(file_exists($filename)) {
        $content = file_get_contents($userId . '.txt');
        $rows = explode("\n", $content);
        for ($i = 0; $i < count($rows) - 1; $i += 2) {
            $message[] = ["role" => "user", "content" => str_replace('\n', "\n", $rows[$i])];
            $message[] = ["role" => "assistant", "content" => str_replace('\n', "\n", $rows[$i + 1])];
        }
    }
    $message[] = ["role" => "user", "content" => $inputText];
    return $message;
}

function delete_old_file($userId) {
    $filename = $userId . '.txt';
    if(file_exists($filename)) {
        $expire = strtotime("-5 minutes");
        $mod = filemtime($filename);
        if($mod < $expire){
            unlink($filename);
        }
    }
}

function save_talk($userId, $text, $responseText) {
    $filename = $userId . '.txt';
    $fp = fopen($filename,'a');
    fwrite($fp, str_replace("\n", '\n', $text) . "\n");
    fwrite($fp, str_replace("\n", '\n', $responseText) . "\n");
    fclose($fp);
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
$userId = $inputObj->{"events"}[0]->{"source"}->{"userId"};
delete_old_file($userId);
$message = make_request_message($text, $userId);
$responseText = send_chatgpt($message);
save_talk($userId, $text, $responseText);

send_reply_message($replyToken, $responseText);
