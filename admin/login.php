<?php
// login.php

session_start();
require_once '../config/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Zapytanie do bazy danych w celu sprawdzenia danych logowania
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Ustawienie sesji po udanym logowaniu
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Nieprawidłowa nazwa użytkownika lub hasło.';
    }
}
// Przygotowanie treści do szablonu
$adminPageTitle = 'Edytuj Stronę';
ob_start();
?>

    <form method="POST" action="">
        <h2>Logowanie</h2>
        <?php if ($error): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <label for="username">Nazwa użytkownika:</label>
        <input type="text" id="username" name="username" required>
        <label for="password">Hasło:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Zaloguj</button>
    </form>
<?php
$adminContent = ob_get_clean();
include '../templates/admin_template.php';
?>
 