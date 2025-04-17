<?php
require_once '../../db.php';
require_once '../config.php'; // APIキーを読み込む

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'unanswered' ;



// base_info 読み込み
$sql = "SELECT * FROM question where state = '" . $mode  . "' order By id DESC ";

// var_dump($sql);
$question_result = $mysqli->query( $sql );

$state_array = [
  'unanswered' => '未確認',
  'answered' => '回答済み',
  'archive' => 'アーカイブ',
]

?>
<!DOCTYPE html>
<html lang="ja">
<head>

  <meta charset="UTF-8">
  <title>回答できなかった質問</title>
  <script src="https://cdn.jsdelivr.net/npm/vue@3.2.47/dist/vue.global.prod.js"></script>
  <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo $GOOGLE_MAPS_API_KEY; ?>"></script>
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <meta name="robots" content="noindex, nofollow">

  <style>
  label {
    display: block;
    width: 100%
  }

  #tab_wrap {
    overflow: hidden;
    border-bottom: solid 1px;
    position: relative;
  }

  .tab {
    display: inline-block;
    width: calc(100px);
    height: 35px;
    border:solid 1px;
    border-bottom:0;
    text-align: center;
    margin-left: 10px;
    position: relative;
    text-decoration: none;
  }
  .tab.selected {
    background-color: black;
    color: white;
  }

  table {
    width: calc(100%)
  }
  tr {
  }
  td {
    border-bottom: solid 1px #dcdcdc;
  }
  .q {
    width: calc(40%)
  }
  .a {
    width: calc(40%);
  }
  input[type="text"]{
  display: block;
  width: calc(100% - 15px);
  padding:5px;
  margin: 0;
  }

  .cvt_title {
    cursor: pointer;
  }

  .cnv_text {
    /* padding: 10px; */
    display: none;
    border-top: solid 1px #dcdcdc;
    /* border: solid 1px; */
    /* background-color: #dcdcdc; */
  }
  .cnv_text div {
    background-color: #dcdcdc;
    /* padding: 10px; */
    padding-top: 10px;
  }
  .cnv_text .bot {
  }
  .cnv_text .user {
  }

  </style>
<script>
$(function(){
    $("[id^=t_]").on("click",function(){
      var id = $(this).data("id")
      $('#c_' + id).toggle()
        // console.log($(this).data("id"));
    });
});
</script>

</head>
<body>
<?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>
<div class="dashboard-container">

<?php include('../components/side_navi.php'); ?>
  <div id="app">

    <h1>回答できなかった質問</h1>

    <div id="tab_wrap">
      <a href="?mode=unanswered" class="tab <?= $mode == 'unanswered' ? 'selected' : '' ?>">未確認</a>
      <a href="?mode=answered" class="tab <?= $mode == 'answered' ? 'selected' : '' ?>">回答済み</a>
      <a href="?mode=archive" class="tab <?= $mode == 'archive' ? 'selected' : '' ?>">アーカイブ</a>
    </div>
    <main>
      <table>
        <tr>
          <th class="q">わからなかった会話内容</th>
          <th  class="a">それに対する答え</th>
          <th  class="state">状態</th>
        </tr>
      <?php foreach ($question_result as $q ) { ?>
        <tr>
          <form method="post" action="complete.php">
              <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
              <td>
                <div class="cnv_title" id="t_<?= $q['id'] ?>" data-id="<?= $q['id'] ?>" ><?= $q['question'] ?></div>
                <?php if ($q['conversation']) { ?>
                <div class="cnv_text" id="c_<?= $q['id'] ?>" >
                  <?php
                  $conversation = json_decode($q['conversation'], true);
                  foreach ( $conversation as $key => $chat) { ?>
                    <div class="<?= $chat['sender'] ?>" ><?= $chat['sender'] ?> :<?= $chat['text'] ?></div>
                  <?php } ?>
                  <?php } ?>
                </div>
              </td>
              <td><input type="text" name="answer" value="<?= $q['answer'] ?>"></td>
              <td><?= $state_array[$q['state']]  ?></td>
              <td><button>保存</button></td>
          </form>

          <form method="post" action="archive_question.php">
              <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
              <td>
                <?php if ($q['state'] !== "archive" ){ ?>
                  <button type="submit" name="mode" value="archive" >アーカイブ</button>
                <?php } else { ?>
                  <button type="submit" name="mode" value="unarchive" >アーカイブから戻す</button>
                <?php }  ?>
              </td>
          </form>

          <form method="post" action="delete_question.php" onsubmit="return confirmDelete();">
              <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
              <td>
                  <button type="submit">削除</button>
              </td>
          </form>
        </tr>
      <?php } ?>
    </table>
    </main>
    <script>
        function confirmDelete() {
            return confirm("本当に削除してもよろしいですか？");
        }
    </script>

  </div>
</div>
</body>
</html>
