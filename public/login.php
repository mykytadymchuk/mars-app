<?php

session_start();

if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
  header("Location: main.php");
  exit;
}

$error = $_GET["error"] ?? null;
?>

<!doctype html>
<html lang="uk">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Авторизація</title>
  <link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="styles/form-style.css" />
  <link rel="stylesheet" href="styles/general-style.css" />
</head>

<body>
  <div class="general-container">
    <img src="src/logo.jpg" alt="mars-logo" class="logo" />
    <div class="main-body">
      <p class="page-title">УВІЙДІТЬ В АКАУНТ</p>
      <form action="services/loginProcess.php" method="post">
        <div class="form-row">
          <label class="form-element" for="username">Логін</label>
          <input type="text" name="username" class="text-input" id="username" />
        </div>
        <div class="form-row">
          <label class="form-element" for="password">Пароль</label>
          <div class="password-wrapped">
            <input type="password" name="password" class="text-input" id="password" />
            <button type="button" id="eye-button" class="eye-button">
              <img src="src/closed-eye.png" alt="" id="eye-icon" />
            </button>
          </div>
        </div>
        <div class="form-buttons">
          <input type="submit" value="Увійти" class="submit-button" />
        </div>
      </form>
    </div>
  </div>

  <?php

  $messages = [
    "invalid_credentials" => "Неправильний логін або пароль."
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
    const passwordInput = document.getElementById("password");
    const eyeButton = document.getElementById("eye-button");
    const eyeIcon = document.getElementById("eye-icon");
    eyeButton.addEventListener("mousedown", () => { passwordInput.type = "text"; eyeIcon.src = "src/opened-eye.png"; });
    eyeButton.addEventListener("mouseup", () => { passwordInput.type = "password"; eyeIcon.src = "src/closed-eye.png"; });
    eyeButton.addEventListener("mouseleave", () => { passwordInput.type = "password"; eyeIcon.src = "src/closed-eye.png"; });
    eyeButton.addEventListener("touchstart", (event) => { event.preventDefault(); passwordInput.type = "text"; });
    eyeButton.addEventListener("touchend", (event) => { event.preventDefault(); passwordInput.type = "password"; });
  </script>


</body>

</html>