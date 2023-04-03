## このアプリケーションについて

OpenAIのAPIを使ったPHPの1ファイルで作ったチャットボットです。

## ファイルの説明

- index.php　ChatGPTを使った会話用チャットボット
- image-gen.phop DELL-Eを使った言葉から画像を作成するチャットボット
- image-var.phop DELL-Eを使った画像の別バージョンを作成するチャットボット
- image-gen-var.php 言葉から画像生成、画像から別のバージョンを作成するチャットボット

## 使い方

- LINEのチャネルを作成すし、チャネルアクセストークンを発行する
- OpenAIのAPI Keyを取得する
- PHPファイルにチャネルアクセストークンとOpenAIのAPI Keyを設定する
- PHPファイルをHTTPSが有効なレンタルサーバ等に置く
- LINEチャネルのWebhook設定にURLを指定し、Webhookの利用をオンにする