<?php
$accessToken = "LINE Channel Access Token";
$apiKey = "OpenAI API Key";

function send_with_auth_token($url, $data, $token, $isJson) {
    $header = [];
    if($isJson) {
        $header[] = 'Content-Type: application/json; charser=UTF-8';
    }
    $header[] = 'Authorization: Bearer ' . $token;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_exec($ch);
    $response = json_decode(curl_exec($ch));
    curl_close($ch);
    return $response;
}

function send_json_with_auth_token($url, $message, $token) {
    return send_with_auth_token($url, $message, $token, true);
}

function send_formdata_with_auth_token($url, $data, $token) {
    return send_with_auth_token($url, $data, $token, false);
}

function send_reply_message($replyToken, $text, $url) {
    global $accessToken;
    $messages = [];
    if($text != '') {
        $messages[] = ["type" => "text", "text" => $text,];
    }
    $messages[] = ["type" => "image", "originalContentUrl" => $url, "previewImageUrl" => $url,];
    $message = json_encode(["replyToken" => $replyToken, "messages" => $messages,]);
    send_json_with_auth_token("https://api.line.me/v2/bot/message/reply", $message, $accessToken);
}

function get_content_with_auth_token($url, $token) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $token
    ));
    curl_exec($ch);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
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

function send_dalle_gen($prompt) {
    global $apiKey;
    $message = json_encode([
        "prompt" => $prompt,
        "n" => 1,
        "size" => "256x256",
    ]);
    $content = send_json_with_auth_token("https://api.openai.com/v1/images/generations", $message, $apiKey);
    return $content->{'data'}[0]->{'url'};
}

function send_dalle_var($filename) {
    global $apiKey;
    $data = [
        "image" => curl_file_create($filename),
        "n" => 1,
        "size" => "256x256",        
    ];
    $content = send_formdata_with_auth_token("https://api.openai.com/v1/images/variations", $data, $apiKey);
    return $content->{'data'}[0]->{'url'};
}

function save_image_file($messageId, $filename) {
    global $accessToken;
    $url = 'https://api-data.line.me/v2/bot/message/' . $messageId . '/content';
    $content = get_content_with_auth_token($url, $accessToken);
    $filename_tmep = "input_image_tmep.jpg";
    $fp = fopen($filename_tmep, 'w');
    fwrite($fp, $content);
    fclose($fp);

    // adjust image to square
    $image_size = getimagesize($filename_tmep);
    $width = $image_size[0];
    $height = $image_size[1];
    $newsize = $width < $height ? $width : $height;
    $dst_image = imagecreatetruecolor($newsize, $newsize);
    $x_offset = ($width - $newsize) / 2;
    $y_offset = ($height - $newsize) / 2;
    $img = imagecreatefromjpeg($filename_tmep);
    imagecopyresampled($dst_image, $img, 0, 0, $x_offset, $y_offset, $newsize, $newsize, $newsize, $newsize);

    // save as png
    imagepng($dst_image, $filename);
}

function process_generation($inputObj) {
    $replyToken = $inputObj->{"events"}[0]->{"replyToken"};
    $text = $inputObj->{"events"}[0]->{"message"}->{"text"};
    $entext = send_chatgpt($text);
    $url = send_dalle_gen($entext);
    send_reply_message($replyToken, $entext, $url);
}

function process_variation($inputObj) {
    $replyToken = $inputObj->{"events"}[0]->{"replyToken"};
    $messageId = $inputObj->{"events"}[0]->{"message"}->{"id"};
    $filename = 'input_image.png';
    save_image_file($messageId, $filename);
    $url = send_dalle_var($filename);
    send_reply_message($replyToken, '', $url);
}
  
$inputJsonMsg = file_get_contents('php://input');
$inputObj = json_decode($inputJsonMsg);

if($inputObj->{"events"}[0]->{"type"} != "message") {
    exit;
}

if($inputObj->{"events"}[0]->{"message"}->{"type"} == "text") {
    process_generation($inputObj);
    exit;
} 

if($inputObj->{"events"}[0]->{"message"}->{"type"} == "image") {
    process_variation($inputObj);
    exit;
}