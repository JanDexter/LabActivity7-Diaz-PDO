<?php
require 'db.php';
require_login();

$errors = [];   // error message keyed by post id
$drafts = [];   // submitted text keyed by post id, so it survives a failed submit
$editPostId = (int) ($_GET['edit_post'] ?? 0);
$editCommentId = (int) ($_GET['edit_comment'] ?? 0);
$failed = '';   // which form the error belongs to: add_comment | save_post | save_comment

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $postId = (int) ($_POST['post_id'] ?? 0);
    $uid = $_SESSION['user_id'];

    if ($action === 'add_comment') {
        $body = trim($_POST['body'] ?? '');
        $exists = $pdo->prepare('SELECT 1 FROM posts WHERE id = ?');
        $exists->execute([$postId]);
        if (!$exists->fetchColumn()) {
            header('Location: index.php');
            exit;
        }
        if ($body === '') {
            $errors[$postId] = 'Comment is required.';
            $failed = $action;
        } else {
            $pdo->prepare('INSERT INTO comments (post_id, user_id, body, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())')
                ->execute([$postId, $uid, $body]);
            header('Location: index.php#post-' . $postId);
            exit;
        }
    } elseif ($action === 'save_post') {
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        if ($title === '' || $body === '') {
            $errors[$postId] = 'Title and body are required.';
            $drafts[$postId] = ['title' => $title, 'body' => $body];
            $editPostId = $postId;
            $failed = $action;
        } else {
            // user_id in the WHERE clause keeps this author-only
            $pdo->prepare('UPDATE posts SET title = ?, body = ?, updated_at = NOW() WHERE id = ? AND user_id = ?')
                ->execute([$title, $body, $postId, $uid]);
            header('Location: index.php#post-' . $postId);
            exit;
        }
    } elseif ($action === 'save_comment') {
        // same author-only rule as posts, enforced in the UPDATE below
        $commentId = (int) ($_POST['comment_id'] ?? 0);
        $body = trim($_POST['body'] ?? '');
        if ($body === '') {
            $errors[$postId] = 'Comment is required.';
            $editCommentId = $commentId;
            $failed = $action;
        } else {
            $pdo->prepare('UPDATE comments SET body = ?, updated_at = NOW() WHERE id = ? AND user_id = ?')
                ->execute([$body, $commentId, $uid]);
            header('Location: index.php#post-' . $postId);
            exit;
        }
    }
    // keep what the user typed in the comment box when validation failed
    if ($failed === 'add_comment' || $failed === 'save_comment') {
        $drafts[$postId] = $body;
    }
}

// newest posts first
$stmt = $pdo->prepare(
    'SELECT p.id, p.user_id, p.title, p.body, p.created_at, p.updated_at, u.name AS author
     FROM posts p JOIN users u ON u.id = p.user_id
     ORDER BY p.created_at DESC, p.id DESC'
);
$stmt->execute();
$posts = $stmt->fetchAll();

