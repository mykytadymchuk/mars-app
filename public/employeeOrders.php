<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../services/databaseConnect.php';
require_once __DIR__ . '/services/orderFunctions.php';
use MongoDB\BSON\ObjectId;

$viewer = $usersCollection->findOne(['Логін' => $_SESSION['username']]);
if (!$viewer) { session_destroy(); header('Location: login.php'); exit; }
$employeeId = (string) ($_GET['employeeId'] ?? '');
try { $employee = $employeesCollection->findOne(['_id' => new ObjectId($employeeId)]); } catch (Throwable $e) { header('Location: employees.php'); exit; }
if (!$employee) { header('Location: employees.php'); exit; }
$employeeName = trim((string) ($employee['Прізвище'] ?? '') . ' ' . (string) ($employee['Ім\'я'] ?? ''));
$employeeUsername = (string) ($employee['Логін'] ?? '');
$employeeUser = $employeeUsername !== '' ? $usersCollection->findOne(['Логін' => $employeeUsername]) : null;

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
$orders = [];
if ($employeeUser) {
    $userId = $employeeUser['_id'];
    $filter = [
        'createdDate' => ['$gte' => $startDate, '$lte' => $endDate],
        '$or' => [['createdBy.userId' => $userId], ['assignedDesignerId' => $userId], ['history.userId' => $userId]]
    ];
    $visibleStatuses = orderVisibleStatuses($viewer);
    if ($visibleStatuses !== null) $filter['status'] = ['$in' => $visibleStatuses];
    foreach ($ordersCollection->find($filter, ['sort' => ['createdAt' => -1]]) as $order) {
        if (orderCanView($order, $viewer)) $orders[] = $order;
    }
}
$ordersByDate = [];
foreach ($orders as $order) $ordersByDate[$order['createdDate'] ?? ''][] = $order;
?>
<!doctype html>
<html lang="uk"><head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" />
  <link rel="stylesheet" href="styles/general-style.css" /><link rel="stylesheet" href="styles/table-page-style.css" /><link rel="stylesheet" href="styles/search-bar-style.css" /><link rel="stylesheet" href="styles/page-header-style.css" /><link rel="stylesheet" href="styles/nav-bar-style.css" /><link rel="stylesheet" href="styles/order-style.css" /><link rel="stylesheet" href="styles/responsive.css" />
  <title>Замовлення працівника — <?= orderText($employeeName) ?></title>
</head><body>
  <div class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" /><div class="header-right-corner"><p id="main-greetings"><?= orderText($employeeName) ?> · Замовлення</p></div></div>
  <div class="nav-bar"><a class="left-arrow" href="employeeOrders.php?employeeId=<?= rawurlencode($employeeId) ?>&amp;week=<?= $weekOffset - 1 ?>"><img src="src/left-arrow.svg" alt="Попередній тиждень" class="nav-bar-arrow" /></a><button type="button" class="mid-section" id="range-toggle"><?= orderText($rangeTitle) ?></button><a class="right-arrow" href="employeeOrders.php?employeeId=<?= rawurlencode($employeeId) ?>&amp;week=<?= $weekOffset + 1 ?>"><img src="src/right-arrow.svg" alt="Наступний тиждень" class="nav-bar-arrow" /></a></div>
  <form method="get" class="income-range-form" id="range-form" hidden><input type="hidden" name="employeeId" value="<?= orderText($employeeId) ?>" /><label>Від <input type="date" name="start" value="<?= orderText($startDate) ?>" required /></label><label>До <input type="date" name="end" value="<?= orderText($endDate) ?>" required /></label><button type="submit">Показати</button></form>
  <main class="main-body orders-list">
    <?php if (!$employeeUser): ?><p class="none-message">Для працівника не знайдено обліковий запис.</p><?php elseif (!$orders): ?><p class="none-message">Замовлень працівника за цей період не знайдено.</p><?php endif; ?>
    <?php foreach ($ordersByDate as $date => $dayOrders): ?><section class="orders-date-group"><h2><?= orderText((new DateTimeImmutable($date, $timezone))->format('d.m.Y')) ?></h2>
      <?php foreach ($dayOrders as $order): $orderId = (string) $order['_id']; $status = (string) ($order['status'] ?? 'Створено'); $statusClass = $status === 'Готове' ? 'status-complete' : ($status === 'Скасовано' ? 'status-cancelled' : 'status-active'); ?>
        <article class="order-card"><div class="order-card-heading"><div><p class="order-number">ЗАМОВЛЕННЯ №<?= orderText(substr($orderId, -6)) ?></p><h3><?= orderText($order['customerName'] ?? '') ?></h3></div><span class="order-status <?= $statusClass ?>"><?= orderText($status) ?></span></div>
          <div class="order-card-summary"><span><?= count($order['items'] ?? []) ?> позицій</span><span>Створено <?= orderText(orderFormatDateTime($order['createdAt'] ?? null)) ?></span></div>
          <ul><?php foreach (($order['items'] ?? []) as $item): ?><li><?= orderText($item['printType'] ?? '') ?> · <?= orderText($item['materialName'] ?? '') ?></li><?php endforeach; ?></ul>
          <div class="order-card-actions"><a class="order-action-button" href="order.php?id=<?= rawurlencode($orderId) ?>">Відкрити замовлення</a></div>
        </article>
      <?php endforeach; ?>
    </section><?php endforeach; ?>
  </main>
  <script>
    document.getElementById('header-logo').onclick = () => location.href = 'main.php';
    document.getElementById('range-toggle').onclick = () => document.getElementById('range-form').hidden = !document.getElementById('range-form').hidden;
  </script>
  <script src="scripts/chat-notifications.js" defer></script>
</body></html>
