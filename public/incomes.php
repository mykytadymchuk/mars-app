<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../services/databaseConnect.php';

function incomeText($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function incomeContains($value, string $needle): bool
{
    return function_exists('mb_stripos') ? mb_stripos((string) $value, $needle, 0, 'UTF-8') !== false : stripos((string) $value, $needle) !== false;
}

$timezone = new DateTimeZone('Europe/Kyiv');
$today = new DateTimeImmutable('today', $timezone);
$weekOffset = filter_var($_GET['week'] ?? 0, FILTER_VALIDATE_INT);
$weekOffset = $weekOffset === false ? 0 : max(-520, min(520, $weekOffset));
$requestedStart = $_GET['start'] ?? '';
$requestedEnd = $_GET['end'] ?? '';
$customRange = preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedStart) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedEnd) && $requestedStart <= $requestedEnd;
if ($customRange) {
    $rangeStart = new DateTimeImmutable($requestedStart, $timezone);
    $rangeEnd = new DateTimeImmutable($requestedEnd, $timezone);
    $rangeTitle = $rangeStart->format('d.m.y') . ' - ' . $rangeEnd->format('d.m.y');
} else {
    $monday = $today->modify('-' . ((int) $today->format('N') - 1) . ' days');
    $rangeStart = $monday->modify(($weekOffset * 7) . ' days');
    $rangeEnd = $rangeStart->modify('+6 days');
    $rangeTitle = $weekOffset === 0 ? 'Поточний тиждень' : $rangeStart->format('d.m.y') . ' - ' . $rangeEnd->format('d.m.y');
}
$startDate = $rangeStart->format('Y-m-d');
$endDate = $rangeEnd->format('Y-m-d');

$searchField = $_GET['field'] ?? 'supplier';
$search = trim($_GET['search'] ?? '');
if (!in_array($searchField, ['supplier', 'date', 'material'], true))
    $searchField = 'supplier';
$incomes = $incomesCollection->find(['incomeDate' => ['$gte' => $startDate, '$lte' => $endDate]], ['sort' => ['incomeDate' => -1, 'createdAt' => -1]])->toArray();
if ($search !== '') {
    $incomes = array_values(array_filter($incomes, static function ($income) use ($searchField, $search): bool {
        if ($searchField === 'date') {
            $date = (string) ($income['incomeDate'] ?? '');
            $formattedDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? (new DateTimeImmutable($date))->format('d.m.Y') : '';
            return incomeContains($date, $search) || incomeContains($formattedDate, $search);
        }
        $value = $searchField === 'supplier' ? ($income['supplierName'] ?? '') : ($income['materialName'] ?? '');
        return incomeContains($value, $search);
    }));
}
$incomesByDate = [];
foreach ($incomes as $income)
    $incomesByDate[$income['incomeDate'] ?? ''][] = $income;
?>
<!doctype html>
<html lang="uk">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" />
    <link rel="stylesheet" href="styles/general-style.css" />
    <link rel="stylesheet" href="styles/table-page-style.css" />
    <link rel="stylesheet" href="styles/search-bar-style.css" />
    <link rel="stylesheet" href="styles/page-header-style.css" />
    <link rel="stylesheet" href="styles/nav-bar-style.css" />
    <title>Надходження</title>
  <link rel="stylesheet" href="styles/responsive.css" />
</head>

