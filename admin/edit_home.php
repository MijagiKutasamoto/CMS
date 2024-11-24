<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Pobranie danych strony głównej
$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = 'home'");
$stmt->execute();
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$page) {
    die("Strona główna nie została znaleziona.");
}

// Obsługa aktualizacji strony głównej
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'] ?? '';
    $pluginSlug = $_POST['plugin_slug'] ?? null;

    $stmt = $pdo->prepare("UPDATE pages SET content = :content, plugin_slug = :plugin_slug WHERE slug = 'home'");
    $stmt->execute([
        'content' => $content,
        'plugin_slug' => $pluginSlug,
    ]);

    header('Location: edit_home.php');
    exit;
}

// Pobranie dostępnych pluginów
$stmt = $pdo->query("SELECT slug, name FROM plugins WHERE status = 1");
$availablePlugins = $stmt->fetchAll(PDO::FETCH_ASSOC);

$adminPageTitle = 'Edytuj Stronę Główną';
ob_start();
?>
<h1>Edytuj Stronę Główną</h1>
<form method="POST" action="">
    <label for="editor">Treść strony głównej:</label>
    <div id="editor-container"><?php echo $page['content']; ?></div>
    <textarea id="content" name="content" style="display: none;"></textarea>

    <label for="plugin_slug">Wybierz plugin (opcjonalne):</label>
    <select id="plugin_slug" name="plugin_slug">
        <option value="">Brak</option>
        <?php foreach ($availablePlugins as $plugin): ?>
            <option value="<?php echo htmlspecialchars($plugin['slug']); ?>" <?php echo $page['plugin_slug'] === $plugin['slug'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($plugin['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" onclick="submitContent()">Zapisz zmiany</button>
</form>

<!-- Włączenie Quill.js -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.min.js"></script>

<script>
// Inicjalizacja edytora Quill
var quill = new Quill('#editor-container', {
    theme: 'snow',
    modules: {
        toolbar: {
            container: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                ['link', 'image'], // Dodanie obsługi linków i obrazków
                ['clean']
            ],
            handlers: {
                link: function() {
                    const value = prompt('Wprowadź URL linku:');
                    if (value) {
                        this.quill.format('link', value);
                    }
                }
            }
        }
    }
});


// Obsługa przesyłania obrazków
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
