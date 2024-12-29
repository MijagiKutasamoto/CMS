<?php
session_start();
require_once '../config/config.php'; // <-- zakładam, że tu masz połączenie $pdo

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error   = '';
$success = '';

// Ścieżki
$pluginsDir = '../plugins/';
$adminDir   = '../admin/';

// Katalog do zapisu ZIP (jeśli chcemy zachować)
$uploadDir = __DIR__ . '/zips/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

/**
 * Funkcja rekurencyjnie usuwa folder i pliki (do obsługi "Usuń plugin")
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
 * Pobieranie pliku ZIP z repo (lub innego URL) przez cURL
 */
function fetchZipWithCurl($url, $destination, &$error)
{
    $ch = curl_init($url);
    if (!$ch) {
        $error = "Nie udało się zainicjować cURL.";
        return false;
    }
    $fp = @fopen($destination, 'wb');
    if (!$fp) {
        $error = "Nie można zapisać pliku: {$destination}";
        curl_close($ch);
        return false;
    }
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    // ewentualnie curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $success = curl_exec($ch);
    if ($success === false) {
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
 * Rozpakowywanie ZIP do katalogu ../plugins
 */
function extractPluginZip($zipFilePath, $pluginsDir, &$error)
{
    $zip = new ZipArchive();
    if ($zip->open($zipFilePath) === true) {
        $destinationFolder = pathinfo($zipFilePath, PATHINFO_FILENAME);
        $extractPath = rtrim($pluginsDir, '/') . '/' . $destinationFolder;
        if (!@mkdir($extractPath) && !is_dir($extractPath)) {
            // folder może już istnieć
        }
        if (!$zip->extractTo($extractPath)) {
            $error = 'Nie udało się wypakować pliku ZIP.';
            $zip->close();
            return false;
        }
        $zip->close();

        return $destinationFolder; // np. "facebook-integration"
    } else {
        $error = 'Nie udało się otworzyć pliku ZIP.';
        return false;
    }
}

/**
 * Instalacja/aktualizacja pluginu (na podstawie install.php)
 */
function installOrUpdatePlugin($pluginFolder, $pluginsDir, $adminDir, $pdo, &$error, &$success)
{
    $installPath = $pluginsDir . $pluginFolder . '/install.php';
    if (!file_exists($installPath)) {
        $error = "Brak pliku install.php w pluginie: {$pluginFolder}.";
        return;
    }

    $pluginConfig = include $installPath;
    $slug = $pluginConfig['slug'] ?? $pluginFolder;

    // Sprawdzamy, czy jest funkcja "install"
    if (!is_callable($pluginConfig['install'] ?? null)) {
        $error = "Funkcja install() nie została znaleziona w pluginie: {$pluginFolder}.";
        return;
    }

    // Czy plugin jest w bazie?
    $stmt = $pdo->prepare("SELECT version FROM plugins WHERE slug = :slug");
    $stmt->execute(['slug' => $slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Aktualizacja
        if (version_compare($row['version'], $pluginConfig['version'], '<')) {
            // mamy w bazie starszą wersję => aktualizujemy
            $pluginConfig['install']($pluginsDir . $pluginFolder, $adminDir, $pdo);

            $stmt2 = $pdo->prepare("UPDATE plugins SET version = :version WHERE slug = :slug");
            $stmt2->execute([
                'version' => $pluginConfig['version'],
                'slug'    => $slug
            ]);

            $success = "Zaktualizowano plugin: {$pluginConfig['name']} do wersji {$pluginConfig['version']}.";
        } else {
            $success = "Plugin {$pluginConfig['name']} (slug: {$slug}) jest już w najnowszej wersji ({$row['version']}).";
        }
    } else {
        // Instalacja nowego pluginu
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

        $success = "Zainstalowano nowy plugin: {$pluginConfig['name']} (slug: {$slug}, wersja: {$pluginConfig['version']}).";
    }
}

/**
 * Pobiera listę pluginów z repo (JSON) – zakładamy, że: repo_api.php?action=list
 */
function fetchRepoPluginList($repoUrl, &$error)
{
    // Użyjmy cURL do pobrania JSON
    $ch = curl_init($repoUrl);
    if (!$ch) {
        $error = "Nie udało się zainicjować cURL dla repo: {$repoUrl}";
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
        $error = "Niepoprawny JSON z repo: {$repoUrl}";
        return [];
    }
    return $data; // tablica pluginów [ { slug, name, version, zip_url, ...}, ... ]
}

// -------------------------------------------------------
// Obsługa formularzy (POST)
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) Instalacja z folderu (../plugins/<folder>)
    if (isset($_POST['plugin_folder'])) {
        $pluginFolder = $_POST['plugin_folder'];
        installOrUpdatePlugin($pluginFolder, $pluginsDir, $adminDir, $pdo, $error, $success);
    }

    // 2) Instalacja/aktualizacja z pliku ZIP (upload)
    if (isset($_POST['install_zip'])) {
        if (!empty($_FILES['plugin_zip']) && $_FILES['plugin_zip']['error'] === UPLOAD_ERR_OK) {
            $zipTmp  = $_FILES['plugin_zip']['tmp_name'];
            $zipName = $_FILES['plugin_zip']['name'];

            $destZip = $uploadDir . uniqid('plugin_', true) . '_' . $zipName;
            if (move_uploaded_file($zipTmp, $destZip)) {
                $extractedFolder = extractPluginZip($destZip, $pluginsDir, $error);
                if ($extractedFolder !== false) {
                    installOrUpdatePlugin($extractedFolder, $pluginsDir, $adminDir, $pdo, $error, $success);
                }
            } else {
                $error = "Nie udało się zapisać pliku ZIP na serwerze.";
            }
        } else {
            $error = "Nie wybrano pliku ZIP lub wystąpił błąd przy uploadzie.";
        }
    }

    // 3) Instalacja z repo (klik "Zainstaluj" / "Zaktualizuj" w liście repo)
    if (isset($_POST['install_from_repo']) && !empty($_POST['repo_zip_url'])) {
        $zipUrl   = $_POST['repo_zip_url'];
        $destName = 'plugin_' . uniqid() . '.zip';
        $destPath = $uploadDir . $destName;

        if (fetchZipWithCurl($zipUrl, $destPath, $error)) {
            $exFolder = extractPluginZip($destPath, $pluginsDir, $error);
            if ($exFolder !== false) {
                installOrUpdatePlugin($exFolder, $pluginsDir, $adminDir, $pdo, $error, $success);
            }
        }
    }

    // 4) Sprawdzenie aktualizacji (lokalnych + repo)
    if (isset($_POST['check_updates'])) {
        // A) Lokal – sprawdzamy ../plugins/<folder>/install.php vs. wersja w bazie
        $allDirs = array_diff(scandir($pluginsDir), ['.', '..']);
        $localUpdates = [];
        foreach ($allDirs as $dir) {
            $instPath = $pluginsDir . $dir . '/install.php';
            if (!is_dir($pluginsDir . $dir) || !file_exists($instPath)) {
                continue;
            }
            $localConfig = include $instPath;
            $slug   = $localConfig['slug'] ?? $dir;
            $vers   = $localConfig['version'] ?? '';
            if (!$vers) {
                continue; // brak wersji w install.php?
            }

            // Szukamy w bazie
            $stmt = $pdo->prepare("SELECT version FROM plugins WHERE slug = :slug");
            $stmt->execute(['slug' => $slug]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // Porównaj
                if (version_compare($row['version'], $vers, '<')) {
                    $localUpdates[] = [
                        'slug'    => $slug,
                        'name'    => $localConfig['name'] ?? $slug,
                        'current' => $row['version'],
                        'latest'  => $vers,
                        'type'    => 'local'
                    ];
                }
            }
        }

        // B) Repo – zakładamy, że mamy jeden URL (lub tablicę, jeśli wiele repo)
        $repoUrl = 'http://localhost/repo/repo_api.php?action=list';
        $remotePlugins = fetchRepoPluginList($repoUrl, $error);
        $remoteUpdates = [];
        if (!empty($remotePlugins)) {
            foreach ($remotePlugins as $rp) {
                // Szukamy w bazie
                $slugRp  = $rp['slug'];
                $versRp  = $rp['version'];
                $stmt = $pdo->prepare("SELECT version FROM plugins WHERE slug = :slug");
                $stmt->execute(['slug' => $slugRp]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    // Mamy w bazie, porównaj
                    if (version_compare($row['version'], $versRp, '<')) {
                        $remoteUpdates[] = [
                            'slug'     => $slugRp,
                            'name'     => $rp['name'],
                            'current'  => $row['version'],
                            'latest'   => $versRp,
                            'zip_url'  => $rp['zip_url'] ?? '',
                            'type'     => 'remote'
                        ];
                    }
                }
            }
        }

        // Sklejamy w jedną listę
        $updates = array_merge($localUpdates, $remoteUpdates);
        $_SESSION['plugin_updates'] = $updates;

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // 5) Usunięcie pluginu
    if (isset($_POST['remove_plugin']) && !empty($_POST['plugin_slug'])) {
        $slugToRemove = $_POST['plugin_slug'];

        // Usuwamy z bazy
        $stmt = $pdo->prepare("DELETE FROM plugins WHERE slug = :slug");
        $stmt->execute(['slug' => $slugToRemove]);

        // Usuwamy folder
        rrmdir($pluginsDir . $slugToRemove);

        $success = "Usunięto plugin: {$slugToRemove}.";
    }
}

// Odczyt ewentualnych aktualizacji
$pluginUpdates = $_SESSION['plugin_updates'] ?? [];
unset($_SESSION['plugin_updates']);

// Lista folderów w ../plugins nieobecnych w bazie => do zainstalowania
$installedSlugs = $pdo->query("SELECT slug FROM plugins")->fetchAll(PDO::FETCH_COLUMN);
$allFolders     = array_diff(scandir($pluginsDir), ['.', '..']);
$newPlugins     = [];
foreach ($allFolders as $folder) {
    if (is_dir($pluginsDir . $folder) && !in_array($folder, $installedSlugs)) {
        if (file_exists($pluginsDir . $folder . '/install.php')) {
            $newPlugins[] = $folder;
        }
    }
}

$adminPageTitle = 'Instalacja / Aktualizacja Pluginów';
ob_start();
?>

<h1>Instalacja / Aktualizacja Pluginów</h1>

<?php if ($error): ?>
    <p style="color:red;font-weight:bold;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<?php if ($success): ?>
    <p style="color:green;font-weight:bold;"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>

<form method="POST">
    <button type="submit" name="check_updates">Sprawdź aktualizacje (lokalne + repo)</button>
</form>

<!-- Jeżeli są aktualizacje do pokazania -->
<?php if (!empty($pluginUpdates)): ?>
    <h2>Dostępne aktualizacje:</h2>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Plugin</th>
            <th>Obecna wersja</th>
            <th>Nowsza wersja</th>
            <th>Źródło</th>
            <th>Akcja</th>
        </tr>
        <?php foreach ($pluginUpdates as $upd): ?>
        <tr>
            <td><?php echo htmlspecialchars($upd['name']); ?> (<?php echo htmlspecialchars($upd['slug']); ?>)</td>
            <td><?php echo htmlspecialchars($upd['current']); ?></td>
            <td><?php echo htmlspecialchars($upd['latest']); ?></td>
            <td><?php echo ($upd['type'] === 'remote') ? 'Repo' : 'Lokalnie'; ?></td>
            <td>
                <?php if ($upd['type'] === 'remote' && !empty($upd['zip_url'])): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="repo_zip_url" value="<?php echo htmlspecialchars($upd['zip_url']); ?>">
                        <button type="submit" name="install_from_repo">Zaktualizuj</button>
                    </form>
                <?php else: ?>
                    <!-- Aktualizacja lokalna -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="plugin_folder" value="<?php echo htmlspecialchars($upd['slug']); ?>">
                        <button type="submit">Zaktualizuj</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<hr>

<!-- Lista pluginów do instalacji z folderu -->
<h2>Instalacja z folderu ../plugins/</h2>
<?php if (!empty($newPlugins)): ?>
    <form method="POST">
        <select name="plugin_folder">
            <?php foreach ($newPlugins as $folderName): ?>
            <option value="<?php echo htmlspecialchars($folderName); ?>">
                <?php echo htmlspecialchars($folderName); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Zainstaluj</button>
    </form>
<?php else: ?>
    <p>Brak nowych pluginów w katalogu.</p>
<?php endif; ?>

<hr>

<!-- Instalacja z pliku ZIP -->
<h2>Instalacja/aktualizacja z pliku ZIP</h2>
<form method="POST" enctype="multipart/form-data">
    <label>Wybierz plik ZIP:</label>
    <input type="file" name="plugin_zip" accept=".zip">
    <button type="submit" name="install_zip">Wyślij i zainstaluj</button>
</form>

<hr>

<!-- Lista pluginów już zainstalowanych (z bazy) z opcją USUNIĘCIA -->
<h2>Zainstalowane pluginy</h2>
<?php
$stmt = $pdo->query("SELECT slug, name, version FROM plugins ORDER BY name ASC");
$installedPlugins = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($installedPlugins)): ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Plugin (slug)</th>
            <th>Wersja</th>
            <th>Akcja</th>
        </tr>
        <?php foreach ($installedPlugins as $pl): ?>
        <tr>
            <td><?php echo htmlspecialchars($pl['name']); ?> (<?php echo htmlspecialchars($pl['slug']); ?>)</td>
            <td><?php echo htmlspecialchars($pl['version']); ?></td>
            <td>
                <!-- Przykładowo: "Usuń" plugin -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="plugin_slug" value="<?php echo htmlspecialchars($pl['slug']); ?>">
                    <button type="submit" name="remove_plugin" onclick="return confirm('Na pewno usunąć plugin <?php echo $pl['slug']; ?>?');">
                        Usuń
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Brak zainstalowanych pluginów.</p>
<?php endif; ?>

<?php
$adminContent = ob_get_clean();
include '../templates/admin_template.php';