// all comments for a post, oldest first so the conversation reads in order
$comments = $pdo->prepare(
    'SELECT c.*, u.name AS author FROM comments c JOIN users u ON u.id = c.user_id
     WHERE c.post_id = ? ORDER BY c.created_at ASC, c.id ASC'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>News Feed</title>
    <script src="validate.js"></script>
</head>
<body>
<?php render_nav(); ?>
<h1>News Feed</h1>
<?php if (!$posts): ?>
    <p>No posts yet.</p>
<?php endif; ?>
<?php foreach ($posts as $post): ?>
    <?php
    $pid = (int) $post['id'];
    $isOwner = is_current_user($post['user_id']);
    $comments->execute([$pid]);
    $postComments = $comments->fetchAll();
    ?>
    <div id="post-<?= $pid ?>">
    <?php if ($isOwner && $editPostId === $pid): ?>
        <?php $d = $failed === 'save_post' && isset($drafts[$pid]) ? $drafts[$pid] : ['title' => $post['title'], 'body' => $post['body']]; ?>
        <form method="post" action="index.php#post-<?= $pid ?>" novalidate data-validate>
            <input type="hidden" name="action" value="save_post">
            <input type="hidden" name="post_id" value="<?= $pid ?>">
            <p><input type="text" name="title" value="<?= e($d['title']) ?>" data-rules="required"></p>
            <p><textarea name="body" data-rules="required"><?= e($d['body']) ?></textarea></p>
            <?php if ($failed === 'save_post' && isset($errors[$pid])): ?><p><?= e($errors[$pid]) ?></p><?php endif; ?>
            <button type="submit">Save</button>
            <a href="index.php#post-<?= $pid ?>">Cancel</a>
        </form>
    <?php else: ?>
        <h2><?= e($post['title']) ?></h2>
        <p>
            By <?= e($post['author']) ?> &middot; <?= e($post['created_at']) ?>
            <?php if ($post['updated_at'] !== $post['created_at']): ?>
                (edited <?= e($post['updated_at']) ?>)
            <?php endif; ?>
        </p>
        <p><?= e($post['body']) ?></p>
        <?php if ($isOwner): ?>
            <a href="index.php?edit_post=<?= $pid ?>#post-<?= $pid ?>">Edit Post</a>
            <form method="post" action="delete_post.php" onsubmit="return confirm('Delete this post and all its comments?');">
                <input type="hidden" name="id" value="<?= $pid ?>">
                <button type="submit">Delete Post</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <h3>Comments (<?= count($postComments) ?>)</h3>
    <?php if (!$postComments): ?><p>No comments yet.</p><?php endif; ?>
    <?php foreach ($postComments as $c): ?>
        <?php $cid = (int) $c['id']; $mine = is_current_user($c['user_id']); ?>
        <?php if ($mine && $editCommentId === $cid): ?>
            <form method="post" action="index.php#post-<?= $pid ?>" novalidate data-validate>
                <input type="hidden" name="action" value="save_comment">
                <input type="hidden" name="post_id" value="<?= $pid ?>">
                <input type="hidden" name="comment_id" value="<?= $cid ?>">
                <p><textarea name="body" data-rules="required"><?= e($failed === 'save_comment' ? ($drafts[$pid] ?? '') : $c['body']) ?></textarea></p>
                <?php if ($failed === 'save_comment' && isset($errors[$pid])): ?><p><?= e($errors[$pid]) ?></p><?php endif; ?>
                <button type="submit">Save</button>
                <a href="index.php#post-<?= $pid ?>">Cancel</a>
            </form>
        <?php else: ?>
            <p>
                <?= e($c['author']) ?> &middot; <?= e($c['created_at']) ?>
                <?php if ($c['updated_at'] !== $c['created_at']): ?>
                    (edited <?= e($c['updated_at']) ?>)
                <?php endif; ?>
            </p>
            <p><?= e($c['body']) ?></p>
            <?php if ($mine): ?>
                <a href="index.php?edit_comment=<?= $cid ?>#post-<?= $pid ?>">Edit Comment</a>
                <form method="post" action="delete_comment.php" onsubmit="return confirm('Delete this comment?');">
                    <input type="hidden" name="id" value="<?= $cid ?>">
                    <button type="submit">Delete Comment</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    <?php endforeach; ?>

    <form method="post" action="index.php#post-<?= $pid ?>" novalidate data-validate>
        <input type="hidden" name="action" value="add_comment">
        <input type="hidden" name="post_id" value="<?= $pid ?>">
        <p><textarea name="body" placeholder="Write a comment..." data-rules="required"><?= e($failed === 'add_comment' ? ($drafts[$pid] ?? '') : '') ?></textarea></p>
        <?php if ($failed === 'add_comment' && isset($errors[$pid])): ?><p><?= e($errors[$pid]) ?></p><?php endif; ?>
        <button type="submit">Comment</button>
    </form>
    </div>
    <hr>
<?php endforeach; ?>
</body>
</html>
