<?php
session_start();
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/../services/databaseConnect.php';
require_once __DIR__ . '/services/stockFunctions.php';
$user = $usersCollection->findOne(["Логін" => $_SESSION["username"]]);
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}
$materials = $materialsCollection->find([], ['sort' => ['name' => 1, 'type' => 1]])->toArray();
$existingIncomes = $incomesCollection->find()->toArray();
foreach ($existingIncomes as $income) {
    if ($stockCollection->countDocuments(['incomeId' => $income['_id']]) === 0) {
        replaceIncomeStock($income, $income['_id'], $materialsCollection, $stockCollection);
    }
}
$stock = $stockCollection->find()->toArray();
$fields = ['name' => 'Матеріал', 'type' => 'Тип'];
foreach ($materials as $material) foreach (($material['parameters'] ?? []) as $parameter) {
    $name = trim((string) ($parameter['name'] ?? ''));
    if ($name !== '') $fields['param:' . $name] = $name;
}
$field = (string) ($_GET['field'] ?? 'name');
if (!isset($fields[$field])) $field = 'name';
$search = trim((string) ($_GET['search'] ?? ''));
$matchesSearch = static function ($material, array $variation) use ($field, $search): bool {
    if ($search === '') return true;
    $haystacks = [];
    if ($field === 'name') $haystacks[] = $material['name'] ?? '';
    elseif ($field === 'type') $haystacks[] = ($material['type'] ?? '') === 'roll' ? 'Рулонний' : 'Листовий';
    else {
        $parameterName = substr($field, 6);
        foreach ($variation as $parameter) if ($parameter['name'] === $parameterName) $haystacks[] = $parameter['value'];
    }
    foreach ($haystacks as $value) {
        if (function_exists('mb_stripos') ? mb_stripos((string) $value, $search, 0, 'UTF-8') !== false : stripos((string) $value, $search) !== false) return true;
    }
    return false;
};
$groups = [];
foreach ($materials as $material) {
    $materialId = $material['_id'];
    foreach (materialVariations($material) as $variation) {
        if (!$matchesSearch($material, $variation)) continue;
        $key = stockVariationKey($materialId, $variation);
        $entities = [];
        foreach ($stock as $entity) if (stockVariationKey($entity['materialId'] ?? '', $entity['parameters'] ?? []) === $key) $entities[] = max(0, min(100, (float) ($entity['percentage'] ?? 0)));
        if (!$entities) $entities = [0];
        if (max($entities) > 50) {
            $status = 'В наявності';
            $color = 'green';
        } elseif (min($entities) <= 20) {
            $status = 'Терміново';
            $color = 'red';
        } else {
            $status = 'До закупки';
            $color = 'orange';
        }
        $materialType = $material['type'] ?? '';
        $materialName = $material['name'] ?? '';
        $groups[$materialType]['materials'][$materialName]['name'] = $materialName;
        $groups[$materialType]['materials'][$materialName]['rows'][] = ['id' => (string) $materialId, 'parameters' => $variation, 'entities' => $entities, 'status' => $status, 'color' => $color];
    }
}
function stockText($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
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
  <link rel="stylesheet" href="styles/general-style.css" />
  <link rel="stylesheet" href="styles/table-page-style.css" />
  <link rel="stylesheet" href="styles/search-bar-style.css" />
  <link rel="stylesheet" href="styles/page-header-style.css" />
  <title>Склад</title>
</head>
<body>
  <form method="get"><div class="main-header">
    <img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
    <div class="search-bar-container"><select name="field" class="search-select">
      <?php foreach ($fields as $value => $label): ?><option value="<?= stockText($value) ?>" <?= $field === $value ? 'selected' : '' ?>><?= stockText($label) ?></option><?php endforeach; ?>
    </select><input type="text" name="search" class="search-bar" placeholder="Пошук" value="<?= stockText($search) ?>" /><button class="search-button"><img src="src/search.png" alt="search-button" draggable="false" /></button></div>
    <div class="header-right-corner"><p id="main-greetings">Вітаю, <?= stockText($_SESSION["username"]) ?>!</p></div>
  </div></form>
  <main class="main-body">
    <?php if (!$groups): ?><p class="none-message">Матеріалів не знайдено.</p><?php endif; ?>
    <?php foreach ($groups as $type => $typeGroup): ?>
      <p class="material-type"><?= $type === 'roll' ? 'Рулонні матеріали' : 'Листові матеріали' ?></p>
      <?php foreach ($typeGroup['materials'] as $group): ?>
        <section class="table">
          <div class="table-header"><?= stockText($group['name']) ?></div><hr />
          <?php foreach ($group['rows'] as $index => $row): ?>
            <?php if ($index > 0): ?><hr /><?php endif; ?>
            <div class="table-element">
              <div class="left-side stock-left-side">
                <p class="material-number"><?= $index + 1 ?></p>
                <p class="material-position"><?= stockText($group['name']) ?></p>
                <?php foreach ($row['parameters'] as $parameter): ?><p class="material-position"><?= stockText($parameter['value']) ?></p><?php endforeach; ?>
                <div class="multiple-params"><?php foreach ($row['entities'] as $percentage): ?><p class="material-position"><?= rtrim(rtrim(number_format($percentage, 2, '.', ''), '0'), '.') ?>%</p><?php endforeach; ?></div>
              </div>
              <div class="mid-side"><p>Статус: <span class="stock-status" style="color:<?= $row['color'] ?>"><?= stockText($row['status']) ?></span></p></div>
            </div>
          <?php endforeach; ?>
        </section>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </main>
  <script>document.getElementById("header-logo").addEventListener("click", () => window.location.href = "main.php");</script>
</body>
</html>
