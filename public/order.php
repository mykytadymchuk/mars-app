<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../services/databaseConnect.php';
require_once __DIR__ . '/services/orderFunctions.php';
use MongoDB\BSON\ObjectId;
$user = $usersCollection->findOne(['Логін' => $_SESSION['username']]);
if (!$user) { session_destroy(); header('Location: login.php'); exit; }
$id = (string) ($_GET['id'] ?? '');
try { $order = $ordersCollection->findOne(['_id' => new ObjectId($id)]); } catch (Throwable $e) { header('Location: orders.php'); exit; }
if (!$order) { header('Location: orders.php'); exit; }
$status = (string) ($order['status'] ?? 'Створено');
if (!orderCanView($order, $user)) { header('Location: orders.php'); exit; }
$manager = orderIsManager($user); $designer = orderIsDesigner($user); $printer = orderIsPrinter($user); $postworker = orderIsPostworker($user);
$responsibleManager = orderIsResponsibleManager($order, $user);
$designerOwnsOrder = orderDesignerOwns($order, $user);
$items = $order['items'] ?? [];
$hasPostworks = orderHasPostworks($order);
$stockEntitiesByItem = [];
if ($printer && $status === 'В роботі') foreach ($items as $itemIndex => $item) {
    $stockFilter = ['materialName' => $item['materialName'] ?? ''];
    if (!empty($item['materialType'])) $stockFilter['type'] = $item['materialType'];
    $entities = $stockCollection->find($stockFilter)->toArray();
    $wanted = [];
    foreach (($item['parameters'] ?? []) as $parameter) {
        $parameterName = (string) ($parameter['name'] ?? '');
        if (!orderIsSizeParameter($parameterName)) $wanted[$parameterName] = (string) ($parameter['value'] ?? '');
    }
    ksort($wanted);
    $stockEntitiesByItem[$itemIndex] = [];
    foreach ($entities as $entity) {
        $actual = [];
        foreach (($entity['parameters'] ?? []) as $parameter) {
            $parameterName = (string) ($parameter['name'] ?? '');
            if (!orderIsSizeParameter($parameterName)) $actual[$parameterName] = (string) ($parameter['value'] ?? '');
        }
        ksort($actual);
        if ($actual === $wanted && (float) ($entity['percentage'] ?? 0) > 0) $stockEntitiesByItem[$itemIndex][] = $entity;
    }
}
$statusClass = $status === 'Готове' ? 'status-complete' : ($status === 'Скасовано' ? 'status-cancelled' : 'status-active');
$actionErrors = ['file_too_large' => 'Файл завеликий. Максимальний розмір — 20 МБ.', 'file_upload_failed' => 'Не вдалося завантажити файл.', 'material_selection' => 'Не вдалося списати матеріал. Перевірте вибрані одиниці та їхній залишок.'];
$actionError = $_GET['error'] ?? '';
?>
<!doctype html>
<html lang="uk"><head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" /><link rel="stylesheet" href="styles/general-style.css" /><link rel="stylesheet" href="styles/page-header-style.css" /><link rel="stylesheet" href="styles/order-style.css" />
  <title>Замовлення <?= orderText(substr((string) $order['_id'], -6)) ?></title><link rel="stylesheet" href="styles/responsive.css" />
