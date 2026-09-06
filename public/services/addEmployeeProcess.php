<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: ../login.php'); exit; }

require_once __DIR__ . '/../../services/databaseConnect.php';
require_once __DIR__ . '/functions.php';

use MongoDB\BSON\ObjectId;

$lastName = trim($_POST['employeeLastName'] ?? '');
$firstName = trim($_POST['employeeFirstName'] ?? '');
$phone = trim($_POST['employeePhone'] ?? '');
$role = trim($_POST['employeeRole'] ?? '');

if ($lastName === '' || $firstName === '') { header('Location: ../addEmployee.php?error=empty_name'); exit; }
if ($role === '') { header('Location: ../addEmployee.php?error=invalid_role'); exit; }
if (!preg_match('/^\+380\d{9}$/', $phone)) { header('Location: ../addEmployee.php?error=incorrect_phone_number_format'); exit; }

try {
    $defaultRoles = ['Менеджер', 'Дизайнер', 'Бухгалтер', 'Друкар', 'Макетник'];
    if ($rolesCollection->countDocuments() === 0) $rolesCollection->insertMany(array_map(fn ($name) => ['Назва' => $name], $defaultRoles));
    if (!$rolesCollection->findOne(['Назва' => $role])) { header('Location: ../addEmployee.php?error=invalid_role'); exit; }
    if ($employeesCollection->findOne(['Номер телефону' => $phone])) { header('Location: ../addEmployee.php?error=employee_exists'); exit; }

    $employeeId = new ObjectId();
    $username = generateEmployeeUsername($lastName, $firstName, $usersCollection);
    $firstPassword = generateEmployeePassword();
    $today = new DateTimeImmutable('today', new DateTimeZone('Europe/Kyiv'));

    $employeesCollection->insertOne([
        '_id' => $employeeId,
        'Прізвище' => $lastName,
        'Ім\'я' => $firstName,
        'Роль' => $role,
        'Номер телефону' => $phone,
        'Дата прийняття на роботу' => $today->format('Y-m-d'),
        'Статус' => 'Працює',
        'Логін' => $username,
        'Перший пароль' => $firstPassword
    ]);

    try {
        $usersCollection->insertOne([
            'Логін' => $username,
            'Пароль' => password_hash($firstPassword, PASSWORD_DEFAULT),
            'Роль' => $role,
            'Працівник ID' => $employeeId
        ]);
    } catch (Exception $e) {
        $employeesCollection->deleteOne(['_id' => $employeeId]);
        throw $e;
    }

    header('Location: ../employees.php');
    exit;
} catch (Exception $e) {
    header('Location: ../addEmployee.php?error=unknown_error');
    exit;
}
