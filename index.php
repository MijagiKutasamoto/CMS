<?php
if (!file_exists('config/config.php')) {
    header('Location: install.php');
    exit;
}

require_once 'config/config.php';

// Pobranie ustawień z bazy danych
$stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Pobranie slugu z URL-a
$slug = $_GET['slug'] ?? 'home';

// Pobranie danych strony
$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = :slug");
$stmt->execute(['slug' => $slug]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if ($page) {
    $pageTitle = $page['title'];
    $layout = $page['layout'] ?? 'default';
    $pluginSlug = $page['plugin_slug'] ?? null;

    ob_start();

    if ($pluginSlug) {
        $pluginPath = __DIR__ . "/plugins/{$pluginSlug}/plugin.php";
        if (file_exists($pluginPath)) {
            include $pluginPath;
        } else {
            echo "<p>Błąd: Plugin <b>{$pluginSlug}</b> nie istnieje.</p>";
        }
    } else {
        echo "<h1>" . htmlspecialchars($page['title']) . "</h1>";
        echo "<div>" . htmlspecialchars_decode($page['content']) . "</div>";
    }

    $content = ob_get_clean();
} else {
    // Strona główna (blog)
    $pageTitle = 'Blog';
    ob_start();

    $stmt = $pdo->query("SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT 10");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Ostatnie posty</h1>";
    foreach ($posts as $post) {
        echo "<div class='blog-post'>";
        echo "<h2>" . htmlspecialchars($post['title']) . "</h2>";
        echo "<p>" . substr(strip_tags($post['content']), 0, 200) . "...</p>";
        echo "<a href='blog_post.php?id={$post['id']}' class='btn'>Czytaj więcej</a>";
        echo "</div>";
    }

    $content = ob_get_clean();
}

// Załadowanie odpowiedniego szablonu
include "templates/main_template.php";
?>