</head><body>
  <div class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" /><div class="header-right-corner"><p id="main-greetings">Вітаю, <?= orderText($_SESSION['username']) ?>!</p></div></div>
  <?php if (isset($actionErrors[$actionError])): ?><script>alert(<?= json_encode($actionErrors[$actionError]) ?>);</script><?php endif; ?>
  <main class="order-detail-page">
    <div class="order-detail-heading"><div><p class="order-number">ЗАМОВЛЕННЯ №<?= orderText(substr((string) $order['_id'], -6)) ?></p><h1><?= orderText($order['customerName'] ?? '') ?></h1><p>Створено: <?= orderText(orderFormatDateTime($order['createdAt'] ?? null)) ?></p><?php if (!empty($order['assignedDesignerUsername'])): ?><p>Дизайнер: <?= orderText($order['assignedDesignerUsername']) ?></p><?php endif; ?></div><span class="order-status <?= $statusClass ?>"><?= orderText($status) ?></span></div>
    <div class="order-action-bar">
      <?php if ($manager): ?>
        <?php if ($responsibleManager): ?><a class="order-action-button" href="addOrder.php?id=<?= rawurlencode($id) ?>">Редагувати замовлення</a><?php endif; ?>
        <?php if ($status === 'Готове до видачі' && $responsibleManager): ?><form method="post" action="services/orderActionProcess.php"><input type="hidden" name="id" value="<?= orderText($id) ?>" /><input type="hidden" name="action" value="manager_complete" /><button class="order-action-button" type="submit">Закрити замовлення</button></form><?php elseif ($status === 'Готове до видачі'): ?><p>Закрити або редагувати замовлення може відповідальний менеджер: <?= orderText($order['createdBy']['username'] ?? '') ?>.</p><?php endif; ?>
        <?php if ($status !== 'Скасовано'): ?><form method="post" action="services/orderActionProcess.php" onsubmit="return confirm('Скасувати замовлення?')"><input type="hidden" name="id" value="<?= orderText($id) ?>" /><input type="hidden" name="action" value="manager_cancel" /><button class="order-action-button danger-action" type="submit">Скасувати замовлення</button></form><?php endif; ?>
      <?php endif; ?>
      <?php if ($designer && $status === 'Створено' && empty($order['assignedDesignerId'])): ?><form method="post" action="services/orderActionProcess.php"><input type="hidden" name="id" value="<?= orderText($id) ?>" /><input type="hidden" name="action" value="designer_accept" /><button class="order-action-button" type="submit">Прийняти в роботу</button></form><?php endif; ?>
      <?php if ($designer && $status === 'Підготовка макету' && $designerOwnsOrder): ?>
        <form method="post" action="services/orderActionProcess.php"><input type="hidden" name="id" value="<?= orderText($id) ?>" /><input type="hidden" name="action" value="designer_release" /><button class="order-action-button" type="submit">Відмовитись від замовлення</button></form>
        <form method="post" action="services/orderActionProcess.php"><input type="hidden" name="id" value="<?= orderText($id) ?>" /><input type="hidden" name="action" value="designer_finish" /><button class="order-action-button" type="submit">Завершити підготовку</button></form>
      <?php endif; ?>
      <?php if ($printer && $status === 'Очікує виконання'): ?><form method="post" action="services/orderActionProcess.php"><input type="hidden" name="id" value="<?= orderText($id) ?>" /><input type="hidden" name="action" value="printer_accept" /><button class="order-action-button" type="submit">Прийняти в роботу</button></form><?php endif; ?>
      <?php if ($printer && $status === 'В роботі'): ?>
        <form method="post" action="services/orderActionProcess.php"><input type="hidden" name="id" value="<?= orderText($id) ?>" /><input type="hidden" name="action" value="printer_wait_material" /><button class="order-action-button" type="submit">Очікування матеріалу</button></form>
      <?php endif; ?>
      <?php if ($printer && $status === 'Очікування матеріалу'): ?><form method="post" action="services/orderActionProcess.php"><input type="hidden" name="id" value="<?= orderText($id) ?>" /><input type="hidden" name="action" value="printer_resume" /><button class="order-action-button" type="submit">Повернути в роботу</button></form><?php endif; ?>
      <?php if ($postworker && $status === 'Очікує постобробки'): ?><form method="post" action="services/orderActionProcess.php"><input type="hidden" name="id" value="<?= orderText($id) ?>" /><input type="hidden" name="action" value="postworker_accept" /><button class="order-action-button" type="submit">Прийняти на постобробку</button></form><?php endif; ?>
      <?php if ($postworker && $status === 'Постобробка'): ?><form method="post" action="services/orderActionProcess.php"><input type="hidden" name="id" value="<?= orderText($id) ?>" /><input type="hidden" name="action" value="postworker_finish" /><button class="order-action-button" type="submit">Завершити постобробку</button></form><?php endif; ?>
    </div>
    <?php if ($printer && $status === 'В роботі'): ?>
      <form class="material-use-form" method="post" action="services/orderActionProcess.php" id="material-use-form">
        <input type="hidden" name="id" value="<?= orderText($id) ?>" /><input type="hidden" name="action" value="printer_finish" />
        <h2>Матеріал для друку</h2>
        <?php foreach ($items as $itemIndex => $item): $dimensions = $item['designerFile']['dimensionsMm'] ?? null; ?>
          <div class="material-use-row"><b>Позиція <?= $itemIndex + 1 ?> · <?= orderText($item['materialName'] ?? '') ?></b>
            <?php if ($dimensions): ?><span class="print-dimensions" data-width="<?= (float) $dimensions['width'] ?>" data-height="<?= (float) $dimensions['height'] ?>">Розмір файлу<?= !empty($dimensions['estimated']) ? ' (оцінка при 96 DPI)' : '' ?>: <?= number_format((float) $dimensions['width'], 2, ',', ' ') ?> × <?= number_format((float) $dimensions['height'], 2, ',', ' ') ?> мм</span>
              <label class="rotate-print"><input type="checkbox" name="rotated[<?= $itemIndex ?>]" value="1" /> Повернути на 90°</label>
            <?php else: ?><span>Розмір файлу не визначено. Дизайнер має завантажити PDF, SVG або зображення.</span><?php endif; ?>
            <label>Конкретна одиниця матеріалу
              <select name="entityId[<?= $itemIndex ?>]" required <?= !$dimensions || empty($stockEntitiesByItem[$itemIndex]) ? 'disabled' : '' ?>><option value="">Оберіть залишок</option>
                <?php foreach (($stockEntitiesByItem[$itemIndex] ?? []) as $entity): $entityDimensions = orderEntityDimensionsMm($entity); ?><option value="<?= orderText((string) $entity['_id']) ?>" data-width="<?= $entityDimensions['width'] ?? '' ?>" data-height="<?= $entityDimensions['height'] ?? '' ?>" data-type="<?= orderText($entity['type'] ?? '') ?>"><?= orderText(orderEntityLabel($entity)) ?></option><?php endforeach; ?>
              </select>
            </label><span class="material-use-preview" aria-live="polite"></span>
          </div>
        <?php endforeach; ?>
        <button class="order-action-button" type="submit"><?= $hasPostworks ? 'Передати на постобробку' : 'Завершити друк та списати матеріал' ?></button>
      </form>
    <?php endif; ?>
    <?php foreach ($items as $index => $item): ?>
      <section class="order-detail-item"><h2>Позиція <?= $index + 1 ?></h2>
        <div class="order-detail-grid"><p><b>Тип друку:</b> <?= orderText($item['printType'] ?? '') ?></p><p><b>Підготовка:</b> <?= orderText($item['preparation'] ?? 'Не потрібна') ?></p><p><b>Матеріал:</b> <?= orderText($item['materialName'] ?? '') ?></p>
          <?php foreach (($item['parameters'] ?? []) as $parameter): ?><p><b><?= orderText($parameter['name'] ?? '') ?>:</b> <?= orderText($parameter['value'] ?? '') ?></p><?php endforeach; ?>
          <p><b>Постобробка:</b> <?= count(orderValues($item['postworks'] ?? [])) > 0 ? orderText(implode(', ', orderValues($item['postworks']))) : 'Не потрібна' ?></p>
          <?php if (!empty($item['materialUsage'])): ?><p><b>Використаний складський залишок:</b> <?= orderText($item['materialUsage']['entityLabel'] ?? '') ?>; списано <?= number_format((float) ($item['materialUsage']['percentageUsed'] ?? 0), 2, ',', ' ') ?>%<?= !empty($item['materialUsage']['rotated']) ? '; макет повернуто' : '' ?></p><?php endif; ?>
        </div>
        <div class="order-files">
          <?php if (!empty($item['managerFile']['storedName'])): ?><a href="services/downloadOrderFile.php?id=<?= rawurlencode($id) ?>&amp;item=<?= $index ?>&amp;type=manager">Завантажити файл менеджера: <?= orderText($item['managerFile']['originalName'] ?? 'Файл') ?></a><?php endif; ?>
          <?php if (!empty($item['designerFile']['storedName'])): ?><a href="services/downloadOrderFile.php?id=<?= rawurlencode($id) ?>&amp;item=<?= $index ?>&amp;type=designer">Завантажити файл дизайнера: <?= orderText($item['designerFile']['originalName'] ?? 'Файл') ?></a><?php endif; ?>
          <?php if (!empty($item['designerFile']['dimensionsMm'])): ?><p>Розмір макету<?= !empty($item['designerFile']['dimensionsMm']['estimated']) ? ' (оцінка при 96 DPI)' : '' ?>: <?= number_format((float) $item['designerFile']['dimensionsMm']['width'], 2, ',', ' ') ?> × <?= number_format((float) $item['designerFile']['dimensionsMm']['height'], 2, ',', ' ') ?> мм</p><?php endif; ?>
          <?php if ($designer && $designerOwnsOrder): ?><form class="designer-file-form" method="post" action="services/orderActionProcess.php" enctype="multipart/form-data"><input type="hidden" name="id" value="<?= orderText($id) ?>" /><input type="hidden" name="action" value="designer_upload" /><input type="hidden" name="itemIndex" value="<?= $index ?>" /><label>Завантажити або оновити файл макету <input type="file" name="designerFile" required /></label><button class="order-action-button" type="submit">Завантажити файл</button></form><?php elseif ($designer && !$designerOwnsOrder && $status === 'Підготовка макету'): ?><p>Замовлення прийняв дизайнер <?= orderText($order['assignedDesignerUsername'] ?? '') ?>.</p><?php endif; ?>
        </div>
        <div class="order-comments"><p><b>Коментар дизайнеру:</b> <?= nl2br(orderText($item['designerComment'] ?? '')) ?: '—' ?></p><p><b>Коментар друкарю:</b> <?= nl2br(orderText($item['printerComment'] ?? '')) ?: '—' ?></p><p><b>Коментар постобробнику:</b> <?= nl2br(orderText($item['postworkerComment'] ?? '')) ?: '—' ?></p></div>
      </section>
    <?php endforeach; ?>
    <section class="order-history"><h2>Історія змін</h2>
      <?php foreach (($order['history'] ?? []) as $entry): ?><article class="order-history-entry"><time><?= orderText(orderFormatDateTime($entry['createdAt'] ?? null)) ?></time><b><?= orderText($entry['username'] ?? '') ?><?= !empty($entry['role']) ? ' (' . orderText($entry['role']) . ')' : '' ?></b><p><?= orderText($entry['message'] ?? '') ?></p></article><?php endforeach; ?>
    </section>
  </main>
  <script>
    document.getElementById('header-logo').onclick = () => location.href = 'main.php';
    document.querySelectorAll('.material-use-row').forEach(row => {
      const size = row.querySelector('.print-dimensions');
      const rotate = row.querySelector('.rotate-print input');
      const select = row.querySelector('select');
      const preview = row.querySelector('.material-use-preview');
      const update = () => {
        if (!size || !select || !preview) return;
        let width = Number(size.dataset.width), height = Number(size.dataset.height);
        if (rotate && rotate.checked) [width, height] = [height, width];
        const option = select.selectedOptions[0];
        const stockWidth = Number(option?.dataset.width), stockHeight = Number(option?.dataset.height);
        let used = '';
        if (option?.value && stockWidth && stockHeight) {
          let percent = width * height / (stockWidth * stockHeight) * 100;
          used = `; спише приблизно ${percent.toFixed(2)}% площі одиниці`;
          if (option.dataset.type === 'roll') {
            const fitsCurrent = width <= stockWidth && height <= stockHeight;
            const alternateFits = height <= stockWidth && width <= stockHeight;
            if (!fitsCurrent && !alternateFits) used += '; макет не вміщується на цей рулон';
            else if (!fitsCurrent && alternateFits) used += `; поверніть макет, довжина рулону буде ${width.toFixed(0)} мм`;
            else {
              used += `; довжина рулону: ${height.toFixed(0)} мм`;
              if (alternateFits && width < height) used += `; поворот заощадить ${(height - width).toFixed(0)} мм намотки`;
              else if (alternateFits && width > height) used += `; цей поворот заощаджує ${(width - height).toFixed(0)} мм намотки`;
            }
          }
        }
        preview.textContent = `Макет: ${width.toFixed(2)} × ${height.toFixed(2)} мм${used}`;
      };
      rotate?.addEventListener('change', update); select?.addEventListener('change', update); update();
    });
  </script>
  <script src="scripts/chat-notifications.js" defer></script>
</body></html>
