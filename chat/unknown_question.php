<?php
include('../core/db.php');

// POSTデータを受け取る
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ユーザーから送られたわからなかった内容を受け取る
    $unknownContent = isset($_POST['unknownContent']) ? $_POST['unknownContent'] : "" ;
    $chat_id = $_POST['chat_id'];
    $page_uid = $_POST['page_uid'];

    // 入力チェック：もし内容が空でない場合にデータを保存
    if (!empty($unknownContent)) {
        // データベースに保存するためのSQL文
        $stmt = $mysqli->prepare("INSERT INTO question (question, state, chat_id, page_uid) VALUES ( ?, 'unanswered', ?, ?)");
        // 空の回答として送る

        // パラメータをバインド
        $stmt->bind_param("sss", $unknownContent, $chat_id, $page_uid );  // questionとanswerの2つのパラメータ

        // 実行
        if ($stmt->execute()) {
            // 保存成功のメッセージ
            echo json_encode(["success" => true, "message" => "わからなかった質問が保存されました"]);
        } else {
            // エラーメッセージ
            echo json_encode(["success" => false, "message" => "質問の保存に失敗しました"]);
        }

        // ステートメントを閉じる
        $stmt->close();
    } else {
        // 内容が空の場合
        echo json_encode(["success" => false, "message" => "わからなかった内容が空です"]);
    }
}

// DB接続終了
$mysqli->close();
?>
