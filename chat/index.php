<?php
include_once(  dirname(__DIR__) . '/compornents/php_header.php');

if (session_status() == PHP_SESSION_NONE) {
    // セッションは有効で、開始していないとき
    session_start();
}


$chat_id = random();

$first_message  = "こんにちは！質問があればどうぞ！";
$placeholder = "メッセージを入力";
if ($_SESSION['lang'] == "EN") {
    $first_message = "Hello! Feel free to ask any questions!";
    $placeholder = "Enter your message";
}

$_SESSION['conversation_history'] = null;

// dd($design);
$icon = isset($design['logo_base64']) && $design['logo_base64']
  ? 'data:image/png;base64,' . $design['logo_base64']
  : '/assets/images/default_icon.png';
?>

<div id="chat-container">
    <div id="chat-app">
        <div class="chat-box" ref="chatBox">
            <div v-for="msg in messages" class="message_wrap">
                <div v-if="msg.sender == 'bot'" :class="msg.sender + '_icon'">
                    <img :src="icon">
                </div>
                <!-- msg.sender == 'bot' の場合にだけ v-html を使ってHTMLを表示 -->
                <div v-if="msg.sender == 'bot'" :class="['message', msg.sender]" v-html="msg.text"></div>
                <!-- 他のメッセージ（ユーザーなど）は通常通り表示 -->
                <div v-else :class="['message', msg.sender]">{{ msg.text }}</div>
            </div>
        </div>
        <div class="input-box">
            <input type="text" v-model="userMessage" @keypress.enter="sendMessage" placeholder="<?= $placeholder ?>">
            <button @click="sendMessage"><span class="material-symbols-outlined">send</span></button>
        </div>
    </div>
</div>


<script>
const { createApp, nextTick } = Vue;
createApp({
    data() {
        return {
            userMessage: "",
            chatId: '<?= $chat_id ?>',
            messages: [{ sender: "bot", text: "<?= $first_message ?>" }],
            apiUrl: "/chat/api.php",  // サーバーサイドのURL,
            pageUid: "<?= htmlspecialchars($_GET['page_uid'] ?? '', ENT_QUOTES, 'UTF-8') ?>",
            icon: '<?= $icon ?>',
        };
    },
    methods: {
      async sendMessage() {
          if (!this.userMessage.trim()) return;
          this.messages.push({ sender: "user", text: this.userMessage });

          const userInput = this.userMessage;
          this.userMessage = ""; // 入力欄をクリア

          var that = this

          try {
              const response = await axios.post(this.apiUrl, {
                  message: userInput , // ユーザーのメッセージをサーバーに送る
                  pageUid: "<?= htmlspecialchars($_GET['page_uid'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              })

              console.log(response.data)
              let botMessage = response.data.message || "申し訳ありませんが、回答が見つかりませんでした。:index";

              const hashIndex = botMessage.indexOf('#');
              if (hashIndex !== -1) {

                const unknownContent = botMessage.slice(hashIndex + 1).trim(); // #以降のわからなかった内容
                that.sendunknownContent(unknownContent)
                botMessage = botMessage.slice(0, hashIndex).trim();
              }

              this.messages.push({ sender: "bot", text: botMessage }); // ゲストに表示するメッセージ
              this.saveLog()

          } catch (error) {
              console.error("通信エラー:", error);
              this.messages.push({ sender: "bot", text: "通信エラーが発生しました。" });
          }

          // メッセージが追加された後にスクロール
          await nextTick();
          this.scrollToBottom();
      },
      sendunknownContent (unknownContent) {
          var that = this
          // axios.post('/chat/chat_log.php',
          axios.post('/chat/unknown_question.php',
              new URLSearchParams({
                chat_id: that.chatId,
                unknownContent: unknownContent,  // わからなかった内容を送る
                page_uid: that.pageUid
              })
          )
          .then(res => {
              console.log(res.data); // サーバーのレスポンスを表示
          })
          .catch(err => {
              console.error('送信エラー:', err); // エラーハンドリング
          });
      },
      saveLog () {
        var that = this
        axios.post('/chat/chat_log.php',
        // axios.post('/chat/unknown_question.php',
            new URLSearchParams({
              chat_id: that.chatId,
              conversation: JSON.stringify(this.messages),
              page_uid: that.pageUid
            })
        )
        .then(res => {
            console.log(res.data); // サーバーのレスポンスを表示
        })
        .catch(err => {
            console.error('送信エラー:', err); // エラーハンドリング
        });
      },
        scrollToBottom() {
            const chatBox = this.$refs.chatBox;
            if (chatBox) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        }
    }
}).mount("#chat-app");
</script>
