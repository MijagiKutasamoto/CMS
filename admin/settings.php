<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page['title']); ?></title>

    <link rel="stylesheet" href="templates/style.css">

</head>
<body>
    
<header class="navbar">
    <div class="logo">
        <a href="/"><img src="/assets/images/logo.png" alt="Logo"></a> <!-- Zamień na ścieżkę do swojego logo -->
    </div>
    <nav class="nav-links">
        <ul>
            <li><a href="/index.php">Strona główna</a></li>
            <!-- Pobieranie stron do nawigacji -->
            <?php
            $stmt = $pdo->query("SELECT title, slug FROM pages ORDER BY created_at ASC");
            $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($pages as $page): ?>
                <li><a href="/<?php echo htmlspecialchars($page['slug']); ?>"><?php echo htmlspecialchars($page['title']); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div class="burger" onclick="toggleMenu()">
        <div class="line1"></div>
        <div class="line2"></div>
        <div class="line3"></div>
    </div>
</header>


    <div class="carousel">

        <div class="list">

            <div class="item" style="background-image: url(templates/eagel1.jpg);">
                <div class="content">
                    <div class="title">Tytuł</div>
                    <div class="des">Jakiś opis tutaj damy skrócony.</div>
                    <div class="btn">
                        <button>Pokaż więcej</button>
                    </div>
                </div>
            </div>

            <div class="item" style="background-image: url(templates/owl1.jpg);">
                
                <div class="content">
                    <div class="title">Tytuł</div>
                    <div class="des">Jakiś opis tutaj damy skrócony.</div>
                    <div class="btn">
                        <button>Pokaż więcej</button>
                    </div>
                </div>

            </div>

            

            </div>

        </div>

        <main>
        <?php echo $content ?? ''; ?>
    </main>
    <footer>
        <div class="footer-content">
            <p>&copy; <?php echo date("Y"); ?> CMS. Wszelkie prawa zastrzeżone.</p>
            <p>Dodatkowe informacje lub treść.</p>
        </div>
    </footer>

        <!--next prev button-->
        <div class="arrows">
            <button class="prev"><</button>
            <button class="next">></button>
        </div>


        <!-- time running -->
        <div class="timeRunning"></div>

    </div>

    <script src="templates/script.js"></script>
</body>
</html>