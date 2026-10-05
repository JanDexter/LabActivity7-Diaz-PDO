<?php
require 'db.php';
require_login();

// deleting only works through POST, so bounce anything else
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT user_id, post_id FROM comments WHERE id = ?');
$stmt->execute([$id]);
$comment = $stmt->fetch();

if (!$comment) {
    header('Location: index.php');
    exit;
}
if ((int) $comment['user_id'] !== (int) $_SESSION['user_id']) {
    http_response_code(403);
    exit('You can only delete your own comments.');
}

$stmt = $pdo->prepare('DELETE FROM comments WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);

header('Location: index.php#post-' . (int) $comment['post_id']);
exit;
