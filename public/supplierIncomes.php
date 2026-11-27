<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../services/databaseConnect.php';
use MongoDB\BSON\ObjectId;

$supplierId = (string) ($_GET['supplierId'] ?? '');
try { $supplierObjectId = new ObjectId($supplierId); } catch (Throwable $e) { header('Location: suppliers.php'); exit; }
$supplier = $suppliersCollection->findOne(['_id' => $supplierObjectId]);
if (!$supplier) { header('Location: suppliers.php'); exit; }
$timezone = new DateTimeZone('Europe/Kyiv');
$today = new DateTimeImmutable('today', $timezone);
$weekOffset = filter_var($_GET['week'] ?? 0, FILTER_VALIDATE_INT);
$weekOffset = $weekOffset === false ? 0 : max(-520, min(520, $weekOffset));
$requestedStart = (string) ($_GET['start'] ?? '');
$requestedEnd = (string) ($_GET['end'] ?? '');
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
$supplierName = (string) ($supplier['Постачальник'] ?? '');
$incomes = $incomesCollection->find([
    'incomeDate' => ['$gte' => $startDate, '$lte' => $endDate],
    '$or' => [['supplierId' => $supplierObjectId], ['supplierName' => $supplierName]]
], ['sort' => ['incomeDate' => -1, 'createdAt' => -1]])->toArray();
$incomesByDate = [];
foreach ($incomes as $income) $incomesByDate[$income['incomeDate'] ?? ''][] = $income;
function supplierIncomeText($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="uk"><head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" /><link rel="stylesheet" href="styles/general-style.css" /><link rel="stylesheet" href="styles/table-page-style.css" /><link rel="stylesheet" href="styles/page-header-style.css" /><link rel="stylesheet" href="styles/nav-bar-style.css" /><link rel="stylesheet" href="styles/responsive.css" />
  <title>Надходження — <?= supplierIncomeText($supplierName) ?></title>
</head><body>
  <div class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" /><div class="header-right-corner"><p id="main-greetings"><?= supplierIncomeText($supplierName) ?> · Надходження</p></div></div>
  <div class="nav-bar"><a class="left-arrow" href="supplierIncomes.php?supplierId=<?= rawurlencode($supplierId) ?>&amp;week=<?= $weekOffset - 1 ?>"><img src="src/left-arrow.svg" alt="Попередній тиждень" class="nav-bar-arrow" /></a><button type="button" class="mid-section" id="range-toggle"><?= supplierIncomeText($rangeTitle) ?></button><a class="right-arrow" href="supplierIncomes.php?supplierId=<?= rawurlencode($supplierId) ?>&amp;week=<?= $weekOffset + 1 ?>"><img src="src/right-arrow.svg" alt="Наступний тиждень" class="nav-bar-arrow" /></a></div>
  <form method="get" class="income-range-form" id="range-form" hidden><input type="hidden" name="supplierId" value="<?= supplierIncomeText($supplierId) ?>" /><label>Від <input type="date" name="start" value="<?= supplierIncomeText($startDate) ?>" required /></label><label>До <input type="date" name="end" value="<?= supplierIncomeText($endDate) ?>" required /></label><button type="submit">Показати</button></form>
  <main class="main-body">
    <?php if (!$incomesByDate): ?><p class="none-message">Надходжень від цього постачальника за період не знайдено.</p><?php endif; ?>
    <?php foreach ($incomesByDate as $date => $dayIncomes): ?><section class="table"><div class="table-header"><?= supplierIncomeText((new DateTimeImmutable($date, $timezone))->format('d.m.Y')) ?></div>
      <?php foreach ($dayIncomes as $index => $income): ?><?php if ($index > 0): ?><hr /><?php endif; ?><div class="table-element"><div class="left-side"><p class="income-number"><?= $index + 1 ?></p><?php if (!empty($income['supplierPhoto'])): ?><img src="src/suppliers/<?= rawurlencode((string) $income['supplierPhoto']) ?>" alt="<?= supplierIncomeText($supplierName) ?>" class="supplier-logo" draggable="false" /><?php else: ?><p class="income-position"><?= supplierIncomeText($supplierName) ?></p><?php endif; ?><p class="income-position"><?= supplierIncomeText($income['materialName'] ?? '') ?></p><?php foreach (($income['parameters'] ?? []) as $parameter): ?><p class="income-position" title="<?= supplierIncomeText($parameter['name'] ?? '') ?>"><?= supplierIncomeText($parameter['value'] ?? '') ?></p><?php endforeach; ?></div><div class="right-side"><p class="income-count">x<?= supplierIncomeText($income['quantity'] ?? 0) ?></p></div></div><?php endforeach; ?>
    </section><?php endforeach; ?>
  </main>
  <script>document.getElementById('header-logo').onclick = () => location.href = 'main.php'; document.getElementById('range-toggle').onclick = () => document.getElementById('range-form').hidden = !document.getElementById('range-form').hidden;</script>
  <script src="scripts/chat-notifications.js" defer></script>
</body></html>
