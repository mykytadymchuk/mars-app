<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../services/databaseConnect.php';
use MongoDB\BSON\ObjectId;
$folderId = $_GET['folder'] ?? '';
$parent = null;
if ($folderId !== '') {
    try {
        $parent = $filesCollection->findOne(['_id' => new ObjectId($folderId), 'Тип' => 'folder']);
    } catch (Exception $e) {
    }
    if (!$parent) {
        header('Location: files.php');
        exit;
    }
}
$back = 'files.php' . ($parent ? '?folder=' . rawurlencode((string) $parent['_id']) : '');
$error = $_GET['error'] ?? '';
$messages = ['empty_name' => 'Введіть назву.', 'file_required' => 'Оберіть файл для завантаження.', 'file_too_large' => 'Файл завеликий. Максимальний розмір — 20 МБ.', 'invalid_file' => 'Не вдалося завантажити файл.', 'item_exists' => 'Елемент з такою назвою вже існує в цій папці.'];
?>
<!doctype html>
<html lang="uk">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" />
    <link rel="stylesheet" href="styles/form-style.css" />
    <link rel="stylesheet" href="styles/general-style.css" />
    <link rel="stylesheet" href="styles/files-style.css" />
    <title>Додавання файлу</title>
</head>

<body>
    <div class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
        <div class="header-right-corner">
            <p id="main-greetings">Вітаю, <?= htmlspecialchars($_SESSION['username']) ?>!</p>
        </div>
    </div>
    <div class="main-body">
        <p class="page-title">ДОДАВАННЯ ФАЙЛУ АБО ПАПКИ</p>
        <form action="services/addFileProcess.php" method="post" enctype="multipart/form-data"><input type="hidden"
                name="folder" value="<?= htmlspecialchars((string) ($parent['_id'] ?? '')) ?>" />
            <div class="form-row"><label class="form-element" for="fileName">Назва</label><input id="fileName"
                    name="name" class="text-input" required /></div>
            <div class="form-row"><label class="form-element">Що додати</label>
                <div class="file-type-options"><label><input type="radio" name="type" value="folder" checked />
                        Папку</label><label><input type="radio" name="type" value="file" /> Файл</label></div>
            </div>
            <div class="form-row" id="upload-row" hidden><label class="form-element"
                    for="uploadedFile">Файл</label><input id="uploadedFile" name="uploadedFile" class="file-input"
                    type="file" /></div>
            <div class="form-buttons"><input type="submit" value="Додати" class="submit-button" /><button type="button"
                    id="cancel-button">Скасувати</button></div>
        </form>
    </div>
    <?php if (isset($messages[$error])): ?>
        <script>alert(<?= json_encode($messages[$error]) ?>);</script><?php endif; ?>
    <script>
        const back = <?= json_encode($back) ?>, upload = document.getElementById('upload-row'), input = document.getElementById('uploadedFile');
        const area = document.createElement('div'), fileName = document.createElement('p');
        area.id = 'file-upload-area'; area.className = 'supplier-photo-upload file-upload-area';
        area.innerHTML = '<div class="supplier-photo-upload-content"><p class="upload-title">Перетягніть файл сюди</p><p class="upload-subtitle">або натисніть, щоб вибрати файл</p></div>';
        fileName.className = 'upload-formats'; fileName.textContent = 'Будь-який тип файлу';
        area.querySelector('.supplier-photo-upload-content').appendChild(fileName);
        input.hidden = true; input.parentNode.appendChild(area); area.appendChild(input);
        document.getElementById('header-logo').onclick = () => location.href = 'main.php';
        document.getElementById('cancel-button').onclick = () => location.href = back;
        document.querySelectorAll('input[name="type"]').forEach(r => r.onchange = () => { const file = document.querySelector('input[name="type"]:checked').value === 'file'; upload.hidden = !file; input.required = file; });
        const showName = () => { fileName.textContent = input.files.length ? input.files[0].name : 'Будь-який тип файлу'; };
        area.onclick = () => input.click(); input.onchange = showName;
        area.ondragover = event => { event.preventDefault(); area.classList.add('dragover'); };
        area.ondragleave = () => area.classList.remove('dragover');
        area.ondrop = event => { event.preventDefault(); area.classList.remove('dragover'); if (event.dataTransfer.files.length) { input.files = event.dataTransfer.files; showName(); } };
    </script>
</body>

</html>