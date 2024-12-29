<?php
return [
    'name' => 'Blog',
    'menu_title' => 'Zarządzanie Blogiem',
    'menu_url' => 'manage_blog.php',
    'version' => '1.0.0', // Dodano numer wersji    
    'install' => function ($pluginPath, $adminPath, $pdo) {
        // 1. Przenoszenie plików administracyjnych
        $pluginAdminPath = $pluginPath . '/admin/';
        $targetAdminPath = $adminPath . '/';

        if (is_dir($pluginAdminPath)) {
            $files = scandir($pluginAdminPath);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    if (!copy($pluginAdminPath . $file, $targetAdminPath . $file)) {
                        die("Błąd podczas kopiowania pliku: $file");
                    }
                }
            }
        } else {
            die("Katalog admin nie został znaleziony w pluginie.");
        }

        // 2. Instalacja tabel w bazie danych
        $sql = "
            CREATE TABLE IF NOT EXISTS blog_posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ";

        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            die("Błąd instalacji tabeli bloga: " . $e->getMessage());
        }
    }
];
