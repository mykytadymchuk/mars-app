<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../services/databaseConnect.php';
use MongoDB\BSON\ObjectId;

function addIncomeText($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
$editIncome = null;
$editId = $_GET['id'] ?? '';
if ($editId !== '') {
    try { $editIncome = $incomesCollection->findOne(['_id' => new ObjectId($editId)]); } catch (Throwable $exception) { header('Location: incomes.php'); exit; }
    if (!$editIncome) { header('Location: incomes.php'); exit; }
}
$suppliers = $suppliersCollection->find([], ['sort' => ['Постачальник' => 1]])->toArray();
$materials = $materialsCollection->find([], ['sort' => ['name' => 1]])->toArray();
$materialData = [];
foreach ($materials as $material) {
    $name = trim((string) ($material['name'] ?? ''));
    if ($name === '') continue;
    if (!isset($materialData[$name])) $materialData[$name] = ['name' => $name, 'parameters' => []];
    foreach (($material['parameters'] ?? []) as $parameter) {
        $parameterName = trim((string) ($parameter['name'] ?? ''));
        if ($parameterName === '') continue;
        if (!isset($materialData[$name]['parameters'][$parameterName])) $materialData[$name]['parameters'][$parameterName] = [];
        foreach (($parameter['values'] ?? []) as $value) {
            $value = trim((string) $value);
            if ($value !== '') $materialData[$name]['parameters'][$parameterName][$value] = $value;
        }
    }
}
foreach ($materialData as &$material) {
    $parameters = [];
    foreach ($material['parameters'] as $parameterName => $values) $parameters[] = ['name' => $parameterName, 'values' => array_values($values)];
    $material['parameters'] = $parameters;
}
unset($material);
$error = $_GET['error'] ?? '';
$messages = ['missing_supplier' => 'Оберіть постачальника.', 'missing_material' => 'Оберіть матеріал.', 'invalid_parameters' => 'Оберіть значення усіх параметрів.', 'invalid_date' => 'Вкажіть коректну дату.', 'invalid_quantity' => 'Вкажіть кількість.', 'unknown_error' => 'Надходження не вдалося додати.'];
?>
<!doctype html><html lang="uk"><head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" /><link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" /><link rel="stylesheet" href="styles/form-style.css" /><link rel="stylesheet" href="styles/general-style.css" /><title>Додавання надходження</title></head>
<body><div class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" /><div class="header-right-corner"><p id="main-greetings">Вітаю, <?= addIncomeText($_SESSION['username']) ?>!</p></div></div><div class="main-body"><p class="page-title">ДОДАВАННЯ НАДХОДЖЕННЯ</p><form action="services/addIncomeProcess.php" method="post"><div class="form-row"><label class="form-element" for="supplierId">Постачальник</label><select name="supplierId" id="supplierId" class="text-input" required><option value="">Оберіть постачальника</option><?php foreach ($suppliers as $supplier): ?><option value="<?= addIncomeText((string) $supplier['_id']) ?>"><?= addIncomeText($supplier['Постачальник'] ?? '') ?></option><?php endforeach; ?></select></div><div class="form-row"><label class="form-element" for="materialId">Матеріал</label><select name="materialName" id="materialId" class="text-input" required><option value="">Оберіть матеріал</option><?php foreach ($materialData as $material): ?><option value="<?= addIncomeText($material['name']) ?>"><?= addIncomeText($material['name']) ?></option><?php endforeach; ?></select></div><div id="material-parameters" class="income-parameters"></div><div class="form-row"><label class="form-element" for="quantity">Кількість</label><input type="number" min="1" step="1" name="quantity" id="quantity" class="text-input" required /></div><div class="form-row"><label class="form-element" for="incomeDate">Дата надходження</label><input type="date" name="incomeDate" id="incomeDate" class="text-input" value="<?= (new DateTimeImmutable('today', new DateTimeZone('Europe/Kyiv')))->format('Y-m-d') ?>" required /></div><div class="form-buttons"><input type="submit" value="Додати" class="submit-button" /><button type="button" id="cancel-button">Скасувати</button></div></form></div><?php if (isset($messages[$error])): ?><script>alert(<?= json_encode($messages[$error]) ?>); history.replaceState({}, document.title, location.pathname);</script><?php endif; ?><script>
const materials=<?= json_encode($materialData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,materialSelect=document.getElementById('materialId'),parameterBox=document.getElementById('material-parameters'),editIncome=<?= json_encode($editIncome, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
materialSelect.onchange=()=>{parameterBox.replaceChildren();const material=materials[materialSelect.value];if(!material)return;material.parameters.forEach((parameter,index)=>{const row=document.createElement('div');row.className='form-row';const label=document.createElement('label');label.className='form-element';label.textContent=parameter.name;const select=document.createElement('select');select.name=`parameters[${index}]`;select.className='text-input';select.required=true;select.innerHTML='<option value="">Оберіть значення</option>';parameter.values.forEach(value=>{const option=document.createElement('option');option.value=value;option.textContent=value;select.appendChild(option);});const parameterName=document.createElement('input');parameterName.type='hidden';parameterName.name=`parameterNames[${index}]`;parameterName.value=parameter.name;row.append(label,select,parameterName);parameterBox.appendChild(row);});};if(editIncome){document.querySelector('.page-title').textContent='РЕДАГУВАННЯ НАДХОДЖЕННЯ';document.querySelector('.submit-button').value='Змінити';document.getElementById('supplierId').value=editIncome.supplierId?.$oid||editIncome.supplierId||'';materialSelect.value=editIncome.materialName||'';materialSelect.dispatchEvent(new Event('change'));const savedValues=Object.fromEntries((editIncome.parameters||[]).map(parameter=>[parameter.name,parameter.value]));parameterBox.querySelectorAll('select').forEach((select,index)=>{const name=parameterBox.querySelector(`input[name="parameterNames[${index}]"]`).value;select.value=savedValues[name]||'';});document.getElementById('quantity').value=editIncome.quantity||'';document.getElementById('incomeDate').value=editIncome.incomeDate||'';const id=document.createElement('input');id.type='hidden';id.name='incomeId';id.value=<?= json_encode($editId) ?>;document.querySelector('form[action="services/addIncomeProcess.php"]').appendChild(id);}document.getElementById('header-logo').onclick=()=>location.href='main.php';document.getElementById('cancel-button').onclick=()=>location.href='incomes.php';
</script></body></html>
