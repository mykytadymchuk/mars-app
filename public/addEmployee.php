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
$roles = $rolesCollection->find()->toArray();
$defaultRoles = ["Менеджер", "Дизайнер", "Бухгалтер", "Друкар", "Макетник"];
if (empty($roles)) {
  $roles = array_map(fn($role) => ["Назва" => $role], $defaultRoles);
}
$messages = ["empty_name" => "Введіть прізвище та ім'я працівника.", "invalid_role" => "Оберіть роль працівника.", "incorrect_phone_number_format" => "Некоректний формат номера телефону.", "employee_exists" => "Працівник з таким номером телефону вже існує.", "unknown_error" => "Працівника не було додано. Сталася невідома помилка."];
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
  <title>Додавання працівника</title>
</head>

<body>
  <div class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
    <div class="header-right-corner">
      <p id="main-greetings">Вітаю, <?= htmlspecialchars($_SESSION["username"]) ?>!</p>
    </div>
  </div>
  <div class="main-body">
    <p class="page-title">ДОДАВАННЯ ПРАЦІВНИКА</p>
    <form action="services/addEmployeeProcess.php" method="post">
      <div class="form-row"><label class="form-element" for="employeeLastName">Прізвище</label><input type="text"
          name="employeeLastName" class="text-input" id="employeeLastName" required /></div>
      <div class="form-row"><label class="form-element" for="employeeFirstName">Ім'я</label><input type="text"
          name="employeeFirstName" class="text-input" id="employeeFirstName" required /></div>
      <div class="form-row"><label class="form-element" for="employeeRole">Роль</label><select name="employeeRole"
          class="text-input" id="employeeRole" required>
          <option value="" selected disabled>Оберіть роль</option><?php foreach ($roles as $role): ?>
            <option value="<?= htmlspecialchars($role["Назва"]) ?>"><?= htmlspecialchars($role["Назва"]) ?></option>

          <?php endforeach; ?>
        </select></div>
      <div class="form-row"><label class="form-element" for="employeePhone">Номер телефону</label><input type="tel"
          name="employeePhone" class="text-input" id="employeePhone" placeholder="+380XXXXXXXXX" pattern="\+380\d{9}"
          required /></div>
      <div class="form-buttons"><input type="submit" value="Додати" class="submit-button" /><button type="button"
          id="cancel-button">Скасувати</button></div>
    </form>
  </div>
  <?php if (isset($messages[$error])): ?>
    <script>alert(<?= json_encode($messages[$error]) ?>); window.history.replaceState({}, document.title, window.location.pathname);</script>
  <?php endif; ?>
  <script>document.getElementById("header-logo").addEventListener("click", () => window.location.href = "main.php"); document.getElementById("cancel-button").addEventListener("click", () => window.location.href = "employees.php");</script>
</body>

</html>