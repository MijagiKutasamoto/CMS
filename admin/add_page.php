<?php
// admin/add_page.php

session_start();
require_once '../config/config.php';
require_once '../includes/functions.php'; // Załączenie pliku z funkcjami

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Pobieranie dostępnych pluginów
$stmt = $pdo->query("SELECT slug, name FROM plugins WHERE status = 1");
$availablePlugins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pobieranie dostępnych stylów stron
$availableLayouts = ['default', 'fullwidth', 'boxed', 'grid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $slug = $_POST['slug'] ?? generateSlug($title); // Automatyczne generowanie slugu na podstawie tytułu
    $content = $_POST['content'] ?? '';
    $pluginSlug = $_POST['plugin_slug'] ?? null; // Plugin przypisany do strony
    $layout = $_POST['layout'] ?? 'default'; // Wybrany układ strony

    if ($title && $slug) {
        $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content, plugin_slug, layout) VALUES (:title, :slug, :content, :plugin_slug, :layout)");
        $stmt->execute([
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'plugin_slug' => $pluginSlug,
            'layout' => $layout,
        ]);
        header('Location: manage_pages.php');
        exit;
    } else {
        $error = 'Wszystkie pola są wymagane.';
    }
}

$adminPageTitle = 'Dodaj nową stronę';
ob_start();
?>

<h1>Dodaj Nową Stronę</h1>
<?php if (isset($error)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<form method="POST" action="">
    <label for="title">Tytuł:</label>
    <input type="text" id="title" name="title" required>

    <label for="slug">Slug (URL):</label>
    <input type="text" id="slug" name="slug" required>

    <label for="content">Treść strony:</label>
    <textarea id="content" name="content"></textarea>

    <label for="plugin_slug">Wybierz plugin (opcjonalne):</label>
    <select id="plugin_slug" name="plugin_slug">
        <option value="">Brak</option>
        <?php foreach ($availablePlugins as $plugin): ?>
            <option value="<?php echo htmlspecialchars($plugin['slug']); ?>">
                <?php echo htmlspecialchars($plugin['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="layout">Wybierz układ strony:</label>
    <select id="layout" name="layout">
        <?php foreach ($availableLayouts as $layout): ?>
            <option value="<?php echo htmlspecialchars($layout); ?>">
                <?php echo ucfirst($layout); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Dodaj Stronę</button>
</form>

<?php
$adminContent = ob_get_clean();
include '../templates/admin_template.php';
?>
