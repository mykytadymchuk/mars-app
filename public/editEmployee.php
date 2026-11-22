<?php
session_start();
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/../services/databaseConnect.php';
use MongoDB\BSON\ObjectId;
$user = $usersCollection->findOne(["Логін" => $_SESSION["username"]]);
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}
$employeeId = $_GET["id"] ?? null;
$error = $_GET["error"] ?? null;
try {
    $employeeObjectId = new ObjectId($employeeId);
} catch (Exception $e) {
    header("Location: employees.php");
    exit;
}
$employee = $employeesCollection->findOne(["_id" => $employeeObjectId]);
if (!$employee) {
    header("Location: employees.php");
    exit;
}
$roles = $rolesCollection->find()->toArray();
$defaults = ["Менеджер", "Дизайнер", "Бухгалтер", "Друкар", "Макетник"];
if (empty($roles))
    $roles = array_map(fn($name) => ["Назва" => $name], $defaults);
$reserved = ["_id", "Прізвище", "Ім'я", "Роль", "Номер телефону", "Дата прийняття на роботу", "Фото", "Логін", "Перший пароль"];
$custom = [];
foreach ($employee as $key => $value) {
    if (!in_array($key, $reserved, true))
        $custom[$key] = $value;
}
$messages = ["empty_name" => "Введіть прізвище та ім'я працівника.", "invalid_role" => "Оберіть роль працівника.", "incorrect_phone_number_format" => "Некоректний формат номера телефону.", "employee_exists" => "Працівник з таким номером телефону вже існує.", "unknown_error" => "Дані не було змінено."];
?>
<?php unset($custom["\xD0\xA1\xD1\x82\xD0\xB0\xD1\x82\xD1\x83\xD1\x81"]); ?>
<?php $messages["too_large_file"] = "Файл завеликий. Максимальний розмір — 20 МБ."; ?>
<!doctype html>
<html lang="uk">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" />
    <link rel="stylesheet" href="styles/form-style.css" />
    <link rel="stylesheet" href="styles/general-style.css" />
    <title>Редагування працівника</title>
</head>

