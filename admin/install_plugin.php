<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Ścieżki do katalogów
$pluginsDir = '../plugins/';
$adminDir = '../admin/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['plugin_folder'])) {
        $pluginFolder = $_POST['plugin_folder'];
        $installPath = $pluginsDir . $pluginFolder . '/install.php';
        if (file_exists($installPath)) {
            $pluginConfig = include $installPath;

            // Sprawdzenie i wykonanie instalacji
            if (is_callable($pluginConfig['install'])) {
                $pluginConfig['install']($pluginsDir . $pluginFolder, $adminDir, $pdo);

                $stmt = $pdo->prepare("INSERT INTO plugins (name, slug, status, menu_title, menu_url) VALUES (:name, :slug, :status, :menu_title, :menu_url)");
                $stmt->execute([
                    'name' => $pluginConfig['name'],
                    'slug' => $pluginFolder,
                    'status' => 0,
                    'menu_title' => $pluginConfig['menu_title'] ?? null,
                    'menu_url' => $pluginConfig['menu_url'] ?? null,
                ]);

                $success = "Plugin {$pluginConfig['name']} został zainstalowany.";
            } else {
                $error = "Funkcja instalacji nie została znaleziona w pluginie {$pluginFolder}.";
            }
        } else {
            $error = "Plugin {$pluginFolder} nie zawiera pliku instalacyjnego.";
        }
    }
}

// Pobieranie dostępnych pluginów
$installedPlugins = $pdo->query("SELECT slug FROM plugins")->fetchAll(PDO::FETCH_COLUMN);
$availablePlugins = array_diff(scandir($pluginsDir), ['.', '..']);
$newPlugins = array_filter($availablePlugins, function ($plugin) use ($installedPlugins, $pluginsDir) {
    return is_dir($pluginsDir . $plugin) && !in_array($plugin, $installedPlugins);
});

$adminPageTitle = 'Instalacja Pluginów';
ob_start();
?>

<h1>Instalacja Pluginów</h1>
<?php if ($error): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<?php if ($success): ?>
    <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>

<h2>Nowe Pluginy</h2>
<?php if (!empty($newPlugins)): ?>
    <form method="POST">
        <label for="plugin_folder">Wybierz plugin:</label>
        <select id="plugin_folder" name="plugin_folder">
            <?php foreach ($newPlugins as $plugin): ?>
                <option value="<?php echo htmlspecialchars($plugin); ?>">
                    <?php echo htmlspecialchars($plugin); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Zainstaluj</button>
    </form>
<?php else: ?>
    <p>Brak nowych pluginów do zainstalowania.</p>
<?php endif; ?>

<?php
$adminContent = ob_get_clean();
include '../templates/admin_template.php';
?>
