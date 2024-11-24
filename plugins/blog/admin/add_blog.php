<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obsługa przesyłania obrazków
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    header('Content-Type: application/json');
    $uploadDir = '../uploads/';
    $uploadFile = $uploadDir . basename($_FILES['image']['name']);
    $imagePath = '';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
        $imagePath = '/uploads/' . basename($_FILES['image']['name']);
        echo json_encode(['url' => $imagePath]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Nie udało się przesłać pliku.']);
    }
    exit;
}

// Obsługa dodawania nowego postu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['image'])) {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';

    if ($title && $content) {
        $stmt = $pdo->prepare("INSERT INTO blog_posts (title, content) VALUES (:title, :content)");
        $stmt->execute(['title' => $title, 'content' => $content]);
        header('Location: manage_blog.php');
        exit;
    } else {
        $error = 'Wszystkie pola są wymagane.';
    }
}

$adminPageTitle = 'Dodaj Nowy Post';
ob_start();
?>
<h1>Dodaj Nowy Post</h1>
<?php if (isset($error)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<form method="POST" action="" enctype="multipart/form-data">
    <label for="title">Tytuł:</label>
    <input type="text" id="title" name="title" required>

    <label for="editor">Treść:</label>
    <div id="editor-container" ondrop="drop(event)" ondragover="allowDrop(event)"></div>
    <textarea id="content" name="content" style="display:none;"></textarea>
    <button type="submit" onclick="submitContent()">Dodaj Post</button>
</form>

<script>
// Inicjalizacja edytora Quill
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

// Funkcje Drag & Drop
function allowDrop(event) {
    event.preventDefault();
}

function drag(event) {
    event.dataTransfer.setData("text/html", event.target.outerHTML);
}

function drop(event) {
    event.preventDefault();
    let data = event.dataTransfer.getData("text/html");
    let range = quill.getSelection();
    let node = document.createRange().createContextualFragment(data);
    quill.root.appendChild(node);
}

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

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.url) {
            let range = quill.getSelection();
            quill.insertEmbed(range.index, 'image', result.url);
        } else {
            console.error('Nieprawidłowa odpowiedź serwera:', result);
        }
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
