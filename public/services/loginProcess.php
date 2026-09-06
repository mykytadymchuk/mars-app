<?php

session_start();

require_once __DIR__ . '/../../services/databaseConnect.php';

$username = trim($_POST["username"] ?? "");
$password = trim($_POST["password"] ?? "");

$user = $usersCollection->findOne([
    "Логін" => $username
]);

if ($user && password_verify($password, $user["Пароль"])) {

    $employeeQuery = ["Логін" => $user["Логін"]];
    if (isset($user["Працівник ID"])) {
        $employeeQuery = ['$or' => [
            ["_id" => $user["Працівник ID"]],
            ["Логін" => $user["Логін"]]
        ]];
    }

    $employee = $employeesCollection->findOne($employeeQuery);
    if ($employee && (($employee["Статус"] ?? "Працює") === "Звільнено")) {
        header("Location: /../login.php?error=invalid_credentials");
        exit;
    }

    session_regenerate_id(true);
    $_SESSION["logged_in"] = true;
    $_SESSION["username"] = $user["Логін"];
    $_SESSION["role"] = $user["Роль"];

    header("Location: /../main.php");
    exit;

} else {

    header("Location: /../login.php?error=invalid_credentials");
    exit;
}
