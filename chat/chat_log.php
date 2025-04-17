<?php

include('../core/db.php');

// POSTデータを受け取る
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ユーザーから送られたわからなかった内容を受け取る
    $conversation = $_POST['conversation'];
    $chat_id = $_POST['chat_id'];
    $page_uid = $_POST['page_uid'];

    // 入力チェック：もし内容が空でない場合にデータを保存
        // まず chat_id が存在するか確認
        $stmt = $mysqli->prepare("SELECT id FROM chat_log WHERE chat_id = ?");
        $stmt->bind_param("s", $chat_id);  // chat_idで検索
        $stmt->execute();
        $stmt->store_result();

        // chat_id が存在する場合
        if ($stmt->num_rows > 0) {
            // 更新処理
            $stmt->close();
            $stmt = $mysqli->prepare("UPDATE chat_log SET conversation = ?, state = 'unanswered' , page_uid = ? WHERE chat_id = ?");
            $stmt->bind_param("sss", $conversation, $page_uid , $chat_id);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "質問が更新されました"]);
            } else {
                echo json_encode(["success" => false, "message" => "質問の更新に失敗しました"]);
            }
        } else {
            // chat_id が存在しない場合、新規挿入
            $stmt->close();
            $stmt = $mysqli->prepare("INSERT INTO chat_log ( state, conversation, chat_id ,page_uid) VALUES ('unanswered', ?, ?, ?)");
            $answer = '';  // 空の回答
            $stmt->bind_param("sss", $conversation, $chat_id, $page_uid);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "質問が保存されました"]);
            } else {
                echo json_encode(["success" => false, "message" => "質問の保存に失敗しました"]);
            }
        }

        // ステートメントを閉じる
        $stmt->close();

}

// DB接続終了
$mysqli->close();
