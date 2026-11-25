<?php
session_start();
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
  header("Location: login.php");
  exit;
}
require_once __DIR__ . '/../services/databaseConnect.php';
$user = $usersCollection->findOne(["Логін" => $_SESSION["username"]]);
if (!$user) {
  session_destroy();
  header("Location: login.php");
  exit;
}
$error = $_GET["error"] ?? null;
$messages = [
  "empty_name" => "Заповніть обов'язкові поля клієнта.",
  "incorrect_phone_number_format" => "Некоректний формат номера телефону.",
  "customer_exists" => "Клієнт з таким номером телефону вже існує.",
  "unknown_error" => "Клієнта не було додано. Сталася невідома помилка."
];
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles/form-style.css" />
  <link rel="stylesheet" href="styles/general-style.css" />
  <title>Додавання клієнта</title>
  <link rel="stylesheet" href="styles/responsive.css" />
</head>
<body>
  <div class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
    <div class="header-right-corner"><p id="main-greetings">Вітаю, <?= htmlspecialchars($_SESSION["username"]) ?>!</p></div>
  </div>
  <div class="main-body">
    <p class="page-title">ДОДАВАННЯ КЛІЄНТА</p>
    <form action="services/addCustomerProcess.php" method="post" enctype="multipart/form-data">
      <div class="form-row"><label class="form-element">Тип клієнта</label><div class="radio-options">
        <label><input type="radio" name="customerType" value="individual" checked />Фізична особа</label>
        <label><input type="radio" name="customerType" value="legal" />Юридична особа</label>
      </div></div>
      <div id="individual-fields">
        <div class="form-row"><label class="form-element" for="customerLastName">Прізвище</label><input type="text" name="customerLastName" class="text-input" id="customerLastName" required /></div>
        <div class="form-row"><label class="form-element" for="customerFirstName">Імʼя</label><input type="text" name="customerFirstName" class="text-input" id="customerFirstName" required /></div>
        <div class="form-row"><label class="form-element" for="customerMiddleName">По-батькові</label><input type="text" name="customerMiddleName" class="text-input" id="customerMiddleName" required /></div>
      </div>
      <div id="legal-fields" hidden>
        <div class="form-row"><label class="form-element" for="customerCompanyName">Назва компанії</label><input type="text" name="customerCompanyName" class="text-input" id="customerCompanyName" disabled /></div>
      </div>
      <div class="form-row"><label class="form-element" for="customerPhone">Номер телефону</label><input type="tel" name="customerPhone" class="text-input" id="customerPhone" placeholder="+380XXXXXXXXX" pattern="\+380\d{9}" required /></div>
      <div class="form-row"><label class="form-element" for="customerPhoto">Фото</label>
        <div class="supplier-photo-upload" id="customerPhotoUpload">
          <input type="file" name="customerPhoto" id="customerPhoto" accept="image/png,image/jpeg,image/webp" hidden />
          <div class="supplier-photo-upload-content" id="uploadContent"><p class="upload-title">Натисніть або перетягніть фото сюди</p><p class="upload-subtitle">PNG, JPG або WEBP</p></div>
          <img class="supplier-photo-preview" id="photoPreview" alt="Попередній перегляд фото" />
        </div>
      </div>
      <div class="form-buttons"><input type="submit" value="Додати" class="submit-button" /><button type="button" id="cancel-button">Скасувати</button></div>
    </form>
  </div>
  <?php if (isset($messages[$error])): ?><script>alert(<?= json_encode($messages[$error]) ?>); window.history.replaceState({}, document.title, window.location.pathname);</script><?php endif; ?>
  <script>
    document.getElementById("header-logo").addEventListener("click", () => window.location.href = "main.php");
    document.getElementById("cancel-button").addEventListener("click", () => window.location.href = "customers.php");
    const customerTypeInputs = document.querySelectorAll('input[name="customerType"]');
    const individualFields = document.getElementById("individual-fields");
    const legalFields = document.getElementById("legal-fields");
    function updateCustomerFields() {
      const isIndividual = document.querySelector('input[name="customerType"]:checked').value === "individual";
      individualFields.hidden = !isIndividual;
      legalFields.hidden = isIndividual;
      individualFields.querySelectorAll("input").forEach(input => { input.disabled = !isIndividual; input.required = isIndividual; });
      legalFields.querySelectorAll("input").forEach(input => { input.disabled = isIndividual; input.required = !isIndividual; });
    }
    customerTypeInputs.forEach(input => input.addEventListener("change", updateCustomerFields));
    updateCustomerFields();
    const uploadArea = document.getElementById("customerPhotoUpload"), photoInput = document.getElementById("customerPhoto"), photoPreview = document.getElementById("photoPreview"), uploadContent = document.getElementById("uploadContent");
    uploadArea.onclick = () => photoInput.click();
    photoInput.onchange = () => showPhoto(photoInput.files[0]);
    uploadArea.ondragover = event => { event.preventDefault(); uploadArea.classList.add("dragover"); };
    uploadArea.ondragleave = () => uploadArea.classList.remove("dragover");
    uploadArea.ondrop = event => { event.preventDefault(); uploadArea.classList.remove("dragover"); photoInput.files = event.dataTransfer.files; showPhoto(photoInput.files[0]); };
    function showPhoto(file) {
      if (!file || !file.type.startsWith("image/")) return;
      const reader = new FileReader();
      reader.onload = event => { photoPreview.src = event.target.result; photoPreview.style.display = "block"; uploadContent.style.display = "none"; };
      reader.readAsDataURL(file);
    }
  </script>
  <script src="scripts/chat-notifications.js" defer></script>
</body>
</html>
