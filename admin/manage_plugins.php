<?php
// admin/manage_plugins.php

session_start();
require_once '../config/config.php'; // zakładam, że tu masz PDO w $pdo

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error   = '';
$success = '';

// Ścieżki:
$pluginsDir = __DIR__ . '/../plugins/';
$adminDir   = __DIR__ . '/../admin/';
// Katalog do zapisu plików .zip
$uploadDir  = __DIR__ . '/zips/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

// ----------------------------------------------------------------------------
// 1. Pomocnicze funkcje
// ----------------------------------------------------------------------------

/**
 * rekurencyjnie usuwa folder i pliki (do obsługi "Usuń" plugin)
 */
function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object !== '.' && $object !== '..') {
                $path = $dir . '/' . $object;
                if (is_dir($path)) {
                    rrmdir($path);
                } else {
                    @unlink($path);
                }
            }
        }
        @rmdir($dir);
    }
}

/**
 * Pobieranie pliku .zip z repo (lub innego URL) przez cURL
 */
function fetchZipWithCurl($url, $destination, &$error)
{
    $ch = curl_init($url);
    if (!$ch) {
        $error = "Nie udało się zainicjować cURL dla: $url";
        return false;
    }
    $fp = @fopen($destination, 'wb');
    if (!$fp) {
        $error = "Nie można zapisać pliku ZIP do: $destination";
        curl_close($ch);
        return false;
    }
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    // ewentualnie: curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    if ($result === false) {
        $error = "cURL error: " . curl_error($ch);
        fclose($fp);
        curl_close($ch);
        return false;
    }

    fclose($fp);
    curl_close($ch);
    return true;
}

/**
 * Rozpakowywanie pliku ZIP do katalogu ../plugins/
 */
function extractPluginZip($zipFilePath, $pluginsDir, &$error)
{
    $zip = new ZipArchive();
    if ($zip->open($zipFilePath) === true) {
        $folderName = pathinfo($zipFilePath, PATHINFO_FILENAME);
        $extractPath = rtrim($pluginsDir, '/') . '/' . $folderName;
        if (!@mkdir($extractPath) && !is_dir($extractPath)) {
            // folder może istnieć
        }
        if (!$zip->extractTo($extractPath)) {
            $error = 'Nie udało się wypakować ZIP.';
            $zip->close();
            return false;
        }
        $zip->close();
        return basename($extractPath);
    } else {
        $error = 'Nie udało się otworzyć pliku ZIP.';
        return false;
    }
}

/**
 * Instalacja/aktualizacja pluginu (na bazie pliku install.php)
 */
