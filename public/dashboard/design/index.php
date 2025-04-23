<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

$page_uid = $_GET['page_uid'] ?? '';
$user_uid = $_SESSION['user']['uid'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM design WHERE page_uid = ? LIMIT 1");
$stmt->execute([$page_uid]);
$design = $stmt->fetch(PDO::FETCH_ASSOC);

$defaults = [
  'primary_color' => '#000000',
  'secondary_color' => '#6b6b6b',
  'background_color' => '#f5f5f5',
  'text_color' => '#333333',
  'font_family' => 'system-ui, sans-serif',
  'button_radius' => 4,
  'chat_bubble_color_user' => '#e0e0e0',
  'chat_bubble_color_ai' => '#ffffff',
  'dark_mode' => 0
];


$design = array_merge($defaults, $design ?: []);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ユーザー画面設定</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP&family=M+PLUS+Rounded+1c&family=Roboto&family=Zen+Kaku+Gothic+New&family=Shippori+Mincho+B1&family=Kosugi+Maru&family=BIZ+UDPGothic&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <style>
    input[type=color], input[type=text], select, textarea {
      margin-bottom: 1em;
      width: 100%;
      max-width: 400px;
    }
  </style>
</head>
<body>
  <?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>

<div class="dashboard-container">

<?php include('../components/side_navi.php'); ?>
  <div id="app" class="container">
    <main>
  <h1>ユーザー画面設定</h1>


  <section id="chat_setting"
           class="setting-section"
           data-uid="<?= htmlspecialchars($page_uid) ?>">   <!-- ★ 追加 -->
    <form method="post" action="chat_save.php">
      <h2>チャット設定</h2>
      <input type="hidden" name="page_uid" value="<?= htmlspecialchars($page_uid) ?>">

      <label for="char">チャットの性格</label>
      <!-- v‑model でバインド。value 属性は付けない -->
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
             maxlength="120" style="width:100%"><br>

      <button type="submit" class="btn-primary" style="margin-top:10px;">保存</button>
    </form>
  </section>



  <script>
  const { createApp, watch } = Vue

  createApp({
    data(){
      return {
        /* 初期選択（表示文字列そのまま） */
        type: '',
        /* あいさつ文（v-model で入力欄と双方向） */
        firstMessage: '',
        /* ラベル → あいさつ文 のマップも日本語キーで統一 */
        greetings: {
          'ふつうの丁寧語'                       : 'こんにちは。ご不明点がございましたら、どうぞお気軽にお尋ねくださいませ。',
          'フレンドリー＆丁寧'                  : 'こんにちは！気になることがあれば何でも聞いてくださいね。すぐにお手伝いします♪',

          /* ホテル / 旅館系 */
          'ホテルコンシェルジュ風（プロフェッショナル）' : 'ようこそ当ホテルへ。チェックインや周辺案内など、ご要望は何なりとお申し付けくださいませ。',
          '旅館の女将風（上品で温かい）'        : 'いらっしゃいませ。お部屋やお風呂、観光情報など、ご入り用の際はどうぞお声掛けください。',
          '民泊オーナー風（ローカル情報豊富）'  : 'ようこそ！近くのおすすめグルメや観光スポットもご案内できますので、遠慮なくご相談くださいね。',
          'キャンプ場オーナー風（アウトドア好き）': 'こんにちは！サイトの設備や焚き火のコツなど、アウトドアの質問は何でもどうぞ～！',

          /* キャラクター系（親しみやすさ重視） */
          '優しいお姉さん風（明るく親身）'      : '困ったときは私に聞いてね!できるだけ分かりやすくお答えします♪',
          'おかあさん風（包容力）'             : 'こんにちは。分からないことがあったら何でも言ってくださいね。しっかりサポートしますよ。',
          '執事風（格式高く落ち着き）'         : 'ご用命がございましたら、お気軽にお申し付けくださいませ。迅速に対応いたします。',
          '上品なおばあさま風（穏やか）'       : 'いらっしゃいませ。よろしければお手伝いさせていただきますので、遠慮なくどうぞ。',

          /* ユニーク系（遊び心は残しつつ実用的に） */
          '子供っぽい元気キャラ（語尾「〜だよ！」）' : 'やあ！分からないことは何でも聞いてね！ぼくが説明するよ！',
          '侍口調（ござる、拙者）'             : '拙者にお任せくだされ。宿や周辺のこと、何でもご指南いたす！',
          '海賊キャラ（豪快語尾「〜だッ！」）'  : 'よぉ相棒！分からないことがあったら遠慮なく聞くんだッ！手助けしてやるぜ！',
          '禅僧風（悟り系）'                   : 'お困りごとの道を、共に探してまいりましょう。どうぞお尋ねください。',
          '芸人ツッコミ系（ユーモア多め）'      : 'どうもー！疑問があれば遠慮なくツッコんでや～！しっかり返すで！',
          '太宰治風（憂い・皮肉・太宰治になりきる）' : 'ああ…人生はままならぬもの。されど、あなたの問いに私は答えねばならぬ。どうぞ…',
          '宇宙人キャラ（片言・未知の比喩）'     : 'ピポパ…地球ノ疑問、ワタシ解析スル。質問、ドウゾ。'
        }
      }
    },
    async mounted(){
  const uid = document
                .getElementById('chat_setting')
                .dataset.uid
  try{
    const res = await fetch(`/api/chat_setting.php?page_uid=${uid}`)
    const json = await res.json()

    /* DB に登録済みなら上書き、無ければデフォルト */
    this.type         = json.chat_charactor     || 'ふつうの丁寧語'
    this.firstMessage = json.chat_first_message || 'こんにちは！ご質問があればどうぞ！'

  }catch(e){
    console.warn('chat_setting fetch error', e)
    this.type         = 'ふつうの丁寧語'
    this.firstMessage = 'こんにちは！ご質問があればどうぞ！'
  }
},
    watch:{
      /* 選択が変わればあいさつ文を自動セット */
      type(newVal){
        if (this.greetings[newVal] !== undefined) {
          this.firstMessage = this.greetings[newVal]
        }
      }
    }
  }).mount('#chat_setting')
  </script>








    <section class="setting-section">
      <h2>デザイン設定</h2>

  <form method="post" action="save.php" enctype="multipart/form-data">
    <input type="hidden" name="page_uid" value="<?= htmlspecialchars($page_uid) ?>">

    <div class="design-section">
      <label>テーマカラー</label>
      <input type="color" name="primary_color" value="<?= !empty($design['primary_color']) ? $design['primary_color'] : $defaults['primary_color'] ?>">

      <label>アクセントカラー</label>
      <input type="color" name="secondary_color" value="<?= !empty($design['secondary_color']) ? $design['secondary_color'] : $defaults['secondary_color'] ?>">

      <label>背景色</label>
      <input type="color" name="background_color" value="<?= !empty($design['background_color']) ? $design['background_color'] : $defaults['background_color'] ?>">
    </div>

    <div class="design-section">
      <label>テキスト色</label>
      <input type="color" name="text_color" value="<?= !empty($design['text_color']) ? $design['text_color'] : $defaults['text_color'] ?>">

      <label>ユーザー吹き出し色</label>
      <input type="color" name="chat_bubble_color_user" value="<?= !empty($design['chat_bubble_color_user']) ? $design['chat_bubble_color_user'] : $defaults['chat_bubble_color_user'] ?>">

      <label>AI吹き出し色</label>
      <input type="color" name="chat_bubble_color_ai" value="<?= !empty($design['chat_bubble_color_ai']) ? $design['chat_bubble_color_ai'] : $defaults['chat_bubble_color_ai'] ?>">
    </div>

    <div class="design-section">
      <?php
      $font_options = [
        'system-ui, sans-serif' => '標準（system-ui）',
        '"Noto Sans JP", sans-serif' => 'Noto Sans JP（読みやすくモダン）',
        '"M PLUS Rounded 1c", sans-serif' => 'M PLUS Rounded（丸み・柔らか）',
        '"Roboto", sans-serif' => 'Roboto（定番サンセリフ）',
        '"Zen Kaku Gothic New", sans-serif' => 'Zen Kaku Gothic（和文ゴシック）',
        '"Shippori Mincho B1", serif' => 'Shippori Mincho（明朝体）',
        '"Kosugi Maru", sans-serif' => 'Kosugi Maru（可愛くて丸い）',
        '"BIZ UDPGothic", sans-serif' => 'BIZ UDPゴシック（企業向け）',
      ];
      $current_font = !empty($design['font_family']) ? $design['font_family'] : $defaults['font_family'];
      ?>
    </div>
    <div class="design-section">

      <label>フォント</label><br>
      <?php foreach ($font_options as $value => $label): ?>
        <label style="font-family: <?= htmlspecialchars($value) ?> !important; display: block; margin-bottom: 5px;">
          <input type="radio" name="font_family" value="<?= htmlspecialchars($value) ?>"
            <?= $current_font === $value ? 'checked' : '' ?>>
          <?= htmlspecialchars($label) ?>
        </label>
      <?php endforeach; ?>

      <label>ボタン角丸(px)</label>
      <input type="number" name="button_radius" value="<?= isset($design['button_radius']) ? $design['button_radius'] : $defaults['button_radius'] ?>" min="0">

      <label>ダークモード</label>
      <select name="dark_mode">
        <option value="0" <?= empty($design['dark_mode']) ? 'selected' : '' ?>>オフ</option>
        <option value="1" <?= !empty($design['dark_mode']) ? 'selected' : '' ?>>オン</option>
      </select>
    </div>

    <?php if (!empty($design['logo_base64'])): ?>
      <div style="margin-bottom:10px;">
        <label>現在のロゴ画像：</label><br>
        <img src="data:image/png;base64,<?= $design['logo_base64'] ?>" width="50" height="50" style="border:1px solid #ccc; border-radius:8px;">
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_logo'): ?>
      <div style="color:red; margin-bottom: 1em;">
        ロゴ画像は PNG / JPG / GIF 形式の画像にしてください。
      </div>
    <?php endif; ?>


    <div class="design-section">
      <label>ロゴ画像（50x50 PNG）</label>
      <input type="file" name="logo_image">
    </div>

    <button type="submit">保存</button>
  </form>

</section>

</main>
</div>
</div>
</body>
</html>

<style>
.design-section {
  margin-bottom: 2em;
}
.design-section label {
  display: block;
  font-weight: bold;
  margin-top: 0.5em;
}
.design-section input[type="text"],
.design-section input[type="number"],
.design-section input[type="color"],
.design-section select {
  width: 300px;
  padding: 6px;
  margin-bottom: 0.5em;
}
.design-section input[type="file"] {
  margin-top: 0.3em;
}

.design-section input[type="color"]{
  height: 50px;
  width: 60px
}
button {
  padding: 8px 16px;
  font-weight: bold;
  background-color: #222;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
</style>
