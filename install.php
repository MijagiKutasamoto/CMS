<?php
// install.php

// Ścieżka do pliku konfiguracyjnego
$configFilePath = __DIR__ . '/config/config.php';

// Sprawdzenie, czy plik konfiguracyjny już istnieje
if (file_exists($configFilePath)) {
    // Jeśli istnieje, pokaż animację i przyciski
    ?>
    <!DOCTYPE html>
    <html lang="pl">
    <head>
        <meta charset="UTF-8">
        <title>System zainstalowany</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100vh;
            }
            .loader {
                border: 16px solid #f3f3f3;
                border-top: 16px solid #3498db;
                border-radius: 50%;
                width: 120px;
                height: 120px;
                animation: spin 2s linear infinite;
                margin-bottom: 20px;
            }
            @keyframes spin {
                0%   { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            h1 {
                margin-bottom: 20px;
            }
            .buttons {
                display: flex;
                gap: 20px;
            }
            button {
                padding: 10px 20px;
                background-color: #3498db;
                color: #fff;
                border: none;
                border-radius: 5px;
                cursor: pointer;
            }
            button:hover {
                background-color: #2980b9;
            }
        </style>
    </head>
    <body>
        <div class="loader"></div>
        <h1>Hej! System jest już zainstalowany.</h1>
        <div class="buttons">
            <button onclick="window.location.href='index.php'">Przejdź do strony głównej</button>
            <button onclick="window.location.href='admin/dashboard.php'">Przejdź do panelu admina</button>
        </div>
    </body>
    </html>
    <?php
    exit;
}


// 2. Obsługa przesłanego formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host         = $_POST['db_host'];
    $db_name         = $_POST['db_name'];
    $db_user         = $_POST['db_user'];
    $db_pass         = $_POST['db_pass'];
    $admin_username  = $_POST['admin_username'];
    $admin_password  = $_POST['admin_password'];
    $site_name       = $_POST['site_name'];

    // (A) Łączenie z bazą
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Błąd połączenia z bazą danych: " . $e->getMessage());
    }

    // (B) Tworzenie tabel (jeśli nie istnieją)
    $queries = [
        // Tabela pages
        "CREATE TABLE IF NOT EXISTS `pages` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `slug` varchar(100) NOT NULL,
            `content` text NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            `plugin_slug` varchar(100) DEFAULT NULL,
            `layout` varchar(50) NOT NULL DEFAULT 'default',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        
        // Tabela plugins
        "CREATE TABLE IF NOT EXISTS `plugins` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `slug` varchar(255) NOT NULL,
            `name` varchar(255) NOT NULL,
            `status` tinyint(1) NOT NULL DEFAULT 0,
            `menu_title` varchar(255) DEFAULT NULL,
            `menu_url` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `version` text NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        
        // Tabela settings
        "CREATE TABLE IF NOT EXISTS `settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `logo` varchar(255) DEFAULT NULL,
            `slider_images` text DEFAULT NULL,
            `enable_slider` int(11) NOT NULL DEFAULT 0,
            `home_plugin_slug` varchar(100) DEFAULT NULL,
            `site_name` text NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        
        // Tabela users
        "CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    ];

    try {
        foreach ($queries as $query) {
            $pdo->exec($query);
        }
    } catch (PDOException $e) {
        die("Błąd podczas tworzenia tabel: " . $e->getMessage());
    }

    // (C) Dodanie strony głównej (o ile nie istnieje)
    try {
        $stmt = $pdo->prepare("SELECT id FROM pages WHERE slug = 'home' LIMIT 1");
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            $insertPage = $pdo->prepare("INSERT INTO pages (title, slug, content) 
                VALUES (:title, :slug, :content)");
            $insertPage->execute([
                ':title'   => 'Strona Główna',
                ':slug'    => 'home',
                ':content' => '<p><strong>Witamy na Twojej nowej stronie!</strong></p><p>Gratulacje! Udało Ci się zainstalować nasz system zarządzania treścią (CMS). Jesteśmy tutaj, aby pomóc Ci w stworzeniu pięknej i funkcjonalnej witryny internetowej. Poniżej znajdziesz kilka wskazówek, jak rozpocząć:</p><ol><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span><strong>Dodaj nowe treści</strong> – Twórz strony, posty i blogi w łatwy i intuicyjny sposób.</li><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span><strong>Personalizuj wygląd</strong> – Skorzystaj z panelu administracyjnego, aby dostosować wygląd strony do swoich potrzeb.</li><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span><strong>Instaluj wtyczki</strong> – Rozszerzaj funkcje swojej witryny za pomocą dostępnych wtyczek.</li><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span><strong>Zarządzaj ustawieniami</strong> – Skonfiguruj swoją stronę, aby działała dokładnie tak, jak tego oczekujesz.</li></ol><p>Twoja strona jest teraz gotowa do działania. Zacznij dodawać treści lub odkrywaj wszystkie funkcje, jakie oferuje nasz CMS!</p><p>Dziękujemy, że wybrałeś nasz system. Jeśli masz pytania lub potrzebujesz pomocy, odwiedź <a href="#" rel="noopener noreferrer" target="_blank">naszą stronę wsparcia</a> lub skontaktuj się z nami.</p><p>Powodzenia w budowaniu Twojej witryny!</p>'
            ]);
        }
    } catch (PDOException $e) {
        die("Błąd podczas dodawania strony: " . $e->getMessage());
    }

    // (D) Ustawienie nazwy strony w tabeli settings
    try {
        $stmt = $pdo->query("SELECT id FROM settings LIMIT 1");
        if ($stmt->rowCount() === 0) {
            $insertSettings = $pdo->prepare("INSERT INTO settings (site_name, enable_slider) VALUES (:site_name, 0)");
            $insertSettings->execute([':site_name' => $site_name]);
        } else {
            $updateSettings = $pdo->prepare("UPDATE settings SET site_name = :site_name");
            $updateSettings->execute([':site_name' => $site_name]);
        }
    } catch (PDOException $e) {
        die("Błąd podczas aktualizacji danych w settings: " . $e->getMessage());
    }

    // (E) Tworzenie konta administratora (jeśli nie istnieje)
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $admin_username]);

        if ($stmt->rowCount() === 0) {
            $hashedPassword = password_hash($admin_password, PASSWORD_BCRYPT);
            $insertAdmin = $pdo->prepare("INSERT INTO users (username, password) 
                                          VALUES (:username, :password)");
            $insertAdmin->execute([
                ':username' => $admin_username,
                ':password' => $hashedPassword
            ]);
        }
    } catch (PDOException $e) {
        die("Błąd podczas dodawania użytkownika administratora: " . $e->getMessage());
    }

    // (F) Generowanie pliku config.php
    $configContent = <<<PHP
