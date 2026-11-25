<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;

$client = new MongoDB\Client("mongodb://mongodb:27017");

$dbName = "mars";
$usersCollection = "users";
$suppliersCollection = "suppliers";
$materialsCollection = "materials";
$ordersCollection = "orders";
$filesCollection = "files";
$incomesCollection = "incomes";
$employeesCollection = "employees";
$rolesCollection = "roles";
$customersCollection = "customers";
$stockCollection = "stock";
$chatsCollection = "chats";

// Check whether database exists
$databaseExists = false;

foreach ($client->listDatabases() as $database) {
    if ($database->getName() === $dbName) {
        $databaseExists = true;
        break;
    }
}

if (!$databaseExists) {

    // Select/create database
    $db = $client->selectDatabase($dbName);

    // Create users collection
    $db->createCollection($usersCollection);
    $db->createCollection($suppliersCollection);
    $db->createCollection($materialsCollection);
    $db->createCollection($ordersCollection);
    $db->createCollection($filesCollection);
    $db->createCollection($incomesCollection);
    $db->createCollection($employeesCollection);
    $db->createCollection($rolesCollection);
    $db->createCollection($customersCollection);
    $db->createCollection($stockCollection);
    $db->createCollection($chatsCollection);

    // Get users collection
    $users = $db->selectCollection($usersCollection);

    // Create default admin
    $passwordHash = password_hash("root", PASSWORD_DEFAULT);

    $users->insertOne([
        "Логін" => "admin",
        "Пароль" => $passwordHash,
        "Роль" => "Адміністратор"
    ]);

    $db->selectCollection($rolesCollection)->insertMany([
        ["Назва" => "Менеджер"],
        ["Назва" => "Дизайнер"],
        ["Назва" => "Бухгалтер"],
        ["Назва" => "Друкар"],
        ["Назва" => "Макетник"]
    ]);

    echo "Database '$dbName' created successfully.<br>";
    echo "Collection '$usersCollection' created successfully.<br>";
    echo "Collection '$suppliersCollection' created successfully.<br>";
    echo "Collection '$materialsCollection' created successfully.<br>";
    echo "Collection '$ordersCollection' created successfully.<br>";
    echo "Collection '$filesCollection' created successfully.<br>";
    echo "Collection '$incomesCollection' created successfully.<br>";
    echo "Collection '$employeesCollection' created successfully.<br>";
    echo "Collection '$rolesCollection' created successfully.<br>";
    echo "Collection '$customersCollection' created successfully.<br>";
    echo "Collection '$stockCollection' created successfully.<br>";
    echo "Collection '$chatsCollection' created successfully.<br>";
    echo "Admin user created successfully.<br>";

} else {

    echo "Database '$dbName' already exists. Nothing was changed.<br>";
}
