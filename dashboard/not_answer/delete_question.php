<?php
require_once '../../db.php'; // DB接続

// POST受け取り
$questionId = $_POST['question_id'] ?? '';

if (empty($questionId)) {
    die('質問IDが未指定です');
}

// 質問IDに基づいて削除処理
$stmt = $mysqli->prepare("DELETE FROM question WHERE id = ?");
$stmt->bind_param("i", $questionId);

// 実行とエラーチェック
if ($stmt->execute()) {
    // 削除成功後にリダイレクト
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    echo "エラーが発生しました。";
}
?>
