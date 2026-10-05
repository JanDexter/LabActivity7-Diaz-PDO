<?php
session_start();

$host   = '127.0.0.1';
$port   = '3306';
$dbname = 'blog_site';
$user   = 'root';
$pass   = '';

$pdo = new PDO(
    "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
    $user,
    $pass,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

// escape anything we print so user input can't inject html
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// send visitors who aren't logged in over to the login page
function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// true when the given user id belongs to whoever is logged in
function is_current_user($userId): bool
{
    return isset($_SESSION['user_id']) && (int) $userId === (int) $_SESSION['user_id'];
}

function render_nav(): void
{
    echo '<nav><a href="index.php">Feed</a> <a href="create_post.php">Create Post</a> '
        . '<a href="logout.php">Logout (' . e($_SESSION['user_name'] ?? '') . ')</a></nav>';
}
