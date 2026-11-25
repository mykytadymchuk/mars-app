<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;

$client = new MongoDB\Client("mongodb://mongodb:27017");

$db = $client->selectDatabase("mars");

$usersCollection = $db->selectCollection("users");
$suppliersCollection = $db->selectCollection("suppliers");
$materialsCollection = $db->selectCollection("materials");
$ordersCollection = $db->selectCollection("orders");
$filesCollection = $db->selectCollection("files");
$incomesCollection = $db->selectCollection("incomes");
$employeesCollection = $db->selectCollection("employees");
$rolesCollection = $db->selectCollection("roles");
$customersCollection = $db->selectCollection("customers");
$stockCollection = $db->selectCollection("stock");
