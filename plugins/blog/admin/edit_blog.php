<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Pobranie danych posta do edycji
$postId = $_GET['id'] ?? null;
if ($postId) {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = :id");
    $stmt->execute(['id' => $postId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        die('Post nie znaleziony.');
    }
} else {
    die('Nieprawidłowe ID posta.');
}

// Obsługa aktualizacji posta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';

    if ($title && $content) {
        $stmt = $pdo->prepare("UPDATE blog_posts SET title = :title, content = :content, updated_at = NOW() WHERE id = :id");
        $stmt->execute(['title' => $title, 'content' => $content, 'id' => $postId]);
        header('Location: manage_blog.php');
        exit;
    } else {
        $error = 'Wszystkie pola są wymagane.';
    }
}

$adminPageTitle = 'Edytuj Post';
ob_start();
?>
<h1>Edytuj Post</h1>
<?php if (isset($error)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<form method="POST" action="">
    <label for="title">Tytuł:</label>
    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
    <label for="editor">Treść:</label>
    <div id="editor-container"><?php echo $post['content']; ?></div>
    <textarea id="content" name="content" style="display:none;"></textarea>
    <button type="submit" onclick="submitContent()">Zapisz zmiany</button>
</form>
 
<script>
// Inicjalizacja edytora Quill z istniejącą treścią i dodatkowymi modułami
var quill = new Quill('#editor-container', {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'color': [] }, { 'background': [] }],
            ['blockquote', 'code-block'],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            ['link', 'image'],
            ['clean']
        ]
    }
});

// Funkcja do obsługi przesyłania obrazków
quill.getModule('toolbar').addHandler('image', function() {
    selectLocalImage();
});

function selectLocalImage() {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/*');
    input.click();

    input.onchange = () => {
        const file = input.files[0];
        if (/^image\//.test(file.type)) {
            saveToServer(file);
        } else {
            console.warn('Wybierz plik graficzny.');
        }
    };
}

function saveToServer(file) {
    const formData = new FormData();
    formData.append('image', file);

    fetch('add_blog.php', { // Ścieżka do pliku obsługującego przesyłanie obrazków
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        let range = quill.getSelection();
        quill.insertEmbed(range.index, 'image', result.url);
    })
    .catch(err => {
        console.error('Błąd przesyłania obrazka:', err);
    });
}

// Przekazanie treści z edytora Quill do pola tekstowego podczas wysyłania formularza
function submitContent() {
    document.querySelector('#content').value = quill.root.innerHTML;
}
</script>
<?php
$adminContent = ob_get_clean();
include '../templates/admin_template.php';
?>
