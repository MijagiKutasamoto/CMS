<?php
// admin/manage_pages.php

session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Pobranie stron z bazy danych
$stmt = $pdo->query("SELECT * FROM pages ORDER BY created_at DESC");
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Przygotowanie treści do szablonu
$adminPageTitle = 'Zarządzanie Stronami';
ob_start();
?>
<h1>Zarządzanie Stronami</h1>
<a href="add_page.php">Dodaj Nową Stronę</a>
<table border="1">
    <tr>
        <th>Tytuł</th>
        <th>Slug</th>
        <th>Data Utworzenia</th>
        <th>Akcje</th>
    </tr>
    <?php foreach ($pages as $page): ?>
        <tr>
            <td><?php echo htmlspecialchars($page['title']); ?></td>
            <td><?php echo htmlspecialchars($page['slug']); ?></td>
            <td><?php echo $page['created_at']; ?></td>
            <td>
                <a href="edit_page.php?id=<?php echo $page['id']; ?>">Edytuj</a> |
                <a href="delete_page.php?id=<?php echo $page['id']; ?>" onclick="return confirm('Czy na pewno chcesz usunąć tę stronę?')">Usuń</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>


<?php
$adminContent = ob_get_clean();

// Wczytanie szablonu panelu admina
include '../templates/admin_template.php';
?>
