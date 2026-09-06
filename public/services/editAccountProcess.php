<?php

session_start();

require_once __DIR__ . '/../../services/databaseConnect.php';

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: /../login.php");
    exit;
}

$newPassword = $_POST["password"] ?? "";

if ($newPassword === "") {
    header("Location: /../editAccount.php?error=empty_password");
    exit;
}


$currentUsername = $_SESSION["username"];

$user = $usersCollection->findOne([
    "Логін" => $currentUsername
]);

if (!$user) {
    session_destroy();

    header("Location: /../login.php");
    exit;
}

$newPasswordHash = password_hash(
    $newPassword,
    PASSWORD_DEFAULT
);

$result = $usersCollection->updateOne(
    [
        "_id" => $user["_id"]
    ],
    [
        '$set' => [
            "Пароль" => $newPasswordHash
        ]
    ]
);

if ($result->getMatchedCount() === 1) {
    header("Location: /../editAccount.php?error=success");
    exit;
}

header("Location: /../editAccount.php?error=unknown_error");
exit;

?>