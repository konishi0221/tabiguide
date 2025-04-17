<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';



$page_uid = $_GET['page_uid'] ?? null;
if (!$page_uid) {
    echo "page_uid が指定されていません。";
    exit;
}

// chat_log の取得
$sql = "SELECT * FROM chat_log WHERE page_uid = :page_uid ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':page_uid' => $page_uid]);
$chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// question の取得と chat_id によるマッピング
$sql = "SELECT * FROM question WHERE page_uid = :page_uid";
$stmt = $pdo->prepare($sql);
$stmt->execute([':page_uid' => $page_uid]);
$questionRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$questions = [];
foreach ($questionRows as $row) {
    $questions[$row['chat_id']] = $row;
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>会話ログ</title>
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <meta name="robots" content="noindex, nofollow">

  <style>

  .chat {
    overflow: hidden;
    border-top: solid 1px #dcdcdc;
    cursor: pointer;
    line-height: 40px;
    vertical-align: middle;
    height: 40px;
  }

  .chat:hover {
    /* background-color: #dcdcdc */
  }
  .day {
    width: 130px;
    float:left
  }
  .question_text {
    float:left

  }
  .red {
    background-color: pink
  }
  .chat div {
    /* float: left; */
    /* min-width: 200px;
    height: 30px;
    line-height:30px; */
  }
  .content {
  display: block;
  background: #f8f8f8;
  padding: 0px;
}


.chat-row {
  display: flex;
  margin: 8px 0;
}

.bot {
  justify-content: flex-start;
}

.user {
  justify-content: flex-end;
}

.bubble {
  max-width: 70%;
  padding: 10px 14px;
  border-radius: 16px;
  line-height: 1.4;
  word-break: break-word;
}

.bot .bubble {
  background-color: #eef;
  border-top-left-radius: 0;
}

.user .bubble {
  background-color: #dcf8c6;
  border-top-right-radius: 0;
}

.chat-wrapper {
  position: relative;
  /* margin-bottom: 20px; */
}

.translate-all {
  position: absolute;
  top: 7px;
  right: 0;
}

.translate-all button {
  font-size: 12px;
  padding: 4px 8px;
  cursor: pointer;
  background: #eee;
  border: 1px solid #ccc;
  border-radius: 6px;
  color: #333
}


.chant_contents_wrap {
  padding: 10px
}

  </style>
<script>
  $(function(){

  });
</script>

</head>
<body>
  <?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>
  <div class="dashboard-container">

<?php include('../components/side_navi.php'); ?>
<?php
foreach ($chats as $key => $chat) {
  $createdAt = strtotime($chat['created_at']);

  $createdDate = date('Y-m-d', $createdAt);
  $today = date('Y-m-d');
  $yesterday = date('Y-m-d', strtotime('-1 day'));

  $chatYear = date('Y', $createdAt);
  $currentYear = date('Y');

  $time = date('H:i', $createdAt); // ← 追加：時刻だけ（例: 14:32）

  if ($createdDate === $today) {
    $chats[$key]['day'] = '今日 ' . $time;
  } elseif ($createdDate === $yesterday) {
    $chats[$key]['day'] = '昨日 ' . $time;
  } elseif ($chatYear === $currentYear) {
    $chats[$key]['day'] = date('n月j日', $createdAt) . ' ' . $time;
  } else {
    $chats[$key]['day'] = date('Y年n月j日', $createdAt);
  }
}
?>

<div id="app">
  <main>
  <h1>会話ログ</h1>

  <div v-for="(chat, index) in chats" :key="chat.chat_id" class=" chat-wrapper" >

    <div class="chat" @click=" activeIndex = (activeIndex === chat.chat_id) ? null : chat.chat_id ">
      <div class="day">{{ chat.day }}</div>
      <div class="question_text">{{ questions[chat.chat_id]?.question || '' }}</div>
    </div>

      <div class="content" v-if="activeIndex === chat.chat_id">
        <div class="translate-all">
          <button @click="translateAll(chat)">🌐 翻訳</button>
        </div>

        <div
          v-for="(cnv, index) in parseConversation(chat.conversation)"
          :key="index"
          :class="['chat-row', 'chant_contents_wrap', cnv.sender]"
        >
          <div class="bubble">{{ cnv.text }}</div>
        </div>
      </div>
  </div>
</main>
</div>
</div>

  <script>
  const { createApp } = Vue;

  createApp({
    data() {
      return {
        activeIndex: null,
        rawQuestions: <?= json_encode($questions, JSON_UNESCAPED_UNICODE) ?>,
        chats: <?= json_encode($chats, JSON_UNESCAPED_UNICODE) ?>,
        questions: {},
      };
    },
    mounted() {
      // lodash で key を chat_id に変換
      this.questions = _.keyBy(this.rawQuestions, 'chat_id');
    },
    methods: {
      parseConversation(convo) {
        try {
          return typeof convo === 'string' ? JSON.parse(convo) : convo || [];
        } catch (e) {
          console.warn('JSON parse error:', convo);
          return [];
        }
      },
      async translateAll(chat) {
        try {
          const res = await fetch('translate.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              conversation: this.parseConversation(chat.conversation)
            })
          });
          const translated = await res.json();
          chat.conversation = translated;
        } catch (e) {
          console.error("翻訳エラー:", e);
          // alert("翻訳に失敗しました");
        }
      },
    }
  }).mount('#app');
  </script>


</body>
</html>
