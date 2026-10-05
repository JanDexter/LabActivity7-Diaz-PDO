<?php
require 'db.php';

// already logged in, no need to register again
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '') {
        $errors['name'] = 'Name is required.';
    }
    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email format is invalid.';
    }
    if (trim($password) === '') {
        $errors['password'] = 'Password is required.';
    }

    // check the email isn't taken yet
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'That email is already registered.';
        }
    }

    // save the user with a hashed password, never the plain one
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <script src="validate.js"></script>
</head>
<body>
<h1>Register</h1>
<form method="post" action="register.php" novalidate data-validate>
    <p>
        <label>Name<br>
            <input type="text" name="name" value="<?= e($name) ?>" data-rules="required">
        </label>
        <?php if (isset($errors['name'])): ?><br><?= e($errors['name']) ?><?php endif; ?>
    </p>
    <p>
        <label>Email<br>
            <input type="email" name="email" value="<?= e($email) ?>" data-rules="required,email">
        </label>
        <?php if (isset($errors['email'])): ?><br><?= e($errors['email']) ?><?php endif; ?>
    </p>
    <p>
        <label>Password<br>
            <input type="password" name="password" data-rules="required">
        </label>
        <?php if (isset($errors['password'])): ?><br><?= e($errors['password']) ?><?php endif; ?>
    </p>
    <button type="submit">Register</button>
    <a href="login.php">Already have an account?</a>
</form>
</body>
</html>
