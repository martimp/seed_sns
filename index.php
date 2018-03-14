<?php 
  // DBの接続
require('dbconnect.php');
session_start();

  // ログインチェック
  // 1時間ログインしていない場合、再度ログイン
if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
  // ログインしている
  // ログイン画面の更新
  $_SESSION['time'] = time();

  // ログインユーザー情報取得
  $user_sql = 'SELECT * FROM `members` WHERE `member_id`=? ';
  $user_data = array($_SESSION['id']);
  $user_stmt = $dbh->prepare($user_sql);
  $user_stmt->execute($user_data);
  $login_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

} else{
  // ログインしていない、または時間切れの場合
  header('Location: login.php');
  exit;
}

  // つぶやくボタンが押された時
if (!empty($_POST)) {

    // 入力チェック
  if ($_POST['tweet'] == '') {
    $error['tweet'] = 'blank';
  }

  if (!isset($error)) {
      // SQL文作成(INSERT INTO)
      // tweet=呟いた内容
      // mamber_id=ログインした人のid
      // reply_tweet_id=-1
      // created=現在日時(now()を使用)
      // modified=現在日時(now()を使用)

    $sql = 'INSERT INTO `tweets` SET `tweet`=?, `member_id`=?, `reply_tweet_id`=-1, `created`=NOW(), `modified`=NOW()';
    $data = array($_POST['tweet'],$_SESSION['id']);
    $stmt = $dbh->prepare($sql);
    $stmt->execute($data);
  }

}
// ページング機能
// 空の変数を用意
$page = '';

// パラメータが存在していたらページ番号を代入
if (isset($_GET['page'])) {
  $page = $_GET['page'];
} else{
  $page = 1;
}

// 1以下のイレギュラーな数字が入ってきた時、ページ番号を強制的に1とする
// max カンマ区切りで羅列された数字の中から最大の数字を取得する
$page = max($page,1);

// 1ページ分の表示件数を指定
$page_number = 5;

// データの件数から最大ページを計算する
$page_sql = 'SELECT COUNT(*) AS `page_count` FROM `tweets` WHERE `delete_flag`=0;';
$page_stmt = $dbh->prepare($page_sql);
$page_stmt->execute();

// 全件取得(論理削除されていないもの)
$page_count = $page_stmt->fetch(PDO::FETCH_ASSOC);

// ceil 小数点切り上げ
$all_page_number = ceil($page_count['page_count'] / $page_number);

// パラメータのページ番号が最大ページを超えていれば、強制的に最後のページとする
// min カンマ区切りで羅列された数字の中から最小の数字を取得する
$page = min($page, $all_page_number);

// 表示するデータの取得開始場所
$start = ($page -1) * $page_number;



// 一覧用の投稿全件取得
// テーブル結合
// INNER join と OUTER JOIN(left join と right join)
// INNER join = 両方のテーブルに存在するデータのみ取得
// OUTER join(left join と right join) = 複数のテーブルがあり、それらを結合する際に優先テーブルを一つ決め、そこにある情報は全て表示しながら、他のテーブルの情報に対になるデータがあれば表示する。
// 優先テーブルに指定されると、そのテーブルの情婦は全て表示される
// LIMIT = テーブルから取得する範囲を指定
// LIMIT 取得する配列のキー,取得する数

$tweet_sql = "SELECT `tweets`.*,`members`.`nick_name`,`members`.`picture_path` FROM `tweets` LEFT JOIN `members` ON `tweets`.`member_id`=`members`.`member_id` WHERE `delete_flag`=0 ORDER BY `tweets`.`created` DESC LIMIT ".$start.",".$page_number;
$tweet_stmt = $dbh->prepare($tweet_sql);
$tweet_stmt->execute();



// 空の配列を用意
  $tweet_list = array(); //データがないときのエラーを防ぐ

  while (true) {
    $tweet = $tweet_stmt->fetch(PDO::FETCH_ASSOC);
    if ($tweet == false) {
      break;
    }
    $tweet_list[] = $tweet;
  }

  // var_dump($tweet_list);

