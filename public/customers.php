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
$searchableFields = ["Прізвище", "Імʼя", "По-батькові", "Назва компанії", "Номер телефону"];
$searchField = $_GET["field"] ?? "";
$searchValue = trim($_GET["search"] ?? "");
$query = [];
if (in_array($searchField, $searchableFields, true) && $searchValue !== "") {
  $query[$searchField] = ['$regex' => preg_quote($searchValue, '/'), '$options' => 'i'];
}
$customers = $customersCollection->find($query)->toArray();
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" />
  <link rel="stylesheet" href="styles/general-style.css" />
  <link rel="stylesheet" href="styles/flex-grid-page-style.css" />
  <link rel="stylesheet" href="styles/search-bar-style.css" />
  <link rel="stylesheet" href="styles/page-header-style.css" />
  <link rel="stylesheet" href="styles/employees-style.css" />
  <title>Клієнти</title>
  <link rel="stylesheet" href="styles/responsive.css" />
</head>
<body>
  <form method="get">
    <div class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
      <div class="search-bar-container"><select name="field" class="search-select">
        <?php foreach ($searchableFields as $fieldName): ?>
          <option value="<?= htmlspecialchars($fieldName) ?>" <?= $searchField === $fieldName ? "selected" : "" ?>><?= htmlspecialchars($fieldName) ?></option>
        <?php endforeach; ?>
      </select><input name="search" class="search-bar" placeholder="Пошук" value="<?= htmlspecialchars($searchValue) ?>" /><button class="search-button"><img src="src/search.png" alt="search-button" /></button></div>
      <div class="add-new-item" id="add-new-customer">+</div>
      <div class="header-right-corner"><p id="main-greetings">Вітаю, <?= htmlspecialchars($_SESSION["username"]) ?>!</p></div>
    </div>
  </form>
  <div class="main-body">
    <?php if (empty($customers)): ?><p class="none-message">Не було знайдено жодного клієнта!</p><?php endif; ?>
    <?php foreach ($customers as $customer): ?>
      <?php
      $name = $customer["Назва компанії"] ?? trim(($customer["Прізвище"] ?? "") . " " . ($customer["Імʼя"] ?? "") . " " . ($customer["По-батькові"] ?? ""));
      $phone = $customer["Номер телефону"] ?? "";
      $formattedPhone = preg_match('/^\+380\d{9}$/', $phone) ? sprintf("+38 (%s) %s-%s-%s", substr($phone, 3, 3), substr($phone, 6, 3), substr($phone, 9, 2), substr($phone, 11, 2)) : $phone;
      $photo = !empty($customer["Фото"]) ? "src/customers/" . rawurlencode($customer["Фото"]) : "src/no-profile-photo.png";
      $reserved = ["_id", "Тип клієнта", "Прізвище", "Імʼя", "По-батькові", "Назва компанії", "Номер телефону", "Фото"];
      ?>
      <div class="flex-element customer-card">
        <img src="<?= htmlspecialchars($photo) ?>" alt="customer" class="employee-photo" draggable="false" />
        <div class="flex-element-main-info-container"><p class="flex-element-name"><?= htmlspecialchars($name) ?></p><p class="employee-role"><?= ($customer["Тип клієнта"] ?? "") === "legal" ? "Юридична особа" : "Фізична особа" ?></p></div>
        <div class="flex-element-info-container">
       <hr>
          
          <p><span class="row-title">Номер телефону:</span> <?= htmlspecialchars($formattedPhone) ?></p>
          <?php foreach ($customer as $key => $value): ?>
            <?php if (!in_array($key, $reserved, true) && (is_scalar($value) || $value instanceof Stringable)): ?>
              <p><span class="row-title"><?= htmlspecialchars((string) $key) ?>:</span> <?= htmlspecialchars((string) $value) ?></p>
            <?php endif; ?>
          <?php endforeach; ?>
        <hr>

        </div>
        <div class="flex-element-edit-container"><p class="flex-element-edit" data-id="<?= htmlspecialchars((string) $customer['_id']) ?>">Редагувати</p></div>
      </div>
    <?php endforeach; ?>
  </div>
  <script>
    document.getElementById("header-logo").onclick = () => location.href = "main.php";
    document.getElementById("add-new-customer").onclick = () => location.href = "addCustomer.php";
    document.querySelectorAll(".flex-element-edit[data-id]").forEach(button => button.onclick = () => location.href = `editCustomer.php?id=${encodeURIComponent(button.dataset.id)}`);
  </script>
  <script src="scripts/chat-notifications.js" defer></script>
</body>
</html>
