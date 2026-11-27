<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../services/databaseConnect.php';
require_once __DIR__ . '/services/orderFunctions.php';
$user = $usersCollection->findOne(['Логін' => $_SESSION['username']]);
if (!$user) { session_destroy(); header('Location: login.php'); exit; }
$timezone = new DateTimeZone('Europe/Kyiv');
$today = new DateTimeImmutable('today', $timezone);
$weekOffset = filter_var($_GET['week'] ?? 0, FILTER_VALIDATE_INT);
$weekOffset = $weekOffset === false ? 0 : max(-520, min(520, $weekOffset));
$requestedStart = $_GET['start'] ?? ''; $requestedEnd = $_GET['end'] ?? '';
$customRange = preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedStart) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedEnd) && $requestedStart <= $requestedEnd;
if ($customRange) {
    $rangeStart = new DateTimeImmutable($requestedStart, $timezone); $rangeEnd = new DateTimeImmutable($requestedEnd, $timezone);
    $rangeTitle = $rangeStart->format('d.m.y') . ' - ' . $rangeEnd->format('d.m.y');
} else {
    $monday = $today->modify('-' . ((int) $today->format('N') - 1) . ' days');
    $rangeStart = $monday->modify(($weekOffset * 7) . ' days'); $rangeEnd = $rangeStart->modify('+6 days');
    $rangeTitle = $weekOffset === 0 ? 'Поточний тиждень' : $rangeStart->format('d.m.y') . ' - ' . $rangeEnd->format('d.m.y');
}
$startDate = $rangeStart->format('Y-m-d'); $endDate = $rangeEnd->format('Y-m-d');
$searchField = $_GET['field'] ?? 'customer'; $search = trim((string) ($_GET['search'] ?? ''));
if (!in_array($searchField, ['customer', 'number', 'status'], true)) $searchField = 'customer';
$manager = orderIsManager($user); $designer = orderIsDesigner($user); $printer = orderIsPrinter($user); $postworker = orderIsPostworker($user);
$ordersFilter = ['createdDate' => ['$gte' => $startDate, '$lte' => $endDate]];
$visibleStatuses = orderVisibleStatuses($user);
if ($visibleStatuses !== null) $ordersFilter['status'] = ['$in' => $visibleStatuses];
$orders = $ordersCollection->find($ordersFilter, ['sort' => ['createdAt' => -1]])->toArray();
if ($search !== '') {
    $orders = array_values(array_filter($orders, static function ($order) use ($searchField, $search): bool {
        $value = $searchField === 'customer' ? ($order['customerName'] ?? '') : ($searchField === 'status' ? ($order['status'] ?? '') : substr((string) ($order['_id'] ?? ''), -6));
        return function_exists('mb_stripos') ? mb_stripos((string) $value, $search, 0, 'UTF-8') !== false : stripos((string) $value, $search) !== false;
    }));
}
$ordersByDate = [];
foreach ($orders as $order) $ordersByDate[$order['createdDate'] ?? ''][] = $order;
?>
<!doctype html><html lang="uk"><head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" /><link rel="stylesheet" href="styles/general-style.css" /><link rel="stylesheet" href="styles/table-page-style.css" /><link rel="stylesheet" href="styles/search-bar-style.css" /><link rel="stylesheet" href="styles/page-header-style.css" /><link rel="stylesheet" href="styles/nav-bar-style.css" /><link rel="stylesheet" href="styles/order-style.css" />
  <title>Замовлення</title><link rel="stylesheet" href="styles/responsive.css" />