<body>
    <div class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
        <div class="header-right-corner">
            <p id="main-greetings">Вітаю, <?= htmlspecialchars($_SESSION["username"]) ?>!</p>
        </div>
    </div>
    <div class="main-body">
        <p class="page-title">РЕДАГУВАННЯ ПРАЦІВНИКА</p>
        <form action="services/editEmployeeProcess.php" method="post" enctype="multipart/form-data"><input type="hidden"
                name="id" value="<?= htmlspecialchars($employeeId) ?>" />
            <div class="form-row"><label class="form-element">Прізвище</label><input name="employeeLastName"
                    class="text-input" value="<?= htmlspecialchars($employee["Прізвище"] ?? "") ?>" required /></div>
            <div class="form-row"><label class="form-element">Ім'я</label><input name="employeeFirstName"
                    class="text-input" value="<?= htmlspecialchars($employee["Ім'я"] ?? "") ?>" required /></div>
            <div class="form-row"><label class="form-element">Роль</label><select name="employeeRole" class="text-input"
                    required><?php foreach ($roles as $role): ?>
                        <option value="<?= htmlspecialchars($role["Назва"]) ?>"
                            <?= ($employee["Роль"] ?? "") === $role["Назва"] ? "selected" : "" ?>>
                            <?= htmlspecialchars($role["Назва"]) ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-row"><label class="form-element">Номер телефону</label><input type="tel"
                    name="employeePhone" class="text-input"
                    value="<?= htmlspecialchars($employee["Номер телефону"] ?? "") ?>" pattern="\+380\d{9}" required />
            </div>
            <div class="form-row"><label class="form-element">Дата прийняття на роботу</label><input class="text-input"
                    value="<?= htmlspecialchars($employee["Дата прийняття на роботу"] ?? "") ?>" readonly /></div>
            <div class="form-row"><label class="form-element">Фото працівника</label>
                <div class="supplier-photo-upload" id="employeePhotoUpload"><input type="file" name="employeePhoto"
                        id="employeePhoto" accept="image/png,image/jpeg,image/webp" hidden />
                    <div class="supplier-photo-upload-content" id="uploadContent">
                        <p class="upload-title">Перетягніть фото сюди</p>
                        <p class="upload-subtitle">або натисніть, щоб вибрати файл</p>
                        <p class="upload-formats">PNG, JPG або WEBP</p>
                    </div><img id="photoPreview" class="supplier-photo-preview" alt="Попередній перегляд"
                        <?= !empty($employee["Фото"]) ? 'src="src/employees/' . htmlspecialchars($employee["Фото"]) . '" style="display:block"' : '' ?> />
                </div>
            </div>
            <div class="form-row"><label class="form-element">Додаткові поля</label>
                <div id="custom-fields-container"><?php foreach ($custom as $key => $value): ?>
                        <div class="custom-field-row"><div class="custom-field-cols-container"><div class="custom-field-cols-element"><p class="form-element">Параметр</p><input name="customFieldName[]" class="text-input custom-field-name" value="<?= htmlspecialchars($key) ?>" placeholder="Назва поля" /></div><div class="custom-field-cols-element"><p class="form-element">Значення</p><input name="customFieldValue[]" class="text-input custom-field-value" value="<?= htmlspecialchars((string) $value) ?>" placeholder="Значення" /></div><div class="custom-field-cols-element"><button type="button" class="remove-custom-field" aria-label="Видалити поле">×</button></div></div></div><?php endforeach; ?>
                </div><button type="button" id="add-custom-field" class="add-custom-field-button">+ Додати поле</button>
            </div>
            <div class="form-buttons"><input type="submit" value="Змінити" class="submit-button" /><button type="button"
                    id="cancel-button">Скасувати</button></div>
        </form>
    </div>
    <?php if (isset($messages[$error])): ?>
        <script>alert(<?= json_encode($messages[$error]) ?>); history.replaceState({}, document.title, location.pathname);</script>
    <?php endif; ?>
    <script>
        const add = document.getElementById("add-custom-field"), container = document.getElementById("custom-fields-container"); add.onclick = () => { const row = document.createElement("div"); row.className = "custom-field-row"; row.innerHTML = '<div class="custom-field-cols-container"><div class="custom-field-cols-element"><p class="form-element">Параметр</p><input name="customFieldName[]" class="text-input custom-field-name" placeholder="Назва поля" /></div><div class="custom-field-cols-element"><p class="form-element">Значення</p><input name="customFieldValue[]" class="text-input custom-field-value" placeholder="Значення" /></div><div class="custom-field-cols-element"><button type="button" class="remove-custom-field" aria-label="Видалити поле">×</button></div></div>'; container.appendChild(row) }; container.onclick = e => { if (e.target.classList.contains("remove-custom-field")) e.target.closest(".custom-field-row").remove() }; const area = document.getElementById("employeePhotoUpload"), file = document.getElementById("employeePhoto"), preview = document.getElementById("photoPreview"), content = document.getElementById("uploadContent"); area.onclick = () => file.click(); file.onchange = () => show(file.files[0]); area.ondragover = e => { e.preventDefault(); area.classList.add("dragover") }; area.ondrop = e => { e.preventDefault(); file.files = e.dataTransfer.files; show(file.files[0]) }; function show(image) { if (!image || !image.type.startsWith("image/")) return; const r = new FileReader(); r.onload = e => { preview.src = e.target.result; preview.style.display = "block"; content.style.display = "none" }; r.readAsDataURL(image) } document.getElementById("header-logo").onclick = () => location.href = "main.php"; document.getElementById("cancel-button").onclick = () => location.href = "employees.php";
    </script>
    <script>if (document.getElementById("photoPreview").getAttribute("src")) document.getElementById("uploadContent").style.display = "none";</script>
    <form action="services/fireEmployeeProcess.php" method="post" id="fire-form"><input type="hidden" name="id"
            value="<?= htmlspecialchars($employeeId) ?>" />
        <div class="form-buttons"><button type="submit" id="fire-button">Звільнити</button></div>
    </form>
    <script>document.getElementById("fire-form").addEventListener("submit", (event) => { if (!confirm("Ви впевнені, що хочете звільнити працівника?")) event.preventDefault(); });</script>
</body>

</html>
