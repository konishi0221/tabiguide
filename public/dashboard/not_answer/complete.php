<?php
require_once '../../db.php';

$question_id = $_POST['question_id'] ?? ''; // 質問IDを取得
$answer = $_POST['answer'] ?? ''; // 回答を取得
$state = $_POST['state'] ?? 'answered'; // stateを取得（デフォルトは「回答済み」）

if (!$question_id || !$answer) {
    die("質問IDまたは回答が未指定です");
}

// 質問IDとstate、answerを一度に更新
$stmt = $mysqli->prepare("UPDATE question SET answer = ?, state = ? WHERE id = ?");
$stmt->bind_param("ssi", $answer, $state, $question_id);
$stmt->execute();
$stmt->close();

// 完了後リダイレクト
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>