</head><body>
  <form action="" method="get"><input type="hidden" name="week" value="<?= $customRange ? '' : $weekOffset ?>" /><?php if ($customRange): ?><input type="hidden" name="start" value="<?= orderText($startDate) ?>" /><input type="hidden" name="end" value="<?= orderText($endDate) ?>" /><?php endif; ?><header class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
    <div class="search-bar-container"><select name="field" class="search-select"><option value="customer" <?= $searchField === 'customer' ? 'selected' : '' ?>>Клієнт</option><option value="number" <?= $searchField === 'number' ? 'selected' : '' ?>>Номер</option><option value="status" <?= $searchField === 'status' ? 'selected' : '' ?>>Статус</option></select><input type="text" name="search" class="search-bar" placeholder="Пошук" value="<?= orderText($search) ?>" /><button class="search-button"><img src="src/search.png" alt="Пошук" /></button></div>
    <?php if ($manager): ?><div class="add-new-item" id="add-new-order">+</div><?php endif; ?>
    <div class="header-right-corner"><p id="main-greetings">Вітаю, <?= orderText($_SESSION['username']) ?>!</p></div>
  </header></form>
  <div class="nav-bar"><a class="left-arrow" href="orders.php?week=<?= $weekOffset - 1 ?>"><img src="src/left-arrow.svg" alt="Попередній тиждень" class="nav-bar-arrow" /></a><button type="button" class="mid-section" id="range-toggle"><?= orderText($rangeTitle) ?></button><a class="right-arrow" href="orders.php?week=<?= $weekOffset + 1 ?>"><img src="src/right-arrow.svg" alt="Наступний тиждень" class="nav-bar-arrow" /></a></div>
  <form method="get" class="income-range-form" id="range-form" hidden><label>Від <input type="date" name="start" value="<?= orderText($startDate) ?>" required></label><label>До <input type="date" name="end" value="<?= orderText($endDate) ?>" required></label><button type="submit">Показати</button><input type="hidden" name="field" value="<?= orderText($searchField) ?>" /><input type="hidden" name="search" value="<?= orderText($search) ?>" /></form>
  <main class="main-body orders-list">
    <?php if (!$orders): ?><p class="none-message">Замовлень за цей період не знайдено.</p><?php endif; ?>
    <?php foreach ($ordersByDate as $date => $dayOrders): ?><section class="orders-date-group"><h2><?= orderText((new DateTimeImmutable($date))->format('d.m.Y')) ?></h2>
      <?php foreach ($dayOrders as $order): ?>
        <?php $status = (string) ($order['status'] ?? 'Створено'); $statusClass = $status === 'Готове' ? 'status-complete' : ($status === 'Скасовано' ? 'status-cancelled' : 'status-active'); $orderId = (string) $order['_id']; ?>
        <article class="order-card"><div class="order-card-heading"><div><p class="order-number">ЗАМОВЛЕННЯ №<?= orderText(substr($orderId, -6)) ?></p><h3><?= orderText($order['customerName'] ?? '') ?></h3></div><span class="order-status <?= $statusClass ?>"><?= orderText($status) ?></span></div>
          <div class="order-card-summary"><span><?= count($order['items'] ?? []) ?> позицій</span><span>Створено <?= orderText(orderFormatDateTime($order['createdAt'] ?? null)) ?></span><span><?= orderText($order['createdBy']['username'] ?? '') ?></span><?php if (!empty($order['assignedDesignerUsername'])): ?><span>Дизайнер: <?= orderText($order['assignedDesignerUsername']) ?></span><?php endif; ?></div>
          <ul><?php foreach (($order['items'] ?? []) as $item): ?><li><?= orderText($item['printType'] ?? '') ?> · <?= orderText($item['materialName'] ?? '') ?><?php if (!empty($item['parameters'])): ?> (<?= orderText(implode(', ', array_map(static fn($parameter) => (string) ($parameter['value'] ?? ''), orderValues($item['parameters'])))) ?>)<?php endif; ?></li><?php endforeach; ?></ul>
          <div class="order-card-actions"><a class="order-action-button" href="order.php?id=<?= rawurlencode($orderId) ?>">Відкрити замовлення</a>
            <?php if (($designer && $status === 'Створено' && empty($order['assignedDesignerId'])) || ($printer && $status === 'Очікує виконання') || ($postworker && $status === 'Очікує постобробки')): ?><form method="post" action="services/orderActionProcess.php"><input type="hidden" name="id" value="<?= orderText($orderId) ?>" /><input type="hidden" name="action" value="<?= $designer ? 'designer_accept' : ($printer ? 'printer_accept' : 'postworker_accept') ?>" /><button class="order-action-button" type="submit">Прийняти</button></form><?php endif; ?>
            <?php if ($manager && orderIsResponsibleManager($order, $user)): ?><a class="order-action-button" href="addOrder.php?id=<?= rawurlencode($orderId) ?>">Редагувати</a><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </section><?php endforeach; ?>
  </main>
  <script>
    document.getElementById('header-logo').onclick = () => location.href = 'main.php';
    document.getElementById('add-new-order')?.addEventListener('click', () => location.href = 'addOrder.php');
    document.getElementById('range-toggle').onclick = () => document.getElementById('range-form').hidden = !document.getElementById('range-form').hidden;
    document.querySelectorAll('.left-arrow,.right-arrow').forEach(link => link.addEventListener('click', () => { const url = new URL(link.href); url.searchParams.set('field', <?= json_encode($searchField) ?>); url.searchParams.set('search', <?= json_encode($search) ?>); link.href = url.toString(); }));
  </script>
  <script src="scripts/chat-notifications.js" defer></script>
</body></html>
