<?php
declare(strict_types=1);

$pageUid   = $_GET['page_uid']   ?? '';
$sessionId = $_GET['session_id'] ?? '';

// Fallback: if params missing, show minimal error
if ($pageUid === '') {
    echo 'Missing page_uid';
    exit;
}

// You can log or process session_id here if needed

header("Location: /dashboard/billing/index.php?page_uid={$pageUid}");
exit;
