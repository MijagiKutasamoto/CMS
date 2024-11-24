<?php
// Sprawdzenie autoryzacji
define('PLUGIN_ACCESS', true);

// Pobierz posty bloga
$stmt = $pdo->query("SELECT * FROM blog_posts ORDER BY created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="plugin-blog">
    <h2>Blog</h2>
    <div class="blog-container">
        <?php foreach ($posts as $post): ?>
            <div class="blog-post">
                <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                <p><?php echo substr(strip_tags($post['content']), 0, 100) . '...'; ?></p>
                <a href="/blog_post.php?id=<?php echo $post['id']; ?>" class="btn">Czytaj więcej</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<link rel="stylesheet" href="/plugins/blog/style.css">
<script src="/plugins/blog/script.js"></script>
