<?php
session_start();
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
  header("Location: login.php");
  exit;
}
require_once __DIR__ . '/../services/databaseConnect.php';
$employeesCollection->updateMany(
  ["\xD0\xA1\xD1\x82\xD0\xB0\xD1\x82\xD1\x83\xD1\x81" => ['$exists' => false]],
  ['$set' => ["\xD0\xA1\xD1\x82\xD0\xB0\xD1\x82\xD1\x83\xD1\x81" => "\xD0\x9F\xD1\x80\xD0\xB0\xD1\x86\xD1\x8E\xD1\x94"]]
);
$user = $usersCollection->findOne(["Логін" => $_SESSION["username"]]);
if (!$user) {
  session_destroy();
  header("Location: login.php");
  exit;
}
$searchableFields = ["Прізвище", "Ім'я", "Роль", "Номер телефону"];
$searchField = $_GET["field"] ?? "";
$searchValue = trim($_GET["search"] ?? "");
$query = [];
if (in_array($searchField, $searchableFields, true) && $searchValue !== "") {
  $query[$searchField] = ['$regex' => preg_quote($searchValue, '/'), '$options' => 'i'];
}
$employees = $employeesCollection->find($query)->toArray();
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
  <title>Працівники</title>
</head>

<body>
  <form method="get">
    <div class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
      <div class="search-bar-container"><select name="field"
          class="search-select"><?php foreach ($searchableFields as $fieldName): ?>
            <option value="<?= htmlspecialchars($fieldName) ?>" <?= $searchField === $fieldName ? "selected" : "" ?>>
              <?= htmlspecialchars($fieldName) ?></option><?php endforeach; ?>
        </select><input name="search" class="search-bar" placeholder="Пошук"
          value="<?= htmlspecialchars($searchValue) ?>" /><button class="search-button"><img src="src/search.png"
            alt="search-button" /></button></div>
      <div class="add-new-item" id="add-new-employee">+</div>
      <div class="header-right-corner">
        <p id="main-greetings">Вітаю, <?= htmlspecialchars($_SESSION["username"]) ?>!</p>
      </div>
    </div>
  </form>
  <div class="main-body"><?php if (empty($employees)): ?>
      <p class="none-message">Не було знайдено жодного працівника!</p>
    <?php endif; ?><?php foreach ($employees as $employee): ?>
      <?php
      $lastName = $employee["Прізвище"] ?? "";
      $firstName = $employee["Ім'я"] ?? "";
      $role = $employee["Роль"] ?? "";
      $phone = $employee["Номер телефону"] ?? "";
      $hireDate = $employee["Дата прийняття на роботу"] ?? "";
      $formattedPhone = preg_match('/^\+380\d{9}$/', $phone) ? sprintf("+38 (%s) %s-%s-%s", substr($phone, 3, 3), substr($phone, 6, 3), substr($phone, 9, 2), substr($phone, 11, 2)) : $phone;
      $photo = !empty($employee["Фото"]) ? "src/employees/" . rawurlencode($employee["Фото"]) : "src/no-profile-photo.png";
      $reserved = ["_id", "Прізвище", "Ім'я", "Роль", "Номер телефону", "Дата прийняття на роботу", "Фото", "Логін", "Перший пароль"];
      $custom = [];
      $statusField = "\xD0\xA1\xD1\x82\xD0\xB0\xD1\x82\xD1\x83\xD1\x81";
      $dismissedStatus = "\xD0\x97\xD0\xB2\xD1\x96\xD0\xBB\xD1\x8C\xD0\xBD\xD0\xB5\xD0\xBD\xD0\xBE";
      $status = $employee[$statusField] ?? "\xD0\x9F\xD1\x80\xD0\xB0\xD1\x86\xD1\x8E\xD1\x94";
      $employeeUsername = $employee["\xD0\x9B\xD0\xBE\xD0\xB3\xD1\x96\xD0\xBD"] ?? "";
      $firstPassword = $employee["\xD0\x9F\xD0\xB5\xD1\x80\xD1\x88\xD0\xB8\xD0\xB9 \xD0\xBF\xD0\xB0\xD1\x80\xD0\xBE\xD0\xBB\xD1\x8C"] ?? "";
      foreach ($employee as $key => $value) {
        if (!in_array($key, $reserved, true) && $key !== $statusField)
          $custom[$key] = $value;
      }
      ?>
      <div class="flex-element"><img src="<?= htmlspecialchars($photo) ?>" alt="employee" class="employee-photo"
          draggable="false" />
        <div class="flex-element-main-info-container">
          <p class="flex-element-name"><?= htmlspecialchars("$lastName $firstName") ?></p>
          <p class="employee-role"><?= htmlspecialchars($role) ?></p>
        </div>
        <div class="flex-element-info-container">
          <p><span class="row-title">Номер телефону:</span> <?= htmlspecialchars($formattedPhone) ?></p>
          <hr />
          <p><span class="row-title">Дата прийняття на роботу:</span> <?= htmlspecialchars($hireDate) ?></p>
          <p><span class="row-title">Стаж:</span> <span class="employee-tenure"
              data-hire-date="<?= htmlspecialchars($hireDate) ?>">—</span></p>
          <p><span class="row-title">Статус:</span> <span class="employee-status <?= $status === $dismissedStatus ? "dismissed" : "working" ?>"><?= htmlspecialchars($status) ?></span></p>
          <?php foreach ($custom as $key => $value): ?>    <?php if (is_scalar($value) || $value instanceof Stringable): ?>
              <p><span class="row-title"><?= htmlspecialchars((string) $key) ?>:</span> <?= htmlspecialchars((string) $value) ?>
              </p><?php endif; ?><?php endforeach; ?>
          <hr />
          <?php if ($employeeUsername !== '' || $firstPassword !== ''): ?>
            <p><span class="row-title">Логін:</span> <?= htmlspecialchars($employeeUsername) ?></p>
            <p><span class="row-title">Перший пароль:</span> <?= htmlspecialchars($firstPassword) ?></p>
          <?php endif; ?>
        </div>
        <div class="flex-element-edit-container">
          <?php if ($status !== $dismissedStatus): ?>
            <p class="flex-element-edit" data-id="<?= $employee['_id'] ?>">Редагувати</p>
          <?php else: ?>
            <form action="services/restoreEmployeeProcess.php" method="post" class="restore-employee-form">
              <input type="hidden" name="id" value="<?= htmlspecialchars((string) $employee['_id']) ?>" />
              <button type="submit" class="flex-element-edit restore-employee-button">Відновити</button>
            </form>
          <?php endif; ?>
        </div>
      </div><?php endforeach; ?>
  </div>
  <script>
    document.getElementById("header-logo").onclick = () => location.href = "main.php"; document.getElementById("add-new-employee").onclick = () => location.href = "addEmployee.php"; document.querySelectorAll(".flex-element-edit[data-id]").forEach(e => e.onclick = () => location.href = `editEmployee.php?id=${e.dataset.id}`);
    function tenure(date) { const start = new Date(`${date}T00:00:00`), today = new Date(); if (Number.isNaN(start.getTime()) || start > today) return "—"; let y = today.getFullYear() - start.getFullYear(), m = today.getMonth() - start.getMonth(), d = today.getDate() - start.getDate(); if (d < 0) { m--; d += new Date(today.getFullYear(), today.getMonth(), 0).getDate(); } if (m < 0) { y--; m += 12; } return `${y} р., ${m} міс., ${d} дн.`; } document.querySelectorAll(".employee-tenure").forEach(e => e.textContent = tenure(e.dataset.hireDate));
  </script>
</body>

</html>
