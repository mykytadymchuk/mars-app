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
    <title>Додавання постачальника</title>
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
        <p class="page-title">ДОДАВАННЯ ПОСТАЧАЛЬНИКА</p>

        <form action="services/addSupplierProcess.php" method="post">
            <div class="form-row">
                <label class="form-element" for="password">Назва постачальника</label>
                <input type="text" name="suppliersName" class="text-input" id="suppliersName" />
            </div>
            <div class="form-row">
                <label class="form-element" for="suppliersPhone">Номер телефону</label>
                <input type="tel" class="text-input" name="suppliersPhone" id="suppliersPhone"
                    placeholder="+380ХХХХХХХХХ" pattern="\+380\d{9}" required>
            </div>

            <div class="form-buttons">
                <input type="submit" value="Додати" class="submit-button" />
                <button type="button" id="cancel-button">Скасувати</button>
            </div>
        </form>
    </div>

    <?php

    $messages = [
        "unknown_error" => "Постачальника не було додано. Сталася невідома помилка.",
        "empty_suppliers_name" => "Введіть назву постачальника.",
        "incorrect_phone_number_format" => "Некоректний формат номера телефону.",
        "supplier_exists" => "Постачальник з таким номером телефону вже існує.",
        "success" => "Постачальника було успішно додано."
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

        marsLogo.addEventListener("click", () => { window.location.href = "main.php"; });
        cancelButton.addEventListener("click", () => { window.location.href = "suppliers.php"; });

    </script>
  <script src="scripts/chat-notifications.js" defer></script>
</body>

</html>