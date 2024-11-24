<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Pobieranie ID posta z URL-a
$postId = $_GET['id'] ?? null;

if ($postId) {
    // Sprawdzenie, czy post istnieje
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = :id");
    $stmt->execute(['id' => $postId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        // Usuwanie posta z bazy danych
        $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = :id");
        $stmt->execute(['id' => $postId]);

        // Przekierowanie po usunięciu
        header('Location: manage_blog.php?success=Post usunięty.');
        exit;
    } else {
        // Jeśli post nie istnieje
        header('Location: manage_blog.php?error=Post nie istnieje.');
        exit;
    }
} else {
    // Jeśli ID nie zostało podane
    header('Location: manage_blog.php?error=Nieprawidłowe ID posta.');
    exit;
}
?>
