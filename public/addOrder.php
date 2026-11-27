<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../services/databaseConnect.php';
require_once __DIR__ . '/services/orderFunctions.php';
$user = $usersCollection->findOne(['Логін' => $_SESSION['username']]);
if (!$user || !orderIsManager($user)) { header('Location: orders.php'); exit; }
use MongoDB\BSON\ObjectId;
$id = (string) ($_GET['id'] ?? '');
$order = null;
if ($id !== '') {
    try { $order = $ordersCollection->findOne(['_id' => new ObjectId($id)]); } catch (Throwable $e) { header('Location: orders.php'); exit; }
    if (!$order) { header('Location: orders.php'); exit; }
    if (!orderIsResponsibleManager($order, $user) || (string) ($order['status'] ?? '') === 'Готове') { header('Location: order.php?id=' . rawurlencode($id)); exit; }
}
$customers = [];
foreach ($customersCollection->find([], ['sort' => ['Прізвище' => 1, 'Назва компанії' => 1]]) as $customer) {
    $customers[] = ['id' => (string) $customer['_id'], 'name' => orderCustomerName($customer), 'phone' => (string) ($customer['Номер телефону'] ?? '')];
}
$materials = [];
foreach ($materialsCollection->find([], ['sort' => ['name' => 1]]) as $material) {
    $name = trim((string) ($material['name'] ?? ''));
    if ($name === '') continue;
    if (!isset($materials[$name])) $materials[$name] = ['name' => $name, 'parameters' => []];
    foreach (($material['parameters'] ?? []) as $parameter) {
        $parameterName = trim((string) ($parameter['name'] ?? ''));
        if ($parameterName === '' || orderIsSizeParameter($parameterName)) continue;
        if (!isset($materials[$name]['parameters'][$parameterName])) $materials[$name]['parameters'][$parameterName] = [];
        foreach (($parameter['values'] ?? []) as $value) {
            $value = trim((string) $value);
            if ($value !== '') $materials[$name]['parameters'][$parameterName][$value] = $value;
        }
    }
}
foreach ($materials as &$material) {
    foreach ($material['parameters'] as $name => $values) $material['parameters'][$name] = array_values($values);
}
unset($material);
$customerId = (string) ($order['customerId'] ?? '');
$customerLabel = '';
if ($customerId !== '') {
    try { $selectedCustomer = $customersCollection->findOne(['_id' => new ObjectId($customerId)]); $customerLabel = orderCustomerName($selectedCustomer); } catch (Throwable $e) { }
}
$initialItems = [];
foreach (($order['items'] ?? []) as $itemIndex => $item) {
    $parameters = [];
    foreach (($item['parameters'] ?? []) as $parameter) $parameters[(string) ($parameter['name'] ?? '')] = (string) ($parameter['value'] ?? '');
    $initialItems[] = [
        'printType' => (string) ($item['printType'] ?? ''),
        'preparation' => (string) ($item['preparation'] ?? ''),
        'materialName' => (string) ($item['materialName'] ?? ''),
        'parameters' => $parameters,
        'postworks' => array_values(orderValues($item['postworks'] ?? [])),
        'designerComment' => (string) ($item['designerComment'] ?? ''),
        'printerComment' => (string) ($item['printerComment'] ?? ''),
        'postworkerComment' => (string) ($item['postworkerComment'] ?? ''),
        'managerFileName' => (string) ($item['managerFile']['originalName'] ?? ''),
        'existingIndex' => $itemIndex
    ];
}
$errors = [
    'invalid_customer' => 'Оберіть клієнта зі списку.',
    'invalid_items' => 'Додайте хоча б одну коректно заповнену позицію.',
    'file_too_large' => 'Файл завеликий. Максимальний розмір — 20 МБ.',
    'save_failed' => 'Замовлення не вдалося зберегти.'
];
$error = $_GET['error'] ?? '';
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" />
  <link rel="stylesheet" href="styles/form-style.css" /><link rel="stylesheet" href="styles/general-style.css" /><link rel="stylesheet" href="styles/order-style.css" />
  <title><?= $order ? 'Редагування замовлення' : 'Нове замовлення' ?></title>
  <link rel="stylesheet" href="styles/responsive.css" />