function installOrUpdatePlugin($pluginFolder, $pluginsDir, $adminDir, $pdo, &$error, &$success)
{
    $installPath = $pluginsDir . $pluginFolder . '/install.php';
    if (!file_exists($installPath)) {
        $error = "Brak install.php w pluginie: $pluginFolder.";
        return;
    }
    $pluginConfig = include $installPath;
    $slug = $pluginConfig['slug'] ?? $pluginFolder;

    if (!is_callable($pluginConfig['install'] ?? null)) {
        $error = "Funkcja 'install' nie znaleziona w pluginie: $pluginFolder.";
        return;
    }

    // Szukamy pluginu w bazie
    $stmt = $pdo->prepare("SELECT id, version FROM plugins WHERE slug = :slug");
    $stmt->execute(['slug' => $slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Aktualizacja
        if (version_compare($row['version'], $pluginConfig['version'], '<')) {
            $pluginConfig['install']($pluginsDir . $pluginFolder, $adminDir, $pdo);

            $stmt2 = $pdo->prepare("UPDATE plugins SET version = :version WHERE id = :id");
            $stmt2->execute([
                'version' => $pluginConfig['version'],
                'id'      => $row['id']
            ]);
            $success = "Zaktualizowano: {$pluginConfig['name']} do wersji {$pluginConfig['version']}.";
        } else {
            $success = "Plugin {$pluginConfig['name']} (slug: $slug) jest już w wersji {$row['version']} lub nowszej.";
        }
    } else {
        // Instalacja nowego
        $pluginConfig['install']($pluginsDir . $pluginFolder, $adminDir, $pdo);

        $stmt2 = $pdo->prepare("
            INSERT INTO plugins (name, slug, status, menu_title, menu_url, version)
            VALUES (:name, :slug, 0, :menu_title, :menu_url, :version)
        ");
        $stmt2->execute([
            'name'       => $pluginConfig['name'],
            'slug'       => $slug,
            'menu_title' => $pluginConfig['menu_title'] ?? null,
            'menu_url'   => $pluginConfig['menu_url']   ?? null,
            'version'    => $pluginConfig['version']
        ]);

        $success = "Zainstalowano nowy plugin: {$pluginConfig['name']} (slug: $slug, wersja: {$pluginConfig['version']}).";
    }
}

/**
 * Pobiera listę pluginów z repo (zakładamy: repo_api.php?action=list)
 */
function fetchRepoPluginList($repoUrl, &$error)
{
    $ch = curl_init($repoUrl);
    if (!$ch) {
        $error = "Nie udało się zainicjować cURL dla: $repoUrl";
        return [];
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $json = curl_exec($ch);
    if ($json === false) {
        $error = "Błąd cURL: " . curl_error($ch);
        curl_close($ch);
        return [];
    }
    curl_close($ch);

    $data = json_decode($json, true);
    if (!is_array($data)) {
        $error = "Niepoprawny JSON z repo: $repoUrl";
        return [];
    }
    return $data; // [ { slug, name, version, zip_url, ...}, ... ]
}

// ----------------------------------------------------------------------------
// 2. Obsługa formularzy
// ----------------------------------------------------------------------------

// 2.1. Aktywacja/Dezaktywacja, usuwanie pluginu (z bazy, z folderu)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pluginId = $_POST['plugin_id'] ?? null;
    $action   = $_POST['action']    ?? '';

    if ($pluginId && ($action === 'activate' || $action === 'deactivate')) {
        $status = ($action === 'activate') ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE plugins SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $pluginId]);
        $success = ($status ? 'Aktywowano' : 'Dezaktywowano') . " plugin ID: $pluginId.";
    }

    if ($pluginId && $action === 'delete') {
        // Pobierz szczegóły pluginu (slug)
        $stmt = $pdo->prepare("SELECT slug FROM plugins WHERE id = :id");
        $stmt->execute(['id' => $pluginId]);
        $plugin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($plugin) {
            $pluginSlug = $plugin['slug'];
            $pluginPath = $pluginsDir . $pluginSlug;

            // Usuń folder pluginu
            if (is_dir($pluginPath)) {
                rrmdir($pluginPath);
            }

            // Usuń wpis z bazy
            $stmt2 = $pdo->prepare("DELETE FROM plugins WHERE id = :id");
            $stmt2->execute(['id' => $pluginId]);
            $success = "Usunięto plugin ID: $pluginId (slug: $pluginSlug).";
        }
    }
}

// 2.2. Instalacja/aktualizacja z pliku ZIP (upload)
if (isset($_POST['install_zip'])) {
    if (!empty($_FILES['plugin_zip']) && $_FILES['plugin_zip']['error'] === UPLOAD_ERR_OK) {
        $zipTmpPath  = $_FILES['plugin_zip']['tmp_name'];
        $zipFileName = $_FILES['plugin_zip']['name'];

        $destPath = $uploadDir . uniqid('plugin_', true) . '_' . $zipFileName;
        if (move_uploaded_file($zipTmpPath, $destPath)) {
            $folderExtracted = extractPluginZip($destPath, $pluginsDir, $error);
            if ($folderExtracted !== false) {
                installOrUpdatePlugin($folderExtracted, $pluginsDir, $adminDir, $pdo, $error, $success);
            }
        } else {
            $error = 'Nie udało się przenieść pliku ZIP na serwer.';
        }
    } else {
        $error = 'Nie wybrano pliku ZIP lub wystąpił błąd przy wgrywaniu.';
    }
}

// 2.3. Instalacja/aktualizacja z repo (przekazany zip_url)
if (isset($_POST['install_from_repo']) && !empty($_POST['repo_zip_url'])) {
    $zipUrl   = $_POST['repo_zip_url'];
    $zipName  = 'plugin_' . uniqid() . '.zip';
    $destPath = $uploadDir . $zipName;

    if (fetchZipWithCurl($zipUrl, $destPath, $error)) {
        $extracted = extractPluginZip($destPath, $pluginsDir, $error);
        if ($extracted !== false) {
            installOrUpdatePlugin($extracted, $pluginsDir, $adminDir, $pdo, $error, $success);
        }
    }
}

