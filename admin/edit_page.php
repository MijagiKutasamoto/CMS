<?php
// admin/edit_page.php

session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Pobieranie dostępnych pluginów
$stmt = $pdo->query("SELECT slug, name FROM plugins WHERE status = 1");
$availablePlugins = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageId = $_GET['id'] ?? null;
if ($pageId) {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = :id");
    $stmt->execute(['id' => $pageId]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$page) {
        die("Strona nie została znaleziona.");
    }
} else {
    die("Nieprawidłowe ID strony.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $slug = $_POST['slug'] ?? generateSlug($title);
    $content = $_POST['content'] ?? '';
    $pluginSlug = $_POST['plugin_slug'] ?? null;

    if ($title && $slug) {
        $stmt = $pdo->prepare("UPDATE pages SET title = :title, slug = :slug, content = :content, plugin_slug = :plugin_slug WHERE id = :id");
        $stmt->execute([
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'plugin_slug' => $pluginSlug,
            'id' => $pageId,
        ]);
        header('Location: manage_pages.php');
        exit;
    } else {
        $error = 'Wszystkie pola są wymagane.';
    }
}

$adminPageTitle = 'Edytuj Stronę';
ob_start();
?>

<h1>Edytuj Stronę</h1>
<?php if (isset($error)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<form method="POST" action="">
    <label for="title">Tytuł:</label>
    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($page['title']); ?>" required>

    <label for="slug">Slug (URL):</label>
    <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($page['slug']); ?>" required>

    <label for="content">Treść:</label>
    <textarea id="content" name="content" required><?php echo htmlspecialchars($page['content']); ?></textarea>

    <label for="plugin_slug">Wybierz plugin (opcjonalne):</label>
    <select id="plugin_slug" name="plugin_slug">
        <option value="">Brak</option>
        <?php foreach ($availablePlugins as $plugin): ?>
            <option value="<?php echo htmlspecialchars($plugin['slug']); ?>" <?php echo $page['plugin_slug'] === $plugin['slug'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($plugin['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Zapisz zmiany</button>
</form>

<?php
$adminContent = ob_get_clean();
include '../templates/admin_template.php';
?>
