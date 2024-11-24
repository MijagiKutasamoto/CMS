<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page['title'] ?? 'Strona'); ?></title>

    <?php
    // Pobranie układu strony
$layout = $page['layout'] ?? 'default';

// Wczytanie CSS dla wybranego układu
echo '<link rel="stylesheet" href="/assets/styles/' . htmlspecialchars($layout) . '.css">';
?>


</head>
<body>

<header class="navbar">
    <div class="logo">
        <a href="/"><img src="<?php echo htmlspecialchars($settings['logo'] ?? '/assets/images/default-logo.png'); ?>" alt="Logo"></a>
    </div>
        <nav class="nav-links">
        <ul>
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

<?php
// Deklaracja zmiennej $sliderImages jako pustej tablicy, gdy slider jest wyłączony lub brak zdjęć
$sliderImages = !empty($settings['slider_images']) ? json_decode($settings['slider_images'], true) : [];
?>

<?php if (!empty($settings['enable_slider']) && !empty($sliderImages)): ?>
    <div class="carousel">
        <div class="list">
            <?php foreach ($sliderImages as $image): ?>
                <div class="item" style="background-image: url('<?php echo htmlspecialchars($image); ?>');"></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <style>
        /* Przesunięcie <main> pod navbar, gdy slider jest wyłączony */
        main {
            margin-top: 60px;
        }
    </style>
<?php endif; ?>


<main>
    <?php echo $content ?? '<p>Brak treści do wyświetlenia.</p>'; ?>
</main>

<footer>
    <div class="footer-content">
        <p>&copy; <?php echo date("Y"); ?> CMS. Wszelkie prawa zastrzeżone.</p>
    </div>
</footer>
<script>
function toggleMenu() {
    const nav = document.querySelector('.nav-links');
    const burger = document.querySelector('.burger');
    nav.classList.toggle('nav-active');
    burger.classList.toggle('toggle');
}
</script>
<script src="templates/script.js"></script>
<script src="assets/js/script.js"></script>
<?php echo "<style>:root { --slider-count: " . count($sliderImages) . "; }</style>"; ?>
</body>
</html>