// 2.4. Wyszukiwanie w repo
if (isset($_POST['search_repo']) && !empty($_POST['repo_search'])) {
    $searchQuery = trim($_POST['repo_search']);
    // Zakładamy, że mamy endpoint: repo_api.php?action=search&query=...
    $repoUrl = 'http://localhost/repo/repo_api.php?action=search&query=' . urlencode($searchQuery);
    $searchResults = fetchRepoPluginList($repoUrl, $error);
}

// 2.5. Wyświetlanie losowych 6 pluginów z repo
if (isset($_POST['random_repo'])) {
    // Zakładam, że mamy endpoint z listą: ?action=list
    // W realnej implementacji możesz mieć kilka repo, łącząc je w jedną tablicę
    $repoUrl = 'http://localhost/repo/repo_api.php?action=list';
    $repoList = fetchRepoPluginList($repoUrl, $error);

    // Losujemy 6, jeśli jest tyle
    if (!empty($repoList)) {
        shuffle($repoList);
        $randomPlugins = array_slice($repoList, 0, 6);
        $_SESSION['random_plugins'] = $randomPlugins;
        header('Location: manage_plugins.php');
        exit;
    }
}

// Wczytujemy z sesji ewentualne wylosowane pluginy
$randomPlugins = $_SESSION['random_plugins'] ?? [];
unset($_SESSION['random_plugins']);

// ----------------------------------------------------------------------------
// 3. Pobieranie listy zainstalowanych pluginów (do zarządzania: aktywacja, usuwanie)
// ----------------------------------------------------------------------------
$stmt = $pdo->query("SELECT * FROM plugins");
$plugins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------------------------------------------------------------
// 4. Render HTML
// ----------------------------------------------------------------------------
$adminPageTitle = 'Zarządzanie Pluginami + Instalacja/Repo';
ob_start();
?>
<h1>Zarządzanie Pluginami</h1>

<!-- Sekcja: instalacja z pliku .zip -->
<form method="POST" enctype="multipart/form-data">
    <label>Wgraj plik ZIP (instalacja / aktualizacja):</label>
    <input type="file" name="plugin_zip" accept=".zip">
    <button type="submit" name="install_zip">Zainstaluj / Zaktualizuj</button>
</form>

<!-- Przyciski do sprawdzania repo (wyszukiwanie, losowe pluginy) -->
<form method="POST" style="margin-top: 10px;">
    <label>Wyszukaj w repo:</label>
    <input type="text" name="repo_search" placeholder="np. facebook" style="width:200px;">
    <button type="submit" name="search_repo">Szukaj</button>
    <button type="submit" name="random_repo">Losowe 6 pluginów</button>
</form>

<?php if ($error): ?>
    <p style="color:red;font-weight:bold;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<?php if ($success): ?>
    <p style="color:green;font-weight:bold;"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>

<!-- Sekcja: wyniki wyszukiwania w repo -->
<?php if (!empty($searchResults)): ?>
    <h2>Wyniki wyszukiwania w repozytorium</h2>
    <table border="1" cellpadding="5">
        <tr>
            <th>Slug</th>
            <th>Nazwa</th>
            <th>Wersja (repo)</th>
            <th>Akcja</th>
        </tr>
        <?php foreach ($searchResults as $repoPlugin): ?>
            <?php
            // Sprawdź, czy zainstalowany
            $stmtCheck = $pdo->prepare("SELECT version FROM plugins WHERE slug = :slug");
            $stmtCheck->execute(['slug' => $repoPlugin['slug']]);
            $local = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            $actionLabel = 'Zainstaluj';
            $actionName  = 'install_from_repo';

            if ($local) {
                // porównaj wersje
                if (version_compare($local['version'], $repoPlugin['version'], '<')) {
                    $actionLabel = 'Zaktualizuj';
                    $actionName  = 'install_from_repo';
                } else {
                    $actionLabel = 'Usuń'; // ewentualnie
                    $actionName  = 'remove_plugin';
                }
            }
            ?>
            <tr>
                <td><?php echo htmlspecialchars($repoPlugin['slug']); ?></td>
                <td><?php echo htmlspecialchars($repoPlugin['name']); ?></td>
                <td><?php echo htmlspecialchars($repoPlugin['version']); ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <?php if ($actionName === 'install_from_repo'): ?>
                            <input type="hidden" name="repo_zip_url" value="<?php echo htmlspecialchars($repoPlugin['zip_url'] ?? ''); ?>">
                            <button type="submit" name="<?php echo htmlspecialchars($actionName); ?>">
                                <?php echo htmlspecialchars($actionLabel); ?>
                            </button>
                        <?php else: ?>
                            <!-- Usuwanie -->
                            <input type="hidden" name="plugin_slug" value="<?php echo htmlspecialchars($repoPlugin['slug']); ?>">
                            <button type="submit" name="remove_plugin" onclick="return confirm('Na pewno usunąć plugin <?php echo htmlspecialchars($repoPlugin['slug']); ?>?');">
                                Usuń
                            </button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<!-- Sekcja: losowe pluginy (jeśli wylosowano) -->
