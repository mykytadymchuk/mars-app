<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}
require_once __DIR__ . '/../../services/databaseConnect.php';
require_once __DIR__ . '/chatFunctions.php';

$currentUser = $usersCollection->findOne(['Логін' => $_SESSION['username']]);
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}
$action = (string) ($_GET['action'] ?? '');
if ($action === 'unread') {
    $unreadTotal = 0;
    $latestIncoming = null;
    foreach ($chatsCollection->find()->toArray() as $item) {
        if (!canAccessChat($item, $currentUser)) continue;
        $count = unreadCountForChat($item, $currentUser);
        $unreadTotal += $count;
        if ($count === 0) continue;
        $messages = $item['messages'] ?? [];
        for ($index = count($messages) - 1; $index >= 0; $index--) {
            $message = $messages[$index];
            if ((string) ($message['senderId'] ?? '') === (string) $currentUser['_id']) continue;
            $time = chatMessageDate($message['sentAt'] ?? null);
            if ($latestIncoming === null || $time > $latestIncoming['sortTime']) {
                $latestIncoming = ['sortTime' => $time, 'sender' => (string) ($message['senderName'] ?? 'Користувач'), 'text' => (string) ($message['text'] ?? ''), 'chat' => (string) ($item['name'] ?? 'Нове повідомлення'), 'key' => (string) ($item['key'] ?? 'general')];
            }
            break;
        }
    }
    echo json_encode(['unreadTotal' => $unreadTotal, 'latest' => $latestIncoming], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
$chatKey = (string) ($_POST['chat'] ?? $_GET['chat'] ?? '');
$chat = $chatsCollection->findOne(['key' => $chatKey]);
if (!$chat) {
    http_response_code(404);
    echo json_encode(['error' => 'chat_not_found']);
    exit;
}
if (!canAccessChat($chat, $currentUser)) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}
markChatRead($chatsCollection, $chat, $currentUser);
$chat = $chatsCollection->findOne(['_id' => $chat['_id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim((string) ($_POST['message'] ?? ''));
    $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    if ($text === '' || $length > 4000) {
        http_response_code(422);
        echo json_encode(['error' => 'invalid_message']);
        exit;
    }
    $chatsCollection->updateOne(['_id' => $chat['_id']], ['$push' => ['messages' => [
        'senderId' => $currentUser['_id'],
        'senderName' => (string) $currentUser['Логін'],
        'text' => $text,
        'sentAt' => new MongoDB\BSON\UTCDateTime()
    ]], '$set' => ['updatedAt' => new MongoDB\BSON\UTCDateTime()]]);
    incrementChatUnreadForRecipients($chat, $currentUser, $usersCollection, $chatsCollection);
    $chat = $chatsCollection->findOne(['_id' => $chat['_id']]);
}

$users = $usersCollection->find()->toArray();
$userById = [];
$employeeByUserId = [];
foreach ($users as $user) {
    $userById[(string) $user['_id']] = $user;
    if (!empty($user['Працівник ID'])) {
        $employee = $employeesCollection->findOne(['_id' => $user['Працівник ID']]);
        if ($employee) $employeeByUserId[(string) $user['_id']] = $employee;
    }
}
$titleFor = static function ($item) use ($currentUser, $userById, $employeeByUserId): string {
    if (($item['type'] ?? '') !== 'direct') return (string) ($item['name'] ?? 'Чат');
    foreach (chatMembers($item) as $memberId) {
        if ((string) $memberId === (string) $currentUser['_id']) continue;
        $employee = $employeeByUserId[(string) $memberId] ?? null;
        if ($employee) {
            $name = trim((string) ($employee['Прізвище'] ?? '') . ' ' . (string) ($employee['Ім\'я'] ?? ''));
            if ($name !== '') return $name;
        }
        return (string) ($userById[(string) $memberId]['Логін'] ?? 'Користувач');
    }
    return 'Особистий чат';
};
$photoFor = static function ($item) use ($currentUser, $employeeByUserId): string {
    if (($item['type'] ?? '') !== 'direct') return 'src/no-profile-photo.png';
    foreach (chatMembers($item) as $memberId) {
        if ((string) $memberId === (string) $currentUser['_id']) continue;
        $employee = $employeeByUserId[(string) $memberId] ?? null;
        if ($employee && !empty($employee['Фото'])) return 'src/employees/' . rawurlencode((string) $employee['Фото']);
    }
    return 'src/no-profile-photo.png';
};
$messagePayload = static function ($message) use ($currentUser, $employeeByUserId): array {
    $senderId = (string) ($message['senderId'] ?? '');
    $employee = $employeeByUserId[$senderId] ?? null;
    $photo = $employee && !empty($employee['Фото']) ? 'src/employees/' . rawurlencode((string) $employee['Фото']) : 'src/no-profile-photo.png';
    return [
        'senderName' => $senderId === (string) $currentUser['_id'] ? 'Ви' : (string) ($message['senderName'] ?? 'Користувач'),
        'mine' => $senderId === (string) $currentUser['_id'],
        'photo' => $photo,
        'text' => (string) ($message['text'] ?? ''),
        'time' => chatMessageTime($message['sentAt'] ?? null)
    ];
};

$allMessages = $chat['messages'] ?? [];
$after = max(0, (int) ($_GET['after'] ?? $_POST['after'] ?? 0));
$newMessages = [];
for ($i = $after, $count = count($allMessages); $i < $count; $i++) $newMessages[] = $messagePayload($allMessages[$i]);

$chatList = [];
foreach ($chatsCollection->find()->toArray() as $item) {
    if (!canAccessChat($item, $currentUser)) continue;
    $latest = chatLatestMessage($item);
    $chatList[] = [
        'key' => (string) ($item['key'] ?? ''),
        'name' => $titleFor($item),
        'photo' => $photoFor($item),
        'preview' => (string) ($latest['text'] ?? 'Повідомлень ще немає'),
        'time' => $latest ? chatMessageTime($latest['sentAt'] ?? null) : '',
        'sortTime' => chatMessageDate($latest['sentAt'] ?? null),
        'unreadCount' => unreadCountForChat($item, $currentUser)
    ];
}
usort($chatList, static fn($left, $right) => $right['sortTime'] <=> $left['sortTime']);
echo json_encode(['messages' => $newMessages, 'messageCount' => count($allMessages), 'chats' => $chatList], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
