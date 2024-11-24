<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Pobranie wszystkich postów z bazy danych
$stmt = $pdo->query("SELECT * FROM blog_posts ORDER BY created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Przygotowanie treści do szablonu
$adminPageTitle = 'Zarządzanie Blogami';
ob_start();
?>
<h1>Zarządzanie Blogami</h1>
<a href="add_blog.php" class="btn btn-add">Stwórz nowy post</a>

<table class="admin-table">
    <thead>
        <tr>
            <th>Tytuł</th>
            <th>Data Utworzenia</th>
            <th>Akcje</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($posts as $post): ?>
            <tr>
                <td><?php echo htmlspecialchars($post['title']); ?></td>
                <td><?php echo $post['created_at']; ?></td>
                <td>
                    <a href="edit_blog.php?id=<?php echo $post['id']; ?>" class="btn btn-edit">Edytuj</a>
                    <a href="delete_blog.php?id=<?php echo $post['id']; ?>" class="btn btn-delete" onclick="return confirm('Czy na pewno chcesz usunąć ten post?')">Usuń</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<style>
    h1 {
        font-size: 24px;
        margin-bottom: 20px;
        color: #14ff72;
    }

    .btn {
        display: inline-block;
        margin: 10px 5px;
        padding: 10px 20px;
        color: #fff;
        background-color: #14ff72;
        border: none;
        border-radius: 5px;
        text-decoration: none;
        font-size: 14px;
        font-weight: bold;
        transition: background-color 0.3s ease;
        cursor: pointer;
    }

    .btn:hover {
        background-color: #0fbf56;
    }

    .btn-add {
        margin-bottom: 15px;
    }

    .btn-edit {
        background-color: #ffa726;
    }

    .btn-edit:hover {
        background-color: #ff9800;
    }

    .btn-delete {
        background-color: #ef5350;
    }

    .btn-delete:hover {
        background-color: #e53935;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        background-color: #1e1e1e;
        color: #e0e0e0;
        border-radius: 10px;
        overflow: hidden;
    }

    .admin-table th, .admin-table td {
        padding: 12px 15px;
        text-align: left;
        border: 1px solid #444;
    }

    .admin-table th {
        background-color: #2c2c2c;
        font-weight: bold;
        color: #14ff72;
    }

    .admin-table tr:hover {
        background-color: #333;
    }

    .admin-table td a {
        text-decoration: none;
        color: inherit;
    }
</style>

<?php
$adminContent = ob_get_clean();
include '../templates/admin_template.php';
?>
