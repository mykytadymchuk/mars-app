<?php
session_start();
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
	header("Location: login.php");
	exit;
}
require_once __DIR__ . '/../services/databaseConnect.php';
$user = $usersCollection->findOne(["Логін" => $_SESSION["username"]]);
if (!$user) {
	session_destroy();
	header("Location: login.php");
	exit;
}
$error = $_GET["error"] ?? null;



$roles = $rolesCollection->find()->toArray();
$defaultRoles = ["Менеджер", "Дизайнер", "Бухгалтер", "Друкар", "Макетник"];
if (empty($roles)) {
	$roles = array_map(fn($role) => ["Назва" => $role], $defaultRoles);
}
$messages = ["empty_name" => "Введіть прізвище та ім'я працівника.", "invalid_role" => "Оберіть роль працівника.", "incorrect_phone_number_format" => "Некоректний формат номера телефону.", "employee_exists" => "Працівник з таким номером телефону вже існує.", "unknown_error" => "Працівника не було додано. Сталася невідома помилка."];
?>



<?php
$messages['empty_name'] = 'Введіть назву матеріалу.';
$messages['invalid_type'] = 'Оберіть тип матеріалу.';
$messages['invalid_parameter'] = 'Кожен параметр має містити назву та хоча б одне значення.';
$messages['unknown_error'] = 'Матеріал не вдалося додати. Спробуйте ще раз.';
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
	<link rel="stylesheet" href="styles/form-style.css" />
	<link rel="stylesheet" href="styles/general-style.css" />
	<title>Додавання матеріалу</title>
  <link rel="stylesheet" href="styles/responsive.css" />
</head>

<body>
	<div class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
		<div class="header-right-corner">
			<p id="main-greetings">Вітаю, <?= htmlspecialchars($_SESSION["username"]) ?>!</p>
		</div>
	</div>
	<div class="main-body">
		<p class="page-title">ДОДАВАННЯ МАТЕРІАЛУ</p>
		<form action="services/addMaterialProcess.php" method="post">
			<div class="form-row"><label class="form-element" for="employeeLastName">Назва матеріалу</label><input type="text"
					name="materialName" class="text-input" id="materialName" required /></div>
			<div class="form-row"><label class="form-element" for="employeeFirstName">Тип матеріалу</label>
				<div class="radio-options">
					<label>
						<input type="radio" name="materialType" value="roll" checked />Рулонний
					</label>
					<label>
						<input type="radio" name="materialType" value="sheet" />Листовий
					</label>
				</div>
			</div>
			<div class="form-row"><label class="form-element">Додаткові поля</label>
				<div id="custom-fields-container">

					<div class="custom-field-row">
						<div class="custom-field-cols-container">
							<div class="custom-field-cols-element">
								<p class="form-element">Параметр</p>
								<input name="customFieldName[]" class="text-input custom-field-name" value=""
									placeholder="Назва поля" />
							</div>
							<div class="custom-field-cols-element">
								<p class="form-element">Значення</p>
								<input name="customFieldValue[]" class="text-input custom-field-value" value=""
									placeholder="Значення" />
							</div>
							<div class="custom-field-cols-element">
								<button type="button" class="remove-custom-field">×</button>
							</div>
						</div>
					</div>
					<div class="custom-field-row">
						<div class="custom-field-cols-container">
							<div class="custom-field-cols-element">
								<p class="form-element">Параметр</p>
								<input name="customFieldName[]" class="text-input custom-field-name" value=""
									placeholder="Назва поля" />
							</div>
							<div class="custom-field-cols-element">
								<p class="form-element">Значення</p>
								<input name="customFieldValue[]" class="text-input custom-field-value" value=""
									placeholder="Значення" />
							</div>
							<div class="custom-field-cols-element">
								<button type="button" class="remove-custom-field">×</button>
							</div>
						</div>
					</div>

				</div>
				<button type="button" id="add-custom-field" class="add-custom-field-button">+ Додати поле</button>
			</div>
			<div class="form-buttons"><input type="submit" value="Додати" class="submit-button" /><button type="button"
					id="cancel-button">Скасувати</button></div>
		</form>
	</div>
	<?php if (isset($messages[$error])): ?>
		<script>alert(<?= json_encode($messages[$error]) ?>); window.history.replaceState({}, document.title, window.location.pathname);</script>
	<?php endif; ?>
	<script>document.getElementById("header-logo").addEventListener("click", () => window.location.href = "main.php"); document.getElementById("cancel-button").addEventListener("click", () => window.location.href = "materials.php");</script>
	<script>
		const add = document.getElementById("add-custom-field"), container = document.getElementById("custom-fields-container");
		add.onclick = () => {
			const row = document.createElement("div");
			row.className = "custom-field-row";
			row.innerHTML = '<input name="customFieldName[]" class="text-input custom-field-name" placeholder="Назва поля" /><input name="customFieldValue[]" class="text-input custom-field-value" placeholder="Значення" /><button type="button" class="remove-custom-field">×</button>';
			container.appendChild(row)
		};
		container.onclick = e => {
			if (e.target.classList.contains("remove-custom-field")) e.target.closest(".custom-field-row").remove()
		};
	</script>
	<script>
		const materialFields = document.getElementById('custom-fields-container');
		const materialForm = materialFields.closest('form');

		function materialValueInput() {
			const valueRow = document.createElement('div');
			valueRow.className = 'custom-field-value-row';
			valueRow.innerHTML = '<input class="text-input custom-field-value" placeholder="Значення" /><button type="button" class="remove-custom-field-value" aria-label="Видалити значення">×</button>';
			return valueRow;
		}

		function materialFieldRow() {
			const row = document.createElement('div');
			row.className = 'custom-field-row';
			row.innerHTML = '<div class="custom-field-cols-container"><div class="custom-field-cols-element"><p class="form-element">Параметр</p><input class="text-input custom-field-name" placeholder="Назва поля" /></div><div class="custom-field-cols-element values-column"><p class="form-element">Значення</p><div class="custom-field-values"></div><button type="button" class="add-custom-field-value">+ Додати значення</button></div><div class="custom-field-cols-element"><button type="button" class="remove-custom-field" aria-label="Видалити параметр">×</button></div></div>';
			row.querySelector('.custom-field-values').appendChild(materialValueInput());
			return row;
		}

		materialFields.replaceChildren(materialFieldRow());
		document.getElementById('add-custom-field').onclick = () => materialFields.appendChild(materialFieldRow());
		materialFields.onclick = event => {
			if (event.target.classList.contains('remove-custom-field')) event.target.closest('.custom-field-row').remove();
			if (event.target.classList.contains('add-custom-field-value')) event.target.closest('.custom-field-row').querySelector('.custom-field-values').appendChild(materialValueInput());
			if (event.target.classList.contains('remove-custom-field-value')) event.target.closest('.custom-field-value-row').remove();
		};
		materialForm.addEventListener('submit', () => {
			materialFields.querySelectorAll('.custom-field-row').forEach((row, index) => {
				row.querySelector('.custom-field-name').name = `customFieldName[${index}]`;
				row.querySelectorAll('.custom-field-value').forEach(input => input.name = `customFieldValue[${index}][]`);
			});
		});
	</script>
  <script src="scripts/chat-notifications.js" defer></script>
</body>

</html>