<?php
// config.php

// Dane połączenia z bazą danych
\$host = '$db_host';
\$db_name = '$db_name';
\$username = '$db_user';
\$password = '$db_pass';

try {
    \$pdo = new PDO("mysql:host=\$host;dbname=\$db_name;charset=utf8mb4", \$username, \$password);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException \$e) {
    die("Błąd połączenia z bazą danych: " . \$e->getMessage());
}
PHP;

    // Upewnij się, że folder config istnieje
    if (!is_dir(__DIR__ . '/config')) {
        mkdir(__DIR__ . '/config', 0755, true);
    }

    // Zapis pliku
    file_put_contents($configFilePath, $configContent);

    ?>
    <!DOCTYPE html>
    <html lang="pl">
    <head>
        <meta charset="UTF-8">
        <title>Instalacja zakończona</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100vh;
            }
            .loader {
                border: 16px solid #f3f3f3;
                border-top: 16px solid #3498db;
                border-radius: 50%;
                width: 120px;
                height: 120px;
                animation: spin 1s linear infinite;
                margin-bottom: 20px;
            }
            @keyframes spin {
                0%   { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            h1 {
                margin-bottom: 20px;
            }
            .hidden {
                display: none;
            }
            .buttons {
                display: flex;
                gap: 20px;
            }
            button {
                padding: 10px 20px;
                background-color: #3498db;
                color: #fff;
                border: none;
                border-radius: 5px;
                cursor: pointer;
            }
            button:hover {
                background-color: #2980b9;
            }
        </style>
        <script>
            // Prosta funkcja do pokazania przycisków po krótkim "ładowaniu"
            document.addEventListener('DOMContentLoaded', function(){
                setTimeout(function(){
                    document.getElementById('loading-text').innerText = 'Instalacja zakończona pomyślnie!';
                    document.querySelector('.loader').style.display = 'none';
                    document.querySelector('.buttons').classList.remove('hidden');
                }, 3000); // 3 sekundy "animacji ładowania"
            });
        </script>
    </head>
    <body>
        <div class="loader"></div>
        <h1 id="loading-text">Trwa końcowa konfiguracja...</h1>
        <div class="buttons hidden">
            <button onclick="window.location.href='index.php'">Przejdź do strony głównej</button>
            <button onclick="window.location.href='admin/dashboard.php'">Przejdź do panelu administracyjnego</button>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Jeśli nie wysłano formularza (GET) – pokaż formularz
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Instalacja CMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input[type="text"], input[type="password"], button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            background-color: #28a745;
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Instalacja CMS</h1>
    <form method="POST">
        <label for="db_host">Host bazy danych:</label>
        <input type="text" id="db_host" name="db_host" required>

        <label for="db_name">Nazwa bazy danych:</label>
        <input type="text" id="db_name" name="db_name" required>

        <label for="db_user">Użytkownik bazy danych:</label>
        <input type="text" id="db_user" name="db_user" required>

        <label for="db_pass">Hasło bazy danych:</label>
        <input type="password" id="db_pass" name="db_pass">

        <label for="admin_username">Nazwa użytkownika administratora:</label>
        <input type="text" id="admin_username" name="admin_username" required>

        <label for="admin_password">Hasło administratora:</label>
        <input type="password" id="admin_password" name="admin_password" required>

        <label for="site_name">Nazwa strony:</label>
        <input type="text" id="site_name" name="site_name" required>

        <button type="submit">Zainstaluj</button>
    </form>
</div>
</body>
</html>
