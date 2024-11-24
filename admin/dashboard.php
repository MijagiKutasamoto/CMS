<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Pobranie aktualnych ustawień z bazy danych
$stmt = $pdo->query("SELECT * FROM settings");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enable_slider = isset($_POST['enable_slider']) ? 1 : 0;
    $logo = $settings['logo'];
    $slider_images = json_decode($settings['slider_images'], true) ?? [];
    $cms_name = $_POST['cms_name'] ?? $settings['site_name'];

    // Obsługa przesyłania logo
    if (isset($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
        $uploadDir = '../uploads/';
        $logoName = basename($_FILES['logo']['name']);
        $logoPath = $uploadDir . $logoName;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $logoPath)) {
            $logo = '/uploads/' . $logoName;
        }
    }

    // Obsługa przesyłania zdjęć slidera
    if (isset($_FILES['slider_images'])) {
        $uploadDir = '../uploads/';
        foreach ($_FILES['slider_images']['tmp_name'] as $index => $tmpName) {
            if (is_uploaded_file($tmpName)) {
                $fileName = basename($_FILES['slider_images']['name'][$index]);
                $filePath = $uploadDir . $fileName;
                if (move_uploaded_file($tmpName, $filePath)) {
                    $slider_images[] = '/uploads/' . $fileName;
                }
            }
        }
    }

    // Usuwanie wybranych zdjęć ze slidera
    if (isset($_POST['delete_slider_images'])) {
        $imagesToDelete = $_POST['delete_slider_images'];
        foreach ($imagesToDelete as $image) {
            $imageKey = array_search($image, $slider_images);
            if ($imageKey !== false) {
                unset($slider_images[$imageKey]);
                $slider_images = array_values($slider_images); // Przebudowanie indeksów
            }
        }
    }

    // Aktualizacja ustawień w bazie danych
    $stmt = $pdo->prepare("UPDATE settings SET enable_slider = :enable_slider, logo = :logo, slider_images = :slider_images, site_name = :cms_name WHERE id = 1");
    $stmt->execute([
        'enable_slider' => $enable_slider,
        'logo' => $logo,
        'slider_images' => json_encode($slider_images),
        'cms_name' => $cms_name,
    ]);

    header('Location: dashboard.php');
    exit;
}

$adminPageTitle = 'Ustawienia CMS';
ob_start();
?>
<h1>Ustawienia CMS</h1>
<form method="POST" action="" enctype="multipart/form-data">
    <label for="cms_name">Nazwa CMS:</label><br>
    <input type="text" name="cms_name" id="cms_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>"><br><br>

    <label for="logo">Logo strony:</label><br>
    <?php if (!empty($settings['logo'])): ?>
        <img src="<?php echo htmlspecialchars($settings['logo']); ?>" alt="Logo" style="max-width: 200px; display: block;">
    <?php endif; ?>
    <input type="file" name="logo" id="logo"><br><br>

    <label for="enable_slider">Włącz slider:</label>
    <input type="checkbox" name="enable_slider" id="enable_slider" <?php echo $settings['enable_slider'] ? 'checked' : ''; ?>><br><br>

    <label for="slider_images">Zdjęcia slidera:</label><br>
    <?php
    $sliderImages = json_decode($settings['slider_images'], true) ?? [];
    foreach ($sliderImages as $image): ?>
        <div style="margin-bottom: 10px;">
            <img src="<?php echo htmlspecialchars($image); ?>" alt="Slider Image" style="max-width: 200px; display: block;">
            <label>
                <input type="checkbox" name="delete_slider_images[]" value="<?php echo htmlspecialchars($image); ?>">
                Usuń zdjęcie
            </label>
        </div>
    <?php endforeach; ?>
    <input type="file" name="slider_images[]" id="slider_images" multiple><br><br>

    <button type="submit">Zapisz ustawienia</button>
</form>
<?php
$adminContent = ob_get_clean();
include '../templates/admin_template.php';
?>
