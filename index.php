<?php
$accessToken = "LINEのアクセストークン";
$apiKey = "OPENAIのAPIキー";
$systemMessage = "あなたは100文字程度で分かりやすく答えてくれます。";

function send_reply_message($replyToken, $responseText) {
    global $accessToken;
    $response = json_encode([
        "replyToken" => $replyToken,
        "messages" => [["type" => "text", "text" => $responseText]]
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

function send_chatgpt($message) {
    global $apiKey;
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

function make_request_message($inputText, $filename) {
    global $systemMessage;
    $message = [];
    $message[] = ["role" => "system", "content" => $systemMessage];
    if(file_exists($filename)) {
        $content = file_get_contents($filename);
        $rows = explode("\n", $content);
        for ($i = 0; $i < count($rows) - 1; $i += 2) {
            $message[] = ["role" => "user", "content" => str_replace('\n', "\n", $rows[$i])];
            $message[] = ["role" => "assistant", "content" => str_replace('\n', "\n", $rows[$i + 1])];
        }
    }
    $message[] = ["role" => "user", "content" => $inputText];
    return $message;
}

function delete_old_file($filename) {
    if(file_exists($filename)) {
        $expire = strtotime("-5 minutes");
        $mod = filemtime($filename);
        if($mod < $expire){
            unlink($filename);
        }
    }
}

function save_talk($filename, $text, $responseText) {
    $fp = fopen($filename, 'a');
    fwrite($fp, str_replace("\n", '\n', $text) . "\n");
    fwrite($fp, str_replace("\n", '\n', $responseText) . "\n");
    fclose($fp);
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
$filename = $userId . '.txt';
delete_old_file($filename);
$message = make_request_message($text, $filename);
$responseText = send_chatgpt($message);
save_talk($filename, $text, $responseText);

send_reply_message($replyToken, $responseText);
