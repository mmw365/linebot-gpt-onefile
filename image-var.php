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

function send_reply_message($replyToken, $url) {
    global $accessToken;
    $message = json_encode([
        "replyToken" => $replyToken,
        "messages" => [["type" => "image", "originalContentUrl" => $url, "previewImageUrl" => $url,]]
    ]);
    send_json_with_auth_token("https://api.line.me/v2/bot/message/reply", $message, $accessToken);
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

function send_dalle($filename) {
    global $apiKey;
    $data = [
        "image" => curl_file_create($filename),
        "n" => 1,
        "size" => "256x256",        
    ];
    $content = send_formdata_with_auth_token("https://api.openai.com/v1/images/variations", $data, $apiKey);
    return $content->{'data'}[0]->{'url'};
}
  
$inputJsonMsg = file_get_contents('php://input');
$inputObj = json_decode($inputJsonMsg);
if($inputObj->{"events"}[0]->{"type"} != "message"
        || $inputObj->{"events"}[0]->{"message"}->{"type"} != "image") {
    exit;
}

$replyToken = $inputObj->{"events"}[0]->{"replyToken"};
$messageId = $inputObj->{"events"}[0]->{"message"}->{"id"};

$filename = 'input_image.png';
save_image_file($messageId, $filename);
$url = send_dalle($filename);

send_reply_message($replyToken, $url);
