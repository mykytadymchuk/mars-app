<?php

session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../services/databaseConnect.php';

$user = $usersCollection->findOne([
    "Логін" => $_SESSION["username"]
]);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$currentUsername = $user["Логін"];

use MongoDB\BSON\ObjectId;

$error = $_GET["error"] ?? null;
$suppliersId = $_GET["id"] ?? null;

if (!$suppliersId) {
    header("Location: suppliers.php");
    exit;
}

try {
    $supplierObjectId = new ObjectId($suppliersId);
} catch (Exception $e) {
    header("Location: suppliers.php");
    exit;
}

$supplier = $suppliersCollection->findOne([
    "_id" => $supplierObjectId
]);

if (!$supplier) {
    header("Location: suppliers.php");
    exit;
}

$supplierName = $supplier["Постачальник"] ?? "";
    $supplierPhone = $supplier["Номер телефону"] ?? "";
    $supplierPhoto = $supplier["Фото"] ?? null;
$systemFieldNames = ["_id", "Постачальник", "Номер телефону", "Фото", "Додаткові поля"];
$customFields = [];

// Read both the old nested layout and the new top-level layout so existing
// supplier records remain editable while they are migrated.
if (!empty($supplier["Додаткові поля"]) && is_iterable($supplier["Додаткові поля"])) {
    foreach ($supplier["Додаткові поля"] as $fieldName => $fieldValue) {
        $customFields[$fieldName] = $fieldValue;
    }
}

foreach ($supplier as $fieldName => $fieldValue) {
    if (!in_array($fieldName, $systemFieldNames, true)) {
        $customFields[$fieldName] = $fieldValue;
    }
}

?>


<!doctype html>
<html lang="uk">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="styles/form-style.css" />
    <link rel="stylesheet" href="styles/general-style.css" />
    <title>Редагування постачальника</title>
</head>

