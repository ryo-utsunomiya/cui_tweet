#!/usr/bin/env php
<?php

/**
* cui_tweet
* 
* @author Ryo Utsunomiya(@ryo511)
* @see codebird-php(https://github.com/mynetx/codebird-php)
*/

// Codebirdライブラリを読み込み
$libDir = __DIR__ . '/codebird/src/codebird.php';
if (is_readable($libDir)) {
    require_once $libDir;
} else {
    echo 'codebirdライブラリの読み込みに失敗しました', PHP_EOL;
    exit;
}

// コンシューマキーとアクセストークンの設定ファイルを読み込み
$configDir = __DIR__ . '/config.php';
if (is_readable($configDir)) {
    require_once $configDir;
} else { // @todo 設定ファイルが無い場合にウィザードで設定 
    echo 'config.phpの読み込みに失敗しました', PHP_EOL;
    exit;
}

$cb = new \Codebird\Codebird(); // Codebirdインスタンスを作成

// コンシューマキーとアクセストークンをセット
$cb->setConsumerKey($consumer_key, $consumer_secret);
$cb->setToken($access_token, $access_token_secret);

// ユーザ入力に応じてツイートを行う
if (isset($argv[1])) {
    $in = $argv[1];
    $in = trim($in); // 空白除去
    if ('exit' === $in) { // 'exit'が入力されたら終了
        exit;
    }
    if ($warning = lengthCheck($in)) { // 文字数チェック
        echo $warning, PHP_EOL; // 警告が返ってきた場合は表示
    } else { // 入力に問題ない場合はツイートを行う
        $reply = $cb->statuses_update('status=' . $in);
        errorCheck($reply); // twitterから返ってきた値にエラーがないかチェック
    }
    exit;

} else {

    // ユーザ名をローカルファイルから読み取り
    $userNameDir = __DIR__ . '/username.txt';
    if (is_readable($userNameDir)) {
        $userName = file_get_contents($userNameDir);
    } else { // ユーザ名設定ファイルの読み込みに失敗した場合はファイルを新規作成
        file_put_contents($userNameDir, '');
    }
    $userName = trim($userName);
    echo $userName, '>';

    $input = fopen('php://stdin', 'r'); // 標準入力ストリームを取得
    while ($in = fgets($input)) { // ユーザ入力を受け付け
        $in = trim($in); // 空白除去
        if ('exit' === $in) { // 'exit'が入力されたら終了
            exit;
        }
        if ($warning = lengthCheck($in)) { // 文字数チェック
            echo $warning, PHP_EOL; // 警告が返ってきた場合は表示
        } else { // 入力に問題ない場合はツイートを行う
            $reply = $cb->statuses_update('status=' . $in);
            errorCheck($reply); // twitterから返ってきた値にエラーがないかチェック
            nameCheck($reply); // ユーザ名に変更がないかチェック
        }
        echo $userName, '>';
    }
}

// ユーザ名に変更があったらローカルファイルを書き換え
function nameCheck($reply) {
    global $userName, $userNameDir;
    $userNameReply = trim($reply->user->name);
    if ($userNameReply !== $userName) {
        $userName = $userNameReply;
        file_put_contents($userNameDir, $userName);
        echo 'ユーザー名が変更されました', PHP_EOL;
    }
}

// twitterからエラーが返ってきたら終了させる
function errorCheck($reply) {
    if (array_key_exists('errors', $reply)) {
        exit("ERROR\n");
    }
}

// 文字数が0又は140文字を超えている場合に警告
function lengthCheck($str) {
    $length = mb_strlen($str, 'UTF-8');
    if (0 === $length) {
        return 'ポストする文字列がありません';
    } elseif (140 < $length) {
        return '140文字以内で入力してください';
    }
    return false;
}
