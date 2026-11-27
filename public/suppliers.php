<?php

session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
  header("Location: login.php");
  exit;
}

require_once __DIR__ . '/../services/databaseConnect.php';

$allSuppliers = $suppliersCollection->find()->toArray();

$fields = [];

foreach ($allSuppliers as $supplier) {

  foreach ($supplier as $fieldName => $value) {

    if ($fieldName === "_id" || $fieldName === "Фото") {
      continue;
    }

    $fields[$fieldName] = true;
  }
}

$searchField = $_GET["field"] ?? "";
$searchValue = trim($_GET["search"] ?? "");

$query = [];

if ($searchField !== "" && $searchValue !== "") {
  if (isset($fields[$searchField])) {

    $query[$searchField] = [
      '$regex' => preg_quote($searchValue, '/'),
      '$options' => 'i'
    ];
  }
}

$suppliers = $suppliersCollection->find($query)->toArray();

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
  <link rel="stylesheet" href="styles/general-style.css" />
  <link rel="stylesheet" href="styles/flex-grid-page-style.css" />
  <link rel="stylesheet" href="styles/search-bar-style.css" />
  <link rel="stylesheet" href="styles/page-header-style.css" />
  <link rel="stylesheet" href="styles/suppliers-style.css" />
  <title>Постачальники</title>
  <link rel="stylesheet" href="styles/responsive.css" />
</head>

<body>
  <form action="" method="get">
    <div class="main-header">
      <img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
      <div class="search-bar-container">
        <select name="field" id="supplier-search-select" class="search-select">

          <?php foreach (array_keys($fields) as $fieldName): ?>

            <option value="<?= htmlspecialchars($fieldName) ?>" <?= $searchField === $fieldName ? "selected" : "" ?>>
              <?= htmlspecialchars($fieldName) ?>
            </option>

          <?php endforeach; ?>

        </select>
        <input type="text" name="search" id="supplier-search" class="search-bar" placeholder="Пошук"
          value="<?= htmlspecialchars($searchValue) ?>" />
        <button class="search-button">
          <img src="src/search.png" alt="search-button" draggable="false" />
        </button>

      </div>
      <div class="add-new-item" id="add-new-supplier">+</div>

      <div class="header-right-corner">
        <p id="main-greetings">Вітаю,
          <?= htmlspecialchars($_SESSION["username"]) ?>!
        </p>
      </div>
    </div>

  </form>


  <div class="main-body">

    <?php
    if (empty($suppliers)) {

      echo '<p class="none-message">Не було знайдено жодного постачальника!</p>';

    } else {

      foreach ($suppliers as $supplier) {

        $name = htmlspecialchars($supplier["Постачальник"] ?? "");
        $phone = $supplier["Номер телефону"] ?? "";
        $systemFieldNames = ["_id", "Постачальник", "Номер телефону", "Фото", "Додаткові поля"];
        $customFields = [];

        // Support both the new top-level fields and records that still use
        // the previous nested "Додаткові поля" structure.
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

        // Format phone number
        $formattedPhone = sprintf(
          "+38 (%s) %s-%s-%s",
          substr($phone, 3, 3),
          substr($phone, 6, 3),
          substr($phone, 9, 2),
          substr($phone, 11, 2)
        );

        // Supplier photo
        if (!empty($supplier["Фото"])) {

          $supplierPhoto = "src/suppliers/" . htmlspecialchars($supplier["Фото"]);

        } else {

          $supplierPhoto = "src/no-profile-photo.png";

        }

        ?>

        <div class="flex-element supplier-income-card" data-incomes-url="supplierIncomes.php?supplierId=<?= rawurlencode((string) $supplier['_id']) ?>" tabindex="0" role="link">

          <img src="<?= $supplierPhoto ?>" alt="supplier" class="supplier-photo" draggable="false" />

          <div class="flex-element-main-info-container">

            <p class="flex-element-name">
              <?= $name ?>
            </p>

          </div>

          <div class="flex-element-info-container">

            <hr />

            <p>
              <span class="row-title">Номер телефону:</span>
              <?= htmlspecialchars($formattedPhone) ?>
            </p>

            <?php foreach ($customFields as $fieldName => $fieldValue): ?>
              <?php if (is_scalar($fieldValue) || $fieldValue instanceof Stringable): ?>
                <p>
                  <span class="row-title"><?= htmlspecialchars((string) $fieldName) ?>:</span>
                  <?= htmlspecialchars((string) $fieldValue) ?>
                </p>
              <?php endif; ?>
            <?php endforeach; ?>

            <hr />

          </div>

          <div class="flex-element-edit-container">

            <p class="flex-element-edit" data-id="<?= $supplier['_id']->__toString() ?>">
              Редагувати
            </p>

          </div>

        </div>

        <?php
      }
    }

    ?>

  </div>

  <script>
    const marsLogo = document.getElementById("header-logo");
    const addButton = document.getElementById("add-new-supplier");
    const editButtons = document.querySelectorAll(".flex-element-edit");

    editButtons.forEach(button => {
      button.addEventListener("click", function () {
        const supplierId = this.dataset.id;
        window.location.href = `editSupplier.php?id=${supplierId}`;
      });
    });
    document.querySelectorAll(".supplier-income-card").forEach(card => { const open = event => { if (event.target.closest(".flex-element-edit-container")) return; window.location.href = card.dataset.incomesUrl; }; card.addEventListener("click", open); card.addEventListener("keydown", event => { if (event.key === "Enter" || event.key === " ") { event.preventDefault(); open(event); } }); });

    marsLogo.addEventListener("click", () => {
      window.location.href = "main.php";
    });

    addButton.addEventListener("click", () => {
      window.location.href = "addSupplier.php";
    });
  </script>
  <script src="scripts/chat-notifications.js" defer></script>
</body>

</html>
