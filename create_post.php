<?php
require 'db.php';
require_login();

$errors = [];
$title = '';
$body = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');

    // make sure neither field was left blank
    if ($title === '') {
        $errors['title'] = 'Title is required.';
    }
    if ($body === '') {
        $errors['body'] = 'Body is required.';
    }

    // only save the post if everything checks out
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO posts (user_id, title, body, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
        $stmt->execute([$_SESSION['user_id'], $title, $body]);
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Post</title>
    <script src="validate.js"></script>
</head>
<body>
<?php render_nav(); ?>
<h1>Create Post</h1>
<form method="post" action="create_post.php" novalidate data-validate>
    <p>
        <label>Title<br>
            <input type="text" name="title" value="<?= e($title) ?>" data-rules="required">
        </label>
        <?php if (isset($errors['title'])): ?><br><?= e($errors['title']) ?><?php endif; ?>
    </p>
    <p>
        <label>Body<br>
            <textarea name="body" data-rules="required"><?= e($body) ?></textarea>
        </label>
        <?php if (isset($errors['body'])): ?><br><?= e($errors['body']) ?><?php endif; ?>
    </p>
    <button type="submit">Publish</button>
</form>
</body>
</html>