</head>
<body>
  <div class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" /><div class="header-right-corner"><p id="main-greetings">Вітаю, <?= orderText($_SESSION['username']) ?>!</p></div></div>
  <main class="main-body order-form-page">
    <h1 class="page-title"><?= $order ? 'РЕДАГУВАННЯ ЗАМОВЛЕННЯ' : 'НОВЕ ЗАМОВЛЕННЯ' ?></h1>
    <form action="services/saveOrderProcess.php" method="post" enctype="multipart/form-data" id="order-form">
      <?php if ($order): ?><input type="hidden" name="orderId" value="<?= orderText($id) ?>" /><?php endif; ?>
      <div class="form-row customer-autocomplete"><label class="form-element" for="customer-search">Клієнт</label><input id="customer-search" class="text-input" value="<?= orderText($customerLabel) ?>" placeholder="Почніть вводити назву чи прізвище" autocomplete="off" required /><input type="hidden" name="customerId" id="customer-id" value="<?= orderText($customerId) ?>" /><div id="customer-suggestions" class="customer-suggestions" hidden></div></div>
      <div class="order-items-header"><h2>Позиції замовлення</h2><button type="button" id="add-order-item" class="add-custom-field-button">+ Додати позицію</button></div>
      <div id="order-items"></div>
      <div class="form-buttons"><input type="submit" value="<?= $order ? 'Зберегти зміни' : 'Створити замовлення' ?>" class="submit-button" /><button type="button" id="cancel-button">Скасувати</button></div>
    </form>
  </main>
  <?php if (isset($errors[$error])): ?><script>alert(<?= json_encode($errors[$error]) ?>);</script><?php endif; ?>
  <script>
    const customers = <?= json_encode($customers, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const materials = <?= json_encode($materials, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const initialItems = <?= json_encode($initialItems, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const postworks = ['Підвороти', 'Проклейка', 'Люверси', 'ОПК', 'Глянцева ламінація', 'Матова ламінація'];
    const printTypes = ['УФ-друк', 'Сольвентний друк', 'Еко-сольвентний друк'];
    const preparations = ['Різка на лазері', 'Різка на фрезері'];
    const itemContainer = document.getElementById('order-items');
    const customerSearch = document.getElementById('customer-search');
    const customerId = document.getElementById('customer-id');
    const suggestions = document.getElementById('customer-suggestions');
    function option(select, value, label, selected = false) { const node = document.createElement('option'); node.value = value; node.textContent = label; node.selected = selected; select.appendChild(node); }
    function fillSelect(select, values, placeholder, selectedValue = '') { select.replaceChildren(); option(select, '', placeholder, !selectedValue); values.forEach(value => option(select, value, value, value === selectedValue)); }
    function createItem(index, item = {}) {
      const area = document.createElement('section');
      area.className = 'order-item-editor';
      area.dataset.existingIndex = item.existingIndex ?? '';
      area.innerHTML = `<div class="order-item-top"><h3>Позиція ${index + 1}</h3><button type="button" class="remove-order-item" aria-label="Видалити позицію">Видалити</button></div>
        <input type="hidden" name="items[${index}][existingIndex]" value="${item.existingIndex ?? ''}" />
        <div class="form-row"><label class="form-element">Тип друку</label><select class="text-input print-type" name="items[${index}][printType]" required></select></div>
        <div class="form-row"><label class="form-element">Підготовка</label><select class="text-input preparation" name="items[${index}][preparation]"></select></div>
        <div class="form-row"><label class="form-element">Матеріал</label><select class="text-input material-name" name="items[${index}][materialName]" required></select></div>
        <div class="material-parameters"></div>
        <div class="form-row"><label class="form-element">Постобробка</label><div class="order-postworks"></div></div>
        <div class="form-row"><label class="form-element">Файл від менеджера</label><div class="order-file-drop"><input class="order-file" type="file" name="items[${index}][managerFile]" /><button type="button" class="order-file-trigger">Оберіть файл або перетягніть сюди</button><span class="order-file-name">Файл не обрано</span></div>${item.managerFileName ? `<p class="existing-file">Поточний файл: ${escapeHtml(item.managerFileName)}</p>` : ''}</div>
        <div class="form-row"><label class="form-element">Коментар дизайнеру</label><textarea class="text-input order-comment" name="items[${index}][designerComment]">${escapeHtml(item.designerComment || '')}</textarea></div>
        <div class="form-row"><label class="form-element">Коментар друкарю</label><textarea class="text-input order-comment" name="items[${index}][printerComment]">${escapeHtml(item.printerComment || '')}</textarea></div>
        <div class="form-row"><label class="form-element">Коментар постобробнику</label><textarea class="text-input order-comment" name="items[${index}][postworkerComment]">${escapeHtml(item.postworkerComment || '')}</textarea></div>`;
      const printSelect = area.querySelector('.print-type');
      fillSelect(printSelect, printTypes, 'Оберіть тип друку', item.printType || '');
      const preparationSelect = area.querySelector('.preparation');
      fillSelect(preparationSelect, preparations, 'Не потрібна', item.preparation || '');
      const materialSelect = area.querySelector('.material-name');
      fillSelect(materialSelect, Object.keys(materials), 'Оберіть матеріал', item.materialName || '');
      const postworkBox = area.querySelector('.order-postworks');
      const fileInput = area.querySelector('.order-file');
      const fileDrop = area.querySelector('.order-file-drop');
      area.querySelector('.order-file-trigger').addEventListener('click', () => fileInput.click());
      fileInput.addEventListener('change', () => { area.querySelector('.order-file-name').textContent = fileInput.files[0]?.name || 'Файл не обрано'; });
      fileDrop.addEventListener('dragover', event => { event.preventDefault(); fileDrop.classList.add('dragover'); });
      fileDrop.addEventListener('dragleave', () => fileDrop.classList.remove('dragover'));
      fileDrop.addEventListener('drop', event => { event.preventDefault(); fileDrop.classList.remove('dragover'); fileInput.files = event.dataTransfer.files; fileInput.dispatchEvent(new Event('change')); });
      postworks.forEach((postwork, postworkIndex) => {
        const label = document.createElement('label');
        label.className = 'postwork-option';
        const input = document.createElement('input');
        input.type = 'checkbox'; input.name = `items[${index}][postworks][]`; input.value = postwork; input.checked = (item.postworks || []).includes(postwork);
        label.append(input, document.createTextNode(postwork)); postworkBox.appendChild(label);
      });
      const parametersBox = area.querySelector('.material-parameters');
      function renderParameters() {
        parametersBox.replaceChildren();
        const material = materials[materialSelect.value];
        if (!material) return;
        Object.entries(material.parameters).forEach(([parameterName, values], parameterIndex) => {
          const row = document.createElement('div'); row.className = 'form-row';
          const label = document.createElement('label'); label.className = 'form-element'; label.textContent = parameterName;
          const select = document.createElement('select'); select.className = 'text-input'; select.name = `items[${index}][parameters][${parameterIndex}]`; select.required = true;
          const parameterNameInput = document.createElement('input'); parameterNameInput.type = 'hidden'; parameterNameInput.name = `items[${index}][parameterNames][${parameterIndex}]`; parameterNameInput.value = parameterName;
          fillSelect(select, values, `Оберіть ${parameterName}`, item.parameters?.[parameterName] || '');
          row.append(label, select, parameterNameInput); parametersBox.appendChild(row);
        });
      }
      materialSelect.addEventListener('change', () => { item.parameters = {}; renderParameters(); });
      renderParameters();
      area.querySelector('.remove-order-item').addEventListener('click', () => { area.remove(); renumberItems(); });
      itemContainer.appendChild(area);
    }
    function escapeHtml(value) { const div = document.createElement('div'); div.textContent = value; return div.innerHTML; }
    function renumberItems() {
      [...itemContainer.querySelectorAll('.order-item-editor')].forEach((area, index) => {
        area.querySelector('h3').textContent = `Позиція ${index + 1}`;
        area.querySelectorAll('[name]').forEach(input => input.name = input.name.replace(/items\[\d+\]/, `items[${index}]`));
      });
    }
    document.getElementById('add-order-item').addEventListener('click', () => createItem(itemContainer.children.length));
    (initialItems.length ? initialItems : [{}]).forEach((item, index) => createItem(index, item));
    customerSearch.addEventListener('input', () => {
      customerId.value = '';
      const query = customerSearch.value.trim().toLocaleLowerCase();
      suggestions.replaceChildren();
      if (!query) { suggestions.hidden = true; return; }
      const matches = customers.filter(customer => `${customer.name} ${customer.phone}`.toLocaleLowerCase().includes(query)).slice(0, 8);
      matches.forEach(customer => {
        const button = document.createElement('button'); button.type = 'button'; button.textContent = customer.phone ? `${customer.name} · ${customer.phone}` : customer.name;
        button.addEventListener('click', () => { customerSearch.value = customer.name; customerId.value = customer.id; customerSearch.setCustomValidity(''); suggestions.hidden = true; });
        suggestions.appendChild(button);
      });
      suggestions.hidden = matches.length === 0;
    });
    document.addEventListener('click', event => { if (!event.target.closest('.customer-autocomplete')) suggestions.hidden = true; });
    document.getElementById('order-form').addEventListener('submit', event => {
      if (!customerId.value) { event.preventDefault(); customerSearch.setCustomValidity('Оберіть клієнта зі списку.'); customerSearch.reportValidity(); }
    });
    customerSearch.addEventListener('input', () => customerSearch.setCustomValidity(''));
    document.getElementById('header-logo').onclick = () => location.href = 'main.php';
    document.getElementById('cancel-button').onclick = () => location.href = 'orders.php';
  </script>
  <script src="scripts/chat-notifications.js" defer></script>
</body>
</html>
