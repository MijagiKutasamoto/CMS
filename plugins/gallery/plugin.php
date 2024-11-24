<?php
// Sprawdzenie autoryzacji
define('PLUGIN_ACCESS', true);

// Pobierz zdjęcia z bazy danych
$stmt = $pdo->query("SELECT * FROM gallery ORDER BY created_at DESC");
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="/plugins/gallery/styles.css">
<script src="/plugins/gallery/script.js"></script>

<div class="gallery-container">
    <?php foreach ($images as $image): ?>
        <div class="gallery-item">
            <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="Zdjęcie">
        </div>
    <?php endforeach; ?>
</div>
