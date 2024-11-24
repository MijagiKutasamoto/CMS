<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Pobierz zdjęcia
$stmt = $pdo->query("SELECT * FROM gallery ORDER BY created_at DESC");
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obsługa formularza dodawania zdjęć
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $uploadDir = '../uploads/gallery/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $files = $_FILES['images'];
    $successCount = 0;
    $errorCount = 0;

    foreach ($files['name'] as $key => $name) {
        $fileName = basename($name);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($files['tmp_name'][$key], $filePath)) {
            $stmt = $pdo->prepare("INSERT INTO gallery (image_url) VALUES (:image_url)");
            $stmt->execute(['image_url' => '/uploads/gallery/' . $fileName]);
            $successCount++;
        } else {
            $errorCount++;
        }
    }

    header('Location: manage_gallery.php?success=' . $successCount . '&error=' . $errorCount);
    exit;
}

// Przygotowanie treści do szablonu
$adminPageTitle = 'Zarządzanie Galerią';
ob_start();
?>

<h1>Zarządzanie Galerią</h1>
<form method="POST" enctype="multipart/form-data">
    <label for="images">Dodaj zdjęcia:</label>
    <input type="file" name="images[]" id="images" accept="image/*" multiple required>
    <button type="submit">Dodaj</button>
</form>

<div class="gallery-admin">
    <?php foreach ($images as $image): ?>
        <div class="gallery-item">
            <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="Zdjęcie">
            <button class="delete-image" data-image-id="<?php echo $image['id']; ?>">Usuń</button>
        </div>
    <?php endforeach; ?>
</div>

<link rel="stylesheet" href="/plugins/gallery/styles.css">
<script src="/plugins/gallery/script.js"></script>
<script>
    document.querySelectorAll('.delete-image').forEach((button) => {
        button.addEventListener('click', () => {
            if (confirm('Czy na pewno chcesz usunąć to zdjęcie?')) {
                fetch('delete_gallery.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ image_id: button.dataset.imageId }),
                })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === 'success') {
                        button.closest('.gallery-item').remove();
                    } else {
                        alert(data.message);
                    }
                });
            }
        });
    });
</script>
<?php
$adminContent = ob_get_clean();
include '../templates/admin_template.php';
?>
