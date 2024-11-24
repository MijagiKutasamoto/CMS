<?php
require_once 'config/config.php';

// Pobranie ustawień strony
$stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Pobranie ID posta z URL-a
$postId = $_GET['id'] ?? null;

if ($postId) {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = :id");
    $stmt->execute(['id' => $postId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        $pageTitle = $post['title'];
        ob_start();
        ?>
        <div class="blog-post">
            <h2><?php echo htmlspecialchars($post['title']); ?></h2>
            <div class="post-meta">
                <p>Opublikowano: <?php echo date('d-m-Y H:i', strtotime($post['created_at'])); ?></p>
            </div>
            <div class="post-content">
                <?php echo $post['content']; // Wyświetlenie treści posta z edytora Quill ?>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
    } else {
        $pageTitle = 'Post nie znaleziony';
        $content = '<h1>Błąd 404</h1><p>Post nie został znaleziony.</p>';
    }
} else {
    $pageTitle = 'Post nie znaleziony';
    $content = '<h1>Błąd 404</h1><p>Post nie został znaleziony.</p>';
}

include 'templates/main_template.php';
?>
