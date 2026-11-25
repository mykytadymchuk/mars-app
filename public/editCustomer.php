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
$id = $_GET["id"] ?? "";
try {
    $customerId = new ObjectId($id);
} catch (Throwable $e) {
    header("Location: customers.php");
    exit;
}
$customer = $customersCollection->findOne(["_id" => $customerId]);
if (!$customer) {
    header("Location: customers.php");
    exit;
}
$type = $customer["Тип клієнта"] ?? (isset($customer["Назва компанії"]) ? "legal" : "individual");
$error = $_GET["error"] ?? null;
$messages = [
    "empty_name" => "Заповніть обов'язкові поля клієнта.",
    "incorrect_phone_number_format" => "Некоректний формат номера телефону.",
    "customer_exists" => "Клієнт з таким номером телефону вже існує.",
    "too_large_file" => "Файл завеликий. Максимальний розмір — 20 МБ.",
    "unknown_error" => "Дані клієнта не було змінено."
];
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" />
  <link rel="stylesheet" href="styles/form-style.css" />
  <link rel="stylesheet" href="styles/general-style.css" />
  <title>Редагування клієнта</title>
  <link rel="stylesheet" href="styles/responsive.css" />
</head>
<body>
  <div class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
    <div class="header-right-corner"><p id="main-greetings">Вітаю, <?= htmlspecialchars($_SESSION["username"]) ?>!</p></div>
  </div>
  <div class="main-body">
    <p class="page-title">РЕДАГУВАННЯ КЛІЄНТА</p>
    <form action="services/editCustomerProcess.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>" />
      <div class="form-row"><label class="form-element">Тип клієнта</label><div class="radio-options">
        <label><input type="radio" name="customerType" value="individual" <?= $type === "individual" ? "checked" : "" ?> />Фізична особа</label>
        <label><input type="radio" name="customerType" value="legal" <?= $type === "legal" ? "checked" : "" ?> />Юридична особа</label>
      </div></div>
      <div id="individual-fields">
        <div class="form-row"><label class="form-element" for="customerLastName">Прізвище</label><input type="text" name="customerLastName" class="text-input" id="customerLastName" value="<?= htmlspecialchars($customer["Прізвище"] ?? "") ?>" /></div>
        <div class="form-row"><label class="form-element" for="customerFirstName">Імʼя</label><input type="text" name="customerFirstName" class="text-input" id="customerFirstName" value="<?= htmlspecialchars($customer["Імʼя"] ?? "") ?>" /></div>
        <div class="form-row"><label class="form-element" for="customerMiddleName">По-батькові</label><input type="text" name="customerMiddleName" class="text-input" id="customerMiddleName" value="<?= htmlspecialchars($customer["По-батькові"] ?? "") ?>" /></div>
      </div>
      <div id="legal-fields" hidden><div class="form-row"><label class="form-element" for="customerCompanyName">Назва компанії</label><input type="text" name="customerCompanyName" class="text-input" id="customerCompanyName" value="<?= htmlspecialchars($customer["Назва компанії"] ?? "") ?>" /></div></div>
      <div class="form-row"><label class="form-element" for="customerPhone">Номер телефону</label><input type="tel" name="customerPhone" class="text-input" id="customerPhone" value="<?= htmlspecialchars($customer["Номер телефону"] ?? "") ?>" placeholder="+380XXXXXXXXX" pattern="\+380\d{9}" required /></div>
      <div class="form-row"><label class="form-element" for="customerPhoto">Фото клієнта</label>
        <div class="supplier-photo-upload" id="customerPhotoUpload"><input type="file" name="customerPhoto" id="customerPhoto" accept="image/png,image/jpeg,image/webp" hidden />
          <div class="supplier-photo-upload-content" id="uploadContent"><p class="upload-title">Перетягніть фото сюди</p><p class="upload-subtitle">або натисніть, щоб вибрати файл</p><p class="upload-formats">PNG, JPG або WEBP</p></div>
          <img id="photoPreview" class="supplier-photo-preview" alt="Попередній перегляд" <?= !empty($customer["Фото"]) ? 'src="src/customers/' . htmlspecialchars(rawurlencode($customer["Фото"])) . '" style="display:block"' : '' ?> />
        </div>
      </div>
      <div class="form-buttons"><input type="submit" value="Змінити" class="submit-button" /><button type="button" id="cancel-button">Скасувати</button></div>
    </form>
  </div>
  <?php if (isset($messages[$error])): ?><script>alert(<?= json_encode($messages[$error]) ?>); history.replaceState({}, document.title, location.pathname + '?id=' + encodeURIComponent(<?= json_encode($id) ?>));</script><?php endif; ?>
  <script>
    const typeInputs = document.querySelectorAll('input[name="customerType"]'), individualFields = document.getElementById("individual-fields"), legalFields = document.getElementById("legal-fields");
    function updateCustomerFields() {
      const individual = document.querySelector('input[name="customerType"]:checked').value === "individual";
      individualFields.hidden = !individual; legalFields.hidden = individual;
      individualFields.querySelectorAll("input").forEach(input => { input.disabled = !individual; input.required = individual; });
      legalFields.querySelectorAll("input").forEach(input => { input.disabled = individual; input.required = !individual; });
    }
    typeInputs.forEach(input => input.addEventListener("change", updateCustomerFields)); updateCustomerFields();
    const area = document.getElementById("customerPhotoUpload"), file = document.getElementById("customerPhoto"), preview = document.getElementById("photoPreview"), content = document.getElementById("uploadContent");
    area.onclick = () => file.click(); file.onchange = () => show(file.files[0]);
    area.ondragover = event => { event.preventDefault(); area.classList.add("dragover"); };
    area.ondragleave = () => area.classList.remove("dragover");
    area.ondrop = event => { event.preventDefault(); area.classList.remove("dragover"); file.files = event.dataTransfer.files; show(file.files[0]); };
    function show(image) { if (!image || !image.type.startsWith("image/")) return; const reader = new FileReader(); reader.onload = event => { preview.src = event.target.result; preview.style.display = "block"; content.style.display = "none"; }; reader.readAsDataURL(image); }
    if (preview.getAttribute("src")) content.style.display = "none";
    document.getElementById("header-logo").onclick = () => location.href = "main.php";
    document.getElementById("cancel-button").onclick = () => location.href = "customers.php";
  </script>
  <script src="scripts/chat-notifications.js" defer></script>
</body>
</html>