<body>
    <div class="main-header">
        <img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
        <div class="header-right-corner">
            <p id="main-greetings">Вітаю, <?= htmlspecialchars($_SESSION["username"]) ?>!</p>
        </div>
    </div>
    <div class="main-body">
        <p class="page-title">РЕДАГУВАННЯ ПОСТАЧАЛЬНИКА</p>

        <form action="services/editSupplierProcess.php" method="post" enctype="multipart/form-data">

            <input type="hidden" name="id" value="<?= htmlspecialchars($suppliersId) ?>">

            <div class="form-row">
                <label class="form-element" for="suppliersName">
                    Назва постачальника
                </label>

                <input type="text" name="suppliersName" class="text-input" id="suppliersName"
                    value="<?= htmlspecialchars($supplierName) ?>" required />
            </div>

            <div class="form-row">
                <label class="form-element" for="suppliersPhone">
                    Номер телефону
                </label>

                <input type="tel" class="text-input" name="suppliersPhone" id="suppliersPhone"
                    value="<?= htmlspecialchars($supplierPhone) ?>" placeholder="+380ХХХХХХХХХ" pattern="\+380\d{9}"
                    required>
            </div>

            <div class="form-row">
                <label class="form-element" for="supplierPhoto">
                    Фото постачальника
                </label>

                <div class="supplier-photo-upload" id="supplierPhotoUpload">

                    <input type="file" name="supplierPhoto" id="supplierPhoto"
                        accept="image/png, image/jpeg, image/webp" hidden />

                    <div class="supplier-photo-upload-content" id="uploadContent" <?= $supplierPhoto ? 'style="display: none"' : '' ?>>

                        <p class="upload-title">
                            Перетягніть фото сюди
                        </p>

                        <p class="upload-subtitle">
                            або натисніть, щоб вибрати файл
                        </p>

                        <p class="upload-formats">
                            PNG, JPG або WEBP
                        </p>

                    </div>

                    <img id="photoPreview" class="supplier-photo-preview" alt="Попередній перегляд" draggable="false"
                        <?= $supplierPhoto ? 'src="src/suppliers/' . htmlspecialchars($supplierPhoto) . '" style="display: block"' : '' ?> />

                </div>
            </div>

            <div class="form-row">

                <label class="form-element">
                    Додаткові поля
                </label>

                <div id="custom-fields-container">

                    <?php if (!empty($customFields)): ?>

                        <?php foreach ($customFields as $fieldName => $fieldValue): ?>

                            <div class="custom-field-row">
                                <div class="custom-field-cols-container">
                                    <div class="custom-field-cols-element">
                                        <p class="form-element">Параметр</p>
                                        <input type="text" name="customFieldName[]" class="text-input custom-field-name" placeholder="Назва поля" value="<?= htmlspecialchars($fieldName) ?>" />
                                    </div>
                                    <div class="custom-field-cols-element">
                                        <p class="form-element">Значення</p>
                                        <input type="text" name="customFieldValue[]" class="text-input custom-field-value" placeholder="Значення" value="<?= htmlspecialchars($fieldValue) ?>" />
                                    </div>
                                    <div class="custom-field-cols-element"><button type="button" class="remove-custom-field" aria-label="Видалити поле">×</button></div>
                                </div>
                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                <button type="button" id="add-custom-field" class="add-custom-field-button">
                    + Додати поле
                </button>

            </div>

            <div class="form-buttons">
                <input type="submit" value="Змінити" class="submit-button" />

                <button type="button" id="cancel-button">
                    Скасувати
                </button>
            </div>

        </form>
    </div>

    <?php

    $messages = [
        "unknown_error" => "Дані не було змінено. Сталася невідома помилка.",
        "empty_suppliers_name" => "Введіть назву постачальника.",
        "incorrect_phone_number_format" => "Некоректний формат номера телефону.",
        "supplier_exists" => "Постачальник з таким номером телефону вже існує.",
        "too_large_file" => "Файл завеликий. Максимальний розмір — 10 МБ.",
        "success" => "Дані було успішно змінено."
    ];

    if (isset($messages[$error])):

        ?>

        <script>
            alert(<?= json_encode($messages[$error]) ?>);

            window.history.replaceState(
                {},
                document.title,
                window.location.pathname
            );
        </script>

    <?php endif; ?>


    <script>
        const cancelButton = document.getElementById("cancel-button");
        const marsLogo = document.getElementById("header-logo");

        marsLogo.addEventListener("click", () => {
            window.location.href = "main.php";
        });

        cancelButton.addEventListener("click", () => {
            window.location.href = "suppliers.php";
        });


        // =========================
        // CUSTOM FIELDS
        // =========================

        const customFieldsContainer =
            document.getElementById("custom-fields-container");

        const addCustomFieldButton =
            document.getElementById("add-custom-field");


        addCustomFieldButton.addEventListener("click", () => {

            const fieldRow = document.createElement("div");

            fieldRow.classList.add("custom-field-row");

            fieldRow.innerHTML = `
            <div class="custom-field-cols-container">
                <div class="custom-field-cols-element"><p class="form-element">Параметр</p><input type="text" name="customFieldName[]" class="text-input custom-field-name" placeholder="Назва поля" /></div>
                <div class="custom-field-cols-element"><p class="form-element">Значення</p><input type="text" name="customFieldValue[]" class="text-input custom-field-value" placeholder="Значення" /></div>
                <div class="custom-field-cols-element"><button type="button" class="remove-custom-field" aria-label="Видалити поле">×</button></div>
            </div>`;

            customFieldsContainer.appendChild(fieldRow);
        });


        customFieldsContainer.addEventListener("click", (event) => {

            if (event.target.classList.contains("remove-custom-field")) {

                event.target.closest(".custom-field-row").remove();

            }

        });


        // =========================
        // SUPPLIER PHOTO
        // =========================

        const uploadArea =
            document.getElementById("supplierPhotoUpload");

        const fileInput =
            document.getElementById("supplierPhoto");

        const uploadContent =
            document.getElementById("uploadContent");

        const photoPreview =
            document.getElementById("photoPreview");


        uploadArea.addEventListener("click", () => {
            fileInput.click();
        });


        fileInput.addEventListener("change", () => {

            if (fileInput.files.length > 0) {
                showPreview(fileInput.files[0]);
            }

        });


        uploadArea.addEventListener("dragover", (event) => {

            event.preventDefault();

            uploadArea.classList.add("dragover");

        });


        uploadArea.addEventListener("dragleave", () => {

            uploadArea.classList.remove("dragover");

        });


        uploadArea.addEventListener("drop", (event) => {

            event.preventDefault();

            uploadArea.classList.remove("dragover");

            if (event.dataTransfer.files.length > 0) {

                fileInput.files = event.dataTransfer.files;

                showPreview(event.dataTransfer.files[0]);

            }

        });


        function showPreview(file) {

            if (!file.type.startsWith("image/")) {

                alert("Будь ласка, виберіть файл зображення.");

                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {

                photoPreview.src = event.target.result;

                uploadContent.style.display = "none";
                photoPreview.style.display = "block";

            };

            reader.readAsDataURL(file);

        }
    </script>
</body>

</html>
