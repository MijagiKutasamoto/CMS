<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo htmlspecialchars($adminPageTitle ?? 'Panel'); ?></title>
    <link rel="stylesheet" href="../assets/styles/admin_style.css">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />

</head>
<body>
<header class="navbar">
    <div class="logo">
        <H2>mijagiCMS</H2> <!-- Zamień na ścieżkę do swojego logo -->
    </div>
    <nav class="nav-links">
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="manage_pages.php">Strony</a></li>
        <li><a href="edit_home.php">Edytuj Stronę Główną</a></li>
        <li><a href="manage_plugins.php">Pluginy</a></li>
        <?php
        // Dodaj aktywne pluginy do menu
        $stmt = $pdo->query("SELECT menu_title, menu_url FROM plugins WHERE status = 1 AND menu_title IS NOT NULL AND menu_url IS NOT NULL");
        $menuPlugins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($menuPlugins as $menuPlugin): ?>
            <li><a href="<?php echo htmlspecialchars($menuPlugin['menu_url']); ?>">
                <?php echo htmlspecialchars($menuPlugin['menu_title']); ?>
            </a></li>
        <?php endforeach; ?>
    </ul>
</nav>


    <div class="burger" onclick="toggleMenu()">
        <div class="line1"></div>
        <div class="line2"></div>
        <div class="line3"></div>
    </div>
</header>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

<script>
function toggleMenu() {
    const nav = document.querySelector('.nav-links');
    const burger = document.querySelector('.burger');
    nav.classList.toggle('nav-active');
    burger.classList.toggle('toggle');
}
</script>

    <main>
        <?php echo $adminContent ?? ''; ?>
    </main>
    <footer>
    <div class="footer-content">
        <p>&copy; <?php echo date("Y"); ?> CMS. Wszelkie prawa zastrzeżone.</p>
        <p>Dodatkowe informacje lub treść.</p>
    </div>
</footer>

</body>
</html>