<?php if (!empty($randomPlugins)): ?>
    <h2>Losowe Pluginy (z repo)</h2>
    <table border="1" cellpadding="5">
        <tr>
            <th>Slug</th>
            <th>Nazwa</th>
            <th>Wersja</th>
            <th>Akcja</th>
        </tr>
        <?php foreach ($randomPlugins as $rp): ?>
            <?php
            $stmtCheck = $pdo->prepare("SELECT version FROM plugins WHERE slug = :slug");
            $stmtCheck->execute(['slug' => $rp['slug']]);
            $local = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            $actionLabel = 'Zainstaluj';
            $actionName  = 'install_from_repo';

            if ($local) {
                if (version_compare($local['version'], $rp['version'], '<')) {
                    $actionLabel = 'Zaktualizuj';
                    $actionName  = 'install_from_repo';
                } else {
                    $actionLabel = 'Usuń';
                    $actionName  = 'remove_plugin';
                }
            }
            ?>
            <tr>
                <td><?php echo htmlspecialchars($rp['slug']); ?></td>
                <td><?php echo htmlspecialchars($rp['name']); ?></td>
                <td><?php echo htmlspecialchars($rp['version']); ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <?php if ($actionName === 'install_from_repo'): ?>
                            <input type="hidden" name="repo_zip_url" value="<?php echo htmlspecialchars($rp['zip_url'] ?? ''); ?>">
                            <button type="submit" name="install_from_repo">
                                <?php echo htmlspecialchars($actionLabel); ?>
                            </button>
                        <?php else: ?>
                            <input type="hidden" name="plugin_slug" value="<?php echo htmlspecialchars($rp['slug']); ?>">
                            <button type="submit" name="remove_plugin" onclick="return confirm('Na pewno usunąć plugin <?php echo htmlspecialchars($rp['slug']); ?>?');">
                                Usuń
                            </button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<hr>
<!-- Wyświetlanie zainstalowanych pluginów (z bazy) -->
<h2>Zainstalowane Pluginy</h2>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th>Nazwa</th>
            <th>Slug</th>
            <th>Wersja</th>
            <th>Status</th>
            <th>Akcje</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Pobieranie pluginów z bazy
        $stmt = $pdo->query("SELECT * FROM plugins ORDER BY name ASC");
        $allPlugins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allPlugins as $pl):
        ?>
        <tr>
            <td><?php echo htmlspecialchars($pl['name']); ?></td>
            <td><?php echo htmlspecialchars($pl['slug']); ?></td>
            <td><?php echo htmlspecialchars($pl['version']); ?></td>
            <td><?php echo $pl['status'] ? 'Aktywny' : 'Nieaktywny'; ?></td>
            <td>
                <!-- Aktywacja / Dezaktywacja -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="plugin_id" value="<?php echo $pl['id']; ?>">
                    <?php if ($pl['status']): ?>
                        <button type="submit" name="action" value="deactivate">Dezaktywuj</button>
                    <?php else: ?>
                        <button type="submit" name="action" value="activate">Aktywuj</button>
                    <?php endif; ?>
                </form>

                <!-- Usuwanie -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Czy na pewno usunąć plugin <?php echo $pl['slug']; ?>?');">
                    <input type="hidden" name="plugin_id" value="<?php echo $pl['id']; ?>">
                    <button type="submit" name="action" value="delete">Usuń</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
$adminContent = ob_get_clean();
include '../templates/admin_template.php';
