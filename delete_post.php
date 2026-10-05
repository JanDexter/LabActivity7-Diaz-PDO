<?php
require 'db.php';
require_login();

// deleting only works through POST, so bounce anything else
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT user_id FROM posts WHERE id = ?');
$stmt->execute([$id]);
$post = $stmt->fetch();

if ($post) {
    if ((int) $post['user_id'] !== (int) $_SESSION['user_id']) {
        http_response_code(403);
        exit('403 Forbidden: you can only delete your own posts.');
    }
    // the comments go away on their own thanks to on delete cascade
    $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $_SESSION['user_id']]);
}

header('Location: index.php');
exit;
