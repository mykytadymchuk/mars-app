<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../services/databaseConnect.php';
use MongoDB\BSON\ObjectId;

$user = $usersCollection->findOne(['Логін' => $_SESSION['username']]);
if (!$user) { session_destroy(); header('Location: login.php'); exit; }
$folderId = $_GET['folder'] ?? null;
$currentFolder = null;
if ($folderId !== null && $folderId !== '') {
    try { $currentFolder = $filesCollection->findOne(['_id' => new ObjectId($folderId), 'Тип' => 'folder']); } catch (Exception $e) {}
    if (!$currentFolder) { header('Location: files.php'); exit; }
}
$search = trim($_GET['search'] ?? '');
$field = $_GET['field'] ?? 'name';
$query = ['Батьківська папка' => $currentFolder['_id'] ?? null];
if ($search !== '') {
    $searchField = $field === 'type' ? 'Тип' : ($field === 'date' ? 'Дата завантаження' : 'Назва');
    $query[$searchField] = ['$regex' => preg_quote($search, '/'), '$options' => 'i'];
}
$items = $filesCollection->find($query)->toArray();
usort($items, static function ($left, $right): int {
    $leftFolder = ($left['Тип'] ?? '') === 'folder';
    $rightFolder = ($right['Тип'] ?? '') === 'folder';
    if ($leftFolder !== $rightFolder) return $leftFolder ? -1 : 1;
    return strcasecmp((string) ($left['Назва'] ?? ''), (string) ($right['Назва'] ?? ''));
});
?>
<!doctype html><html lang="uk"><head>
<meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" />
<link rel="stylesheet" href="styles/general-style.css" /><link rel="stylesheet" href="styles/flex-grid-page-style.css" /><link rel="stylesheet" href="styles/search-bar-style.css" /><link rel="stylesheet" href="styles/page-header-style.css" /><link rel="stylesheet" href="styles/files-style.css" /><title>Файли</title>
  <link rel="stylesheet" href="styles/responsive.css" />
</head><body>
<form method="get"><div class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
<?php if ($currentFolder): ?><input type="hidden" name="folder" value="<?= htmlspecialchars((string) $currentFolder['_id']) ?>" /><?php endif; ?>
<div class="search-bar-container"><select name="field" class="search-select"><option value="name" <?= $field === 'name' ? 'selected' : '' ?>>Назва</option><option value="type" <?= $field === 'type' ? 'selected' : '' ?>>Тип</option><option value="date" <?= $field === 'date' ? 'selected' : '' ?>>Дата завантаження</option></select><input name="search" class="search-bar" placeholder="Пошук" value="<?= htmlspecialchars($search) ?>" /><button class="search-button"><img src="src/search.png" alt="search" /></button></div>
<div class="add-new-item" id="add-new-file">+</div><?php if ($currentFolder): ?><button type="button" class="previous-folder-button" id="previous-folder-button">← Попередня папка</button><?php endif; ?><div class="header-right-corner"><p id="main-greetings">Вітаю, <?= htmlspecialchars($_SESSION['username']) ?>!</p></div></div></form>
<?php if ($currentFolder): ?><div class="files-navigation"><span>Поточна папка: <?= htmlspecialchars($currentFolder['Назва']) ?></span></div><?php endif; ?>
<div class="main-body"><?php if (!$items): ?><p class="none-message">У цій папці ще немає файлів або папок.</p><?php endif; ?>
<?php foreach ($items as $item): $isFolder = ($item['Тип'] ?? '') === 'folder'; $mime = $item['MIME тип'] ?? ''; $storedFile = $item['Файл'] ?? ''; $isImage = !$isFolder && str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml'; ?>
<div class="flex-element file-card" <?= $isFolder ? 'data-folder-id="' . htmlspecialchars((string) $item['_id']) . '"' : ($storedFile ? 'data-file-url="src/files/' . rawurlencode($storedFile) . '" data-download-name="' . htmlspecialchars($item['Оригінальна назва'] ?? $item['Назва'] ?? 'file') . '"' : '') ?>>
<?php if ($isFolder): ?><img src="src/folder.png" alt="folder" class="folder-icon" draggable="false" /><?php elseif ($isImage && $storedFile): ?><img src="src/files/<?= rawurlencode($storedFile) ?>" alt="<?= htmlspecialchars($item['Назва']) ?>" class="files-icon" draggable="false" /><?php else: ?><img src="src/files.png" alt="file" class="file-icon" draggable="false" /><?php endif; ?>
<div class="flex-element-main-info-container"><p class="flex-element-name"><?= htmlspecialchars($item['Назва'] ?? '') ?></p></div>
<?php if (!$isFolder): ?><div class="flex-element-info-container"><hr /><p><span class="row-title">Дата завантаження:</span> <?= htmlspecialchars($item['Дата завантаження'] ?? '') ?></p><p><span class="row-title">Тип:</span> <?= htmlspecialchars($mime ?: 'Файл') ?></p><hr /></div><?php endif; ?>
<div class="flex-element-edit-container"><p class="flex-element-edit file-edit" data-id="<?= htmlspecialchars((string) $item['_id']) ?>">Редагувати</p><form action="services/deleteFileProcess.php" method="post" class="file-action-form"><input type="hidden" name="id" value="<?= htmlspecialchars((string) $item['_id']) ?>" /><input type="hidden" name="folder" value="<?= htmlspecialchars((string) ($currentFolder['_id'] ?? '')) ?>" /><p class="flex-element-edit file-delete" role="button" tabindex="0">Видалити</p></form></div>
</div><?php endforeach; ?></div>
<script>
const folder=<?= json_encode($currentFolder ? (string) $currentFolder['_id'] : '') ?>;
const parentFolder=<?= json_encode(!empty($currentFolder['Батьківська папка']) ? (string) $currentFolder['Батьківська папка'] : '') ?>;
document.getElementById('header-logo').onclick=()=>location.href='main.php'; document.getElementById('add-new-file').onclick=()=>location.href='addFile.php'+(folder?'?folder='+encodeURIComponent(folder):'');
const previousFolderButton=document.getElementById('previous-folder-button'); if(previousFolderButton) previousFolderButton.onclick=()=>location.href='files.php'+(parentFolder?'?folder='+encodeURIComponent(parentFolder):'');
document.querySelectorAll('.file-card[data-folder-id]').forEach(card=>card.onclick=event=>{if(!event.target.closest('.flex-element-edit-container'))location.href='files.php?folder='+encodeURIComponent(card.dataset.folderId);});
document.querySelectorAll('.file-card[data-file-url]').forEach(card=>card.onclick=event=>{if(event.target.closest('.flex-element-edit-container'))return;const link=document.createElement('a');link.href=card.dataset.fileUrl;link.download=card.dataset.downloadName;link.style.display='none';document.body.appendChild(link);link.click();link.remove();});
document.querySelectorAll('.file-edit').forEach(button=>button.onclick=event=>{event.stopPropagation();location.href='editFile.php?id='+encodeURIComponent(button.dataset.id);});
document.querySelectorAll('.file-action-form').forEach(form=>{form.onclick=event=>event.stopPropagation();const remove=()=>{if(confirm('Ви впевнені, що хочете видалити цей елемент?'))form.requestSubmit();};form.querySelector('.file-delete').onclick=remove;form.querySelector('.file-delete').onkeydown=event=>{if(event.key==='Enter'||event.key===' ') {event.preventDefault();remove();}};});
</script>  <script src="scripts/chat-notifications.js" defer></script>
</body></html>
