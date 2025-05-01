<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

$page_uid = $_GET['page_uid'] ?? '';
$user_uid = $_SESSION['user']['uid'] ?? '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>チャット設定</title>
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <style>
    .chat-settings {
      max-width: 800px;
      margin: 0 auto;
      padding: 2rem;
    }
    .form-group {
      margin-bottom: 1.5rem;
    }
    label {
      display: block;
      margin-bottom: 0.5rem;
      color: #333;
    }
    select, input[type="text"] {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      margin-bottom: 1rem;
    }
    .btn-primary {
      background: #007bff;
      color: white;
      border: none;
      padding: 0.5rem 1.5rem;
      border-radius: 4px;
      cursor: pointer;
    }
    .btn-primary:hover {
      background: #0056b3;
    }
    .preview {
      background: #f8f9fa;
      padding: 1rem;
      border-radius: 8px;
      margin-top: 1rem;
    }
  </style>
</head>
<body>
  <?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>

  <div class="dashboard-container">
    <?php include(dirname(__DIR__) . '/components/side_navi.php'); ?>
    
    <div id="app" class="container">
      <main>

          <h1>チャット設定</h1>
          
          <section id="chat_setting" class="" data-uid="<?= htmlspecialchars($page_uid) ?>">
            <form method="post" action="chat_save.php">
              <div class="form-group">
                <input type="hidden" name="page_uid" value="<?= htmlspecialchars($page_uid) ?>">

                <label for="char">チャットの性格</label>
                <select id="char" name="chat_charactor" v-model="type" required>
                  <optgroup label="ベーシック">
                    <option>ふつうの丁寧語</option>
                    <option>フレンドリー＆丁寧</option>
                    <option>ホテルコンシェルジュ風（プロフェッショナル）</option>
                    <option>旅館の女将風（上品で温かい）</option>
                    <option>民泊オーナー風（ローカル情報豊富）</option>
                    <option>キャンプ場オーナー風（アウトドア好き）</option>
                    <option>優しいお姉さん風（明るく親身）</option>
                    <option>おかあさん風（包容力）</option>
                    <option>執事風（格式高く落ち着き）</option>
                    <option>上品なおばあさま風（穏やか）</option>
                    <option>子供っぽい元気キャラ（語尾「〜だよ！」）</option>
                  </optgroup>

                  <optgroup label="エンタメ">
                    <option>侍口調（ござる、拙者）</option>
                    <option>海賊キャラ（豪快語尾「〜だッ！」）</option>
                    <option>禅僧風（悟り系）</option>
                    <option>芸人ツッコミ系（ユーモア多め）</option>
                    <option>太宰治風（憂い・皮肉・太宰治になりきる）</option>
                    <option>宇宙人キャラ（片言・未知の比喩）</option>
                  </optgroup>
                </select>

                <label for="first">最初のメッセージ</label>

                
                <input id="first" type="text" name="first_message"
                      v-model="firstMessage"
                      maxlength="120">
                      <br>
                <button type="submit" class="btn-primary">保存</button>
              </div>
            </form>
          </section>
      </main>
    </div>
  </div>

  <script>
  const { createApp, watch } = Vue

  createApp({
    data() {
      return {
        type: 'ふつうの丁寧',
        firstMessage: 'こんにちは。ご不明点がございましたら、どうぞお気軽にお尋ねくださいませ。',
        greetings: {
          'ふつうの丁寧語': 'こんにちは。ご不明点がございましたら、どうぞお気軽にお尋ねくださいませ。',
          'フレンドリー＆丁寧': 'こんにちは！気になることがあれば何でも聞いてくださいね。すぐにお手伝いします♪',
          'ホテルコンシェルジュ風（プロフェッショナル）': 'ようこそ当ホテルへ。チェックインや周辺案内など、ご要望は何なりとお申し付けくださいませ。',
          '旅館の女将風（上品で温かい）': 'いらっしゃいませ。お部屋やお風呂、観光情報など、ご入り用の際はどうぞお声掛けください。',
          '民泊オーナー風（ローカル情報豊富）': 'ようこそ！近くのおすすめグルメや観光スポットもご案内できますので、遠慮なくご相談くださいね。',
          'キャンプ場オーナー風（アウトドア好き）': 'こんにちは！サイトの設備や焚き火のコツなど、アウトドアの質問は何でもどうぞ～！',
          '優しいお姉さん風（明るく親身）': '困ったときは私に聞いてね!できるだけ分かりやすくお答えします♪',
          'おかあさん風（包容力）': 'こんにちは。分からないことがあったら何でも言ってくださいね。しっかりサポートしますよ。',
          '執事風（格式高く落ち着き）': 'ご用命がございましたら、お気軽にお申し付けくださいませ。迅速に対応いたします。',
          '上品なおばあさま風（穏やか）': 'いらっしゃいませ。よろしければお手伝いさせていただきますので、遠慮なくどうぞ。',
          '子供っぽい元気キャラ（語尾「〜だよ！」）': 'やあ！分からないことは何でも聞いてね！ぼくが説明するよ！',
          '侍口調（ござる、拙者）': '拙者にお任せくだされ。宿や周辺のこと、何でもご指南いたす！',
          '海賊キャラ（豪快語尾「〜だッ！」）': 'よぉ相棒！分からないことがあったら遠慮なく聞くんだッ！手助けしてやるぜ！',
          '禅僧風（悟り系）': 'お困りごとの道を、共に探してまいりましょう。どうぞお尋ねください。',
          '芸人ツッコミ系（ユーモア多め）': 'どうもー！疑問があれば遠慮なくツッコんでや～！しっかり返すで！',
          '太宰治風（憂い・皮肉・太宰治になりきる）': 'ああ…人生はままならぬもの。されど、あなたの問いに私は答えねばならぬ。どうぞ…',
          '宇宙人キャラ（片言・未知の比喩）': 'ピポパ…地球ノ疑問、ワタシ解析スル。質問、ドウゾ。'
        }
      }
    },
    async mounted() {
      const uid = document.getElementById('chat_setting').dataset.uid
      const json = await fetch(`/api/chat_setting.php?page_uid=${uid}`).then(r => r.json())

      this.type = json.chat_charactor || 'ふつうの丁寧語'
      this.firstMessage = json.chat_first_message
        ? json.chat_first_message
        : this.greetings[this.type]

      this.$watch(
        () => this.type,
        newVal => {
          this.firstMessage = this.greetings[newVal] || ''
        }
      )
    }
  }).mount('#chat_setting')
  </script>
</body>
</html> 