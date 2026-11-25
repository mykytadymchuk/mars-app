<?php session_start();
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
$currentUsername = $user["Логін"];
$error = $_GET["error"] ?? null;
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
  <title>Редагування акаунту</title>
  <link rel="stylesheet" href="styles/responsive.css" />
</head>

<body>
  <div class="main-header">
    <img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
    <div class="header-right-corner">
      <p id="main-greetings">Вітаю, <?= htmlspecialchars($_SESSION["username"]) ?>!</p>
    </div>
  </div>
  <div class="main-body">
    <p class="page-title">РЕДАГУВАННЯ АКАУНТУ</p>

    <form action="services/editAccountProcess.php" method="post">
      <div class="form-row">
        <label class="form-element" for="password">Новий пароль</label>
        <div class="password-wrapped">
          <input type="password" name="password" class="text-input" id="password" minlength="4" />
          <button type="button" id="eye-button" class="eye-button">
            <img src="src/closed-eye.png" alt="" id="eye-icon" />
          </button>
        </div>
      </div>
      <div class="form-buttons">
        <input type="submit" value="Зберегти" class="submit-button" />
        <button type="button" id="cancel-button">Скасувати</button>
      </div>
    </form>
  </div>

  <?php

  $messages = [
    "unknown_error" => "Дані не було змінено. Сталася невідома помилка.",
    "empty_password" => "Пароль не може бути порожнім.",
    "success" => "Пароль було успішно змінено."
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
    const passwordInput = document.getElementById("password");
    const eyeButton = document.getElementById("eye-button");
    const eyeIcon = document.getElementById("eye-icon");
    marsLogo.addEventListener("click", () => { window.location.href = "main.php"; });
    cancelButton.addEventListener("click", () => { window.location.href = "main.php"; });
    eyeButton.addEventListener("mousedown", () => { passwordInput.type = "text"; eyeIcon.src = "src/opened-eye.png"; });
    eyeButton.addEventListener("mouseup", () => { passwordInput.type = "password"; eyeIcon.src = "src/closed-eye.png"; });
    eyeButton.addEventListener("mouseleave", () => { passwordInput.type = "password"; eyeIcon.src = "src/closed-eye.png"; });
    eyeButton.addEventListener("touchstart", (event) => { event.preventDefault(); passwordInput.type = "text"; });
    eyeButton.addEventListener("touchend", (event) => { event.preventDefault(); passwordInput.type = "password"; });
  </script>
  <script src="scripts/chat-notifications.js" defer></script>
</body>

</html>