<body>
    <form action="" method="get">
        <header class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
            <div class="search-bar-container"><select name="field" id="income-search-select" class="search-select">
                    <option value="supplier" <?= $searchField === 'supplier' ? 'selected' : '' ?>>Постачальник</option>
                    <option value="date" <?= $searchField === 'date' ? 'selected' : '' ?>>Дата</option>
                    <option value="material" <?= $searchField === 'material' ? 'selected' : '' ?>>Матеріал</option>
                </select><input type="text" name="search" id="income-search" class="search-bar" placeholder="Пошук"
                    value="<?= incomeText($search) ?>" /><button class="search-button"><img src="src/search.png"
                        alt="search-button" draggable="false" /></button></div>
            <div class="add-new-item" id="add-new-income">+</div>
            <div class="header-right-corner">
                <p id="main-greetings">Вітаю, <?= incomeText($_SESSION['username']) ?>!</p>
            </div>
        </header>
    </form>
    <div class="nav-bar"><a class="left-arrow" href="incomes.php?week=<?= $weekOffset - 1 ?>"><img
                src="src/left-arrow.svg" alt="Попередній тиждень" class="nav-bar-arrow" /></a><button type="button"
            class="mid-section" id="range-toggle"><?= incomeText($rangeTitle) ?></button><a class="right-arrow"
            href="incomes.php?week=<?= $weekOffset + 1 ?>"><img src="src/right-arrow.svg" alt="Наступний тиждень"
                class="nav-bar-arrow" /></a></div>
    <form method="get" class="income-range-form" id="range-form" hidden><label>Від <input type="date" name="start"
                value="<?= incomeText($startDate) ?>" required></label><label>До <input type="date" name="end"
                value="<?= incomeText($endDate) ?>" required></label><button type="submit">Показати</button></form>
    <main class="main-body"><?php if (empty($incomesByDate)): ?>
            <p class="none-message">Не було знайдено жодного надходження!</p>
        <?php endif; ?><?php foreach ($incomesByDate as $date => $dayIncomes): ?>
            <section class="table">
                <div class="table-header"><?= incomeText((new DateTimeImmutable($date))->format('d.m.Y')) ?></div>
                <?php foreach ($dayIncomes as $number => $income): ?>        <?php if ($number > 0): ?>
                        <hr /><?php endif; ?>
                    <div class="table-element">
                        <div class="left-side">
                            <p class="income-number"><?= $number + 1 ?></p><?php if (!empty($income['supplierPhoto'])): ?><img
                                    src="src/suppliers/<?= rawurlencode((string) $income['supplierPhoto']) ?>"
                                    alt="<?= incomeText($income['supplierName'] ?? '') ?>" class="supplier-logo"
                                    draggable="false" /><?php else: ?>
                                <p class="income-position"><?= incomeText($income['supplierName'] ?? '') ?></p><?php endif; ?>
                            <p class="income-position"><?= incomeText($income['materialName'] ?? '') ?></p>
                            <?php foreach (($income['parameters'] ?? []) as $parameter): ?>
                                <p class="income-position" title="<?= incomeText($parameter['name'] ?? '') ?>">
                                    <?= incomeText($parameter['value'] ?? '') ?></p><?php endforeach; ?>
                        </div>
                        <div class="right-side">
                            <p class="income-count">x<?= incomeText($income['quantity'] ?? 0) ?></p><form action="services/deleteIncomeProcess.php" method="post" class="income-delete-form"><input type="hidden" name="id" value="<?= incomeText((string) $income['_id']) ?>" /><button type="submit" class="income-delete" onclick="return confirm('Видалити це надходження?')">Видалити</button></form>
                        </div>
                    </div><?php endforeach; ?>
            </section><?php endforeach; ?>
    </main>
    <script>document.getElementById('header-logo').onclick = () => location.href = 'main.php'; document.getElementById('add-new-income').onclick = () => location.href = 'addIncome.php'; document.getElementById('range-toggle').onclick = () => document.getElementById('range-form').hidden = !document.getElementById('range-form').hidden; document.querySelector('form[action=""]').addEventListener('submit', event => { const state = document.createElement('input'); state.type = 'hidden'; state.name = <?= $customRange ? "'start'" : "'week'" ?>; state.value = <?= $customRange ? json_encode($startDate) : json_encode((string) $weekOffset) ?>; event.currentTarget.appendChild(state);<?php if ($customRange): ?>const end = document.createElement('input'); end.type = 'hidden'; end.name = 'end'; end.value = <?= json_encode($endDate) ?>; event.currentTarget.appendChild(end); <?php endif; ?> }); document.querySelectorAll('.left-arrow,.right-arrow').forEach(link => link.addEventListener('click', () => { const url = new URL(link.href); url.searchParams.set('field', <?= json_encode($searchField) ?>); url.searchParams.set('search', <?= json_encode($search) ?>); link.href = url.toString(); }));</script>
  <script src="scripts/chat-notifications.js" defer></script>
</body>

</html>