// 

  ?>


  <!DOCTYPE html>
  <html lang="ja">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>SeedSNS</title>

    <!-- Bootstrap -->
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="assets/css/form.css" rel="stylesheet">
    <link href="assets/css/timeline.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">

  </head>
  <body>
    <nav class="navbar navbar-default navbar-fixed-top">
      <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header page-scroll">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="index.html"><span class="strong-title"><i class="fa fa-twitter-square"></i> Seed SNS</span></a>
        </div>
        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
          <ul class="nav navbar-nav navbar-right">
            <li><a href="logout.php">ログアウト</a></li>
          </ul>
        </div>
        <!-- /.navbar-collapse -->
      </div>
      <!-- /.container-fluid -->
    </nav>

    <div class="container">
      <div class="row">
        <div class="col-md-4 content-margin-top">
          <legend>ようこそ<?php echo $login_user['nick_name'] ?>さん！</legend>
          <form method="post" action="" class="form-horizontal" role="form">
            <!-- つぶやき -->
            <div class="form-group">
              <label class="col-sm-4 control-label">つぶやき</label>
              <div class="col-sm-8">
                <textarea name="tweet" cols="50" rows="5" class="form-control" placeholder="例：Hello World!"></textarea>
                <?php if (isset($error) && $error['tweet'] == 'blank') {  ?>
                <p class="error">つぶやき内容を入力してください</p>
                <?php } ?>

              </div>
            </div>
            <ul class="paging">
              <input type="submit" class="btn btn-info" value="つぶやく">
              &nbsp;&nbsp;&nbsp;&nbsp;
              <!-- 最初のページの時、前のボタンを押せないようにする -->
              <?php if ($page == 1) { ?>
              <li>前</li>
              <?php } else { ?>
              <li><a href="index.php?page=<?php echo $page -1; ?>" class="btn btn-default">前</a></li>
              <?php } ?>
              &nbsp;&nbsp;|&nbsp;&nbsp;
              <!-- 最後のページの時、次のボタンを押せないようにする -->
              <?php if ($page == $all_page_number) { ?>
              <li>次</li>
              <?php } else { ?>
              <li><a href="index.php?page=<?php echo $page +1; ?>" class="btn btn-default"></a></li>
              <?php } ?>
              <!-- 現在のページ / 最大のページ -->
              <li><?php echo $page; ?> / <?php echo $all_page_number; ?></li>
            </ul>
          </form>
        </div>

        <div class="col-md-8 content-margin-top">
          <?php foreach ($tweet_list as $unko) { 
           ?>
           <div class="msg">
            <img src="<?php echo "picture_path/".$unko['picture_path']; ?>" width="48" height="48">
            <p>
              <?php echo $unko['tweet']; ?><span class="name"> &nbsp;<?php echo $unko['nick_name'] ?></span>
              <?php if ($login_user['member_id'] !== $unko['member_id']) { ?>
              [<a href="#">Re</a>]
              <?php } ?>
            </p>
            <p class="day">
              <a href="date.php">
                <?php echo $unko['modified']; ?>
              </a>
              
              <?php if ($login_user['member_id'] == $unko['member_id']) { ?>
              [<a href="edit.php?tweet_id=<?php echo $unko['tweet_id']; ?>" style="color: #00994C;">編集</a>]
              [<a href="delete.php?tweet_id=<?php echo $unko['tweet_id']; ?>" style="color: #F33;">削除</a>]
              <?php } ?>
              [<a href="view.php?tweet_id=<?php echo $unko['tweet_id']; ?>" style="color: #000000;">詳細</a>]

            </p>
          </div>
          <?php } ?>
        </div>
      </div>
    </div>
    

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="assets/js/jquery-3.1.1.js"></script>
    <script src="assets/js/jquery-migrate-1.4.1.js"></script>
    <script src="assets/js/bootstrap.js"></script>
  </body>
  </html>
