<?php
require('dbconnect.php');
session_start();

  // GET送信で送られてきた値で該当する投稿を取得
  // GET送信はURLを参照する
if (isset($_GET)) {

  $sql = 'UPDATE `tweets` SET `delete_flag`=1 WHERE `tweet_id`=?';
  $data = array($_GET['tweet_id']);
  $stmt = $dbh->prepare($sql);
  $stmt->execute($data);

  header('Location: index.php');
    // それ以降の処理を中断
  exit();
}