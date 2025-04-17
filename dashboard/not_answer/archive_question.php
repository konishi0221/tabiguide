<?php
require_once '../../db.php'; // DB接続

// POST受け取り
$questionId = $_POST['question_id'] ?? '';
$mode = $_POST['mode'] ?? '';

if (empty($questionId)) {
    die('質問IDが未指定です');
}

// 質問IDに基づいて状態をアーカイブに更新する処理
if ($mode == "archive") {
  $stmt = $mysqli->prepare("UPDATE question SET state = 'archive' WHERE id = ?");

} else {
  $stmt = $mysqli->prepare("UPDATE question SET state = 'unanswered' WHERE id = ?");
}

$stmt->bind_param("i", $questionId);

// 実行とエラーチェック
if ($stmt->execute()) {
    // アーカイブ成功後にリダイレクト
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    echo "エラーが発生しました。";
}
?>
