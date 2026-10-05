<?php
require 'db.php';

// already logged in, no need to see this page
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email format is invalid.';
    }
    if (trim($password) === '') {
        $errors['password'] = 'Password is required.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id, name, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if ($row && password_verify($password, $row['password'])) {
            // fresh session id after login so an old one can't be reused
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $row['id'];
            $_SESSION['user_name'] = $row['name'];
            header('Location: index.php');
            exit;
        }
        // same message for wrong email or wrong password on purpose
        $errors['form'] = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <script src="validate.js"></script>
</head>
<body>
<h1>Login</h1>
<?php if (isset($errors['form'])): ?><p><?= e($errors['form']) ?></p><?php endif; ?>
<form method="post" action="login.php" novalidate data-validate>
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
    <button type="submit">Login</button>
    <a href="register.php">Create an account</a>
</form>
</body>
</html>
