<?php

session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
  header("Location: login.php");
  exit;
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
  <link rel="stylesheet" href="styles/main-style.css" />
  <link rel="stylesheet" href="styles/general-style.css" />
  <link rel="stylesheet" href="styles/main-header-style.css" />
  <title>Головна сторінка</title>
</head>

<body>
  <div class="main-header">
    <img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
    <div class="header-right-corner">
      <p id="main-greetings">Вітаю, <?= htmlspecialchars($_SESSION["username"]) ?>!</p>
      <p id="main-edit-account">Редагувати акаунт</p>
      <p id="logout-button">Вийти</p>
    </div>
  </div>
  <div class="main-body">
    <div class="main-function" id="orders-button">
      <img src="src/orders.png" alt="orders" draggable="false" />
      <p>Замовлення</p>
    </div>
    <div class="main-function" id="employees-button">
      <img src="src/employees.png" alt="employees" draggable="false" />
      <p>Персонал</p>
    </div>
    <div class="main-function" id="stock-button">
      <img src="src/stock.png" alt="stock" draggable="false" />
      <p>Склад</p>
    </div>
    <div class="main-function" id="incomes-button">
      <img src="src/incomes.png" alt="incomes" draggable="false" />
      <p>Надходження</p>
    </div>
    <div class="main-function" id="chat-button">
      <img src="src/chat.png" alt="chat" draggable="false" />
      <p>Чат</p>
    </div>
    <div class="main-function" id="files-button">
      <img src="src/files.png" alt="files" draggable="false" />
      <p>Файли</p>
    </div>
    <div class="main-function" id="materials-button">
      <img src="src/materials.png" alt="materials" draggable="false" />
      <p>Матеріали</p>
    </div>
    <div class="main-function" id="suppliers-button">
      <img src="src/suppliers.png" alt="suppliers" draggable="false" />
      <p>Постачальники</p>
    </div>
  </div>

  <script>
    const marsLogo = document.getElementById("header-logo");
    const editAccount = document.getElementById("main-edit-account");
    const logoutButton = document.getElementById("logout-button");

    const ordersButton = document.getElementById("orders-button");
    const employeesButton = document.getElementById("employees-button");
    const stockButton = document.getElementById("stock-button");
    const incomesButton = document.getElementById("incomes-button");
    const filesButton = document.getElementById("files-button");
    const chatButton = document.getElementById("chat-button");
    const materialsButton = document.getElementById("materials-button");
    const suppliersButton = document.getElementById("suppliers-button");

    marsLogo.addEventListener("click", () => {
      window.location.href = "main.php";
    });

    editAccount.addEventListener("click", () => {
      window.location.href = "editAccount.php";
    });

    logoutButton.addEventListener("click", () => {
      window.location.href = "services/logoutProcess.php";
    });

    ordersButton.addEventListener("click", () => {
      window.location.href = "orders.php";
    });

    employeesButton.addEventListener("click", () => {
      window.location.href = "employees.php";
    });

    stockButton.addEventListener("click", () => {
      window.location.href = "stock.php";
    });

    incomesButton.addEventListener("click", () => {
      window.location.href = "incomes.php";
    });

    filesButton.addEventListener("click", () => {
      window.location.href = "files.php";
    });

    chatButton.addEventListener("click", () => {
      window.location.href = "chat.php";
    });

    materialsButton.addEventListener("click", () => {
      window.location.href = "materials.php";
    });

    suppliersButton.addEventListener("click", () => {
      window.location.href = "suppliers.php";
    });
  </script>
</body>

</html>