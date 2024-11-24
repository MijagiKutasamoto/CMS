<?php
// admin/manage_plugins.php

session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Pobieranie pluginów z bazy danych
$stmt = $pdo->query("SELECT * FROM plugins");
$plugins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obsługa aktywacji/dezaktywacji i usuwania pluginów
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pluginId = $_POST['plugin_id'] ?? null;
    $action = $_POST['action'] ?? '';

    if ($pluginId && ($action === 'activate' || $action === 'deactivate')) {
        $status = $action === 'activate' ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE plugins SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $pluginId]);
        header('Location: manage_plugins.php');
        exit;
    }

    if ($pluginId && $action === 'delete') {
        // Pobierz szczegóły pluginu
        $stmt = $pdo->prepare("SELECT slug FROM plugins WHERE id = :id");
        $stmt->execute(['id' => $pluginId]);
        $plugin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($plugin) {
            $pluginSlug = $plugin['slug'];
            $pluginPath = __DIR__ . '/../plugins/' . $pluginSlug;

            // Usuń katalog pluginu, jeśli istnieje
            if (is_dir($pluginPath)) {
                function deleteDirectory($dir) {
                    $files = array_diff(scandir($dir), ['.', '..']);
                    foreach ($files as $file) {
                        $path = $dir . DIRECTORY_SEPARATOR . $file;
                        is_dir($path) ? deleteDirectory($path) : unlink($path);
                    }
                    return rmdir($dir);
                }
                deleteDirectory($pluginPath);
            }

            // Usuń wpis z bazy danych
            $stmt = $pdo->prepare("DELETE FROM plugins WHERE id = :id");
            $stmt->execute(['id' => $pluginId]);

            header('Location: manage_plugins.php');
            exit;
        }
    }
}

$adminPageTitle = 'Zarządzanie Pluginami';
ob_start();
?>

<h1>Zarządzanie Pluginami</h1>
<a href="install_plugin.php" class="btn">Dodaj Nowy Plugin</a>
<table>
    <thead>
        <tr>
            <th>Nazwa</th>
            <th>Slug</th>
            <th>Status</th>
            <th>Akcje</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($plugins as $plugin): ?>
            <tr>
                <td><?php echo htmlspecialchars($plugin['name']); ?></td>
                <td><?php echo htmlspecialchars($plugin['slug']); ?></td>
                <td><?php echo $plugin['status'] ? 'Aktywny' : 'Nieaktywny'; ?></td>
                <td>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="plugin_id" value="<?php echo $plugin['id']; ?>">
                        <?php if ($plugin['status']): ?>
                            <button type="submit" name="action" value="deactivate">Dezaktywuj</button>
                        <?php else: ?>
                            <button type="submit" name="action" value="activate">Aktywuj</button>
                        <?php endif; ?>
                    </form>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="plugin_id" value="<?php echo $plugin['id']; ?>">
                        <button type="submit" name="action" value="delete" onclick="return confirm('Czy na pewno chcesz usunąć ten plugin?')">Usuń</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
$adminContent = ob_get_clean();
include '../templates/admin_template.php';
?>
