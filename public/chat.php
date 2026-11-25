<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../services/databaseConnect.php';
require_once __DIR__ . '/services/chatFunctions.php';

$currentUser = $usersCollection->findOne(['Логін' => $_SESSION['username']]);
if (!$currentUser) {
    session_destroy();
    header('Location: login.php');
    exit;
}

ensureDirectChatsForUser($currentUser, $usersCollection, $chatsCollection);
$roomDefinitions = [['key' => 'general', 'type' => 'general', 'name' => 'Загальний чат']];
foreach ($rolesCollection->find([], ['sort' => ['Назва' => 1]]) as $role) {
    $roleName = trim((string) ($role['Назва'] ?? ''));
    if ($roleName !== '') $roomDefinitions[] = ['key' => 'role:' . $roleName, 'type' => 'role', 'name' => 'Чат ролі "' . $roleName . '"', 'role' => $roleName];
}
foreach ($roomDefinitions as $room) {
    if (!$chatsCollection->findOne(['key' => $room['key']])) {
        $chatsCollection->insertOne($room + ['messages' => [], 'createdAt' => new MongoDB\BSON\UTCDateTime()]);
    }
}

$chatSearch = trim((string) ($_GET['search'] ?? ''));
$requestedKey = (string) ($_GET['chat'] ?? 'general');
$requestedChat = $chatsCollection->findOne(['key' => $requestedKey]);
if ($requestedChat && canAccessChat($requestedChat, $currentUser)) markChatRead($chatsCollection, $requestedChat, $currentUser);
$allChats = $chatsCollection->find()->toArray();
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

$chatTitle = static function ($chat) use ($currentUser, $userById, $employeeByUserId): string {
    if (($chat['type'] ?? '') !== 'direct') return (string) ($chat['name'] ?? 'Чат');
    foreach (($chat['members'] ?? []) as $memberId) {
        if ((string) $memberId === (string) $currentUser['_id']) continue;
        $other = $userById[(string) $memberId] ?? null;
        if (!$other) continue;
        $employee = $employeeByUserId[(string) $memberId] ?? null;
        if ($employee) {
            $employeeName = trim((string) ($employee['Прізвище'] ?? '') . ' ' . (string) ($employee['Ім\'я'] ?? ''));
            if ($employeeName !== '') return $employeeName;
        }
        return (string) ($other['Логін'] ?? 'Користувач');
    }
    return 'Особистий чат';
};
$chatPhoto = static function ($chat) use ($currentUser, $employeeByUserId): string {
    if (($chat['type'] ?? '') !== 'direct') return 'src/no-profile-photo.png';
    foreach (($chat['members'] ?? []) as $memberId) {
        if ((string) $memberId === (string) $currentUser['_id']) continue;
        $employee = $employeeByUserId[(string) $memberId] ?? null;
        if ($employee && !empty($employee['Фото'])) return 'src/employees/' . rawurlencode((string) $employee['Фото']);
    }
    return 'src/no-profile-photo.png';
};
$visibleChats = [];
foreach ($allChats as $chat) {
    if (!canAccessChat($chat, $currentUser)) continue;
    $title = $chatTitle($chat);
    $latest = chatLatestMessage($chat);
    $preview = $latest['text'] ?? 'Повідомлень ще немає';
    $searchMatch = function_exists('mb_stripos') ? mb_stripos($title . ' ' . $preview, $chatSearch, 0, 'UTF-8') !== false : stripos($title . ' ' . $preview, $chatSearch) !== false;
    if ($chatSearch !== '' && !$searchMatch) continue;
    $chat['_displayName'] = $title;
    $chat['_photo'] = $chatPhoto($chat);
    $chat['_latest'] = $latest;
    $chat['_unreadCount'] = unreadCountForChat($chat, $currentUser);
    $visibleChats[] = $chat;
}
usort($visibleChats, static function ($left, $right) {
    return chatMessageDate($right['_latest']['sentAt'] ?? null) <=> chatMessageDate($left['_latest']['sentAt'] ?? null);
});
$activeChat = null;
foreach ($allChats as $chat) {
    if (($chat['key'] ?? '') !== $requestedKey) continue;
    if (!canAccessChat($chat, $currentUser)) continue;
    $activeChat = $chat;
    break;
}
if (!$activeChat) {
    foreach ($allChats as $chat) if (($chat['key'] ?? '') === 'general') { $activeChat = $chat; break; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $messageText = trim((string) ($_POST['message'] ?? ''));
    $postKey = (string) ($_POST['chat'] ?? '');
    $chatToSend = $chatsCollection->findOne(['key' => $postKey]);
    $allowed = $chatToSend && canAccessChat($chatToSend, $currentUser);
    $messageLength = function_exists('mb_strlen') ? mb_strlen($messageText, 'UTF-8') : strlen($messageText);
    if ($allowed && $messageText !== '' && $messageLength <= 4000) {
        $message = ['senderId' => $currentUser['_id'], 'senderName' => (string) $currentUser['Логін'], 'text' => $messageText, 'sentAt' => new MongoDB\BSON\UTCDateTime()];
        $chatsCollection->updateOne(['_id' => $chatToSend['_id']], ['$push' => ['messages' => $message], '$set' => ['updatedAt' => new MongoDB\BSON\UTCDateTime()]]);
        incrementChatUnreadForRecipients($chatToSend, $currentUser, $usersCollection, $chatsCollection);
    }
    header('Location: chat.php?chat=' . rawurlencode($postKey) . ($chatSearch !== '' ? '&search=' . rawurlencode($chatSearch) : ''));
    exit;
}

$activeTitle = $activeChat ? $chatTitle($activeChat) : 'Загальний чат';
$activePhoto = $activeChat ? $chatPhoto($activeChat) : 'src/no-profile-photo.png';
$activeMessages = $activeChat['messages'] ?? [];
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles/general-style.css" />
  <link rel="stylesheet" href="styles/search-bar-style.css" />
  <link rel="stylesheet" href="styles/page-header-style.css" />
  <link rel="stylesheet" href="styles/chat-style.css" />
  <title>Чат</title>
  <link rel="stylesheet" href="styles/responsive.css" />
</head>
<body>
  <div class="main-header"><img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
    <div class="header-right-corner"><p id="main-greetings">Вітаю, <?= chatEscape($_SESSION['username']) ?>!</p></div>
  </div>
  <div class="main-body"><div class="chat-general-container">
    <div class="chats-list-container">
      <div class="chat-search-container"><form method="get" action="chat.php" class="search-bar-container">
        <input type="hidden" name="chat" value="<?= chatEscape($activeChat['key'] ?? 'general') ?>" />
        <input type="text" name="search" id="chat-search" class="search-bar" placeholder="Пошук" value="<?= chatEscape($chatSearch) ?>" />
        <button class="search-button"><img src="src/search.png" alt="search-button" draggable="false" /></button>
      </form></div>
      <div class="chat-list">
        <?php foreach ($visibleChats as $index => $chat): ?>
          <?php $latest = $chat['_latest']; ?>
          <a class="chat-element <?= $chat['_unreadCount'] > 0 ? 'has-unread' : '' ?>" data-chat-key="<?= chatEscape($chat['key']) ?>" <?= ($chat['key'] ?? '') === ($activeChat['key'] ?? '') ? 'id="active-chat"' : '' ?> href="chat.php?chat=<?= rawurlencode((string) $chat['key']) ?><?= $chatSearch !== '' ? '&search=' . rawurlencode($chatSearch) : '' ?>">
            <div class="chat-picture-container"><img src="<?= chatEscape($chat['_photo']) ?>" alt="chat-picture" class="chat-picture" draggable="false" /></div>
            <div class="chat-info-container"><div class="chat-info-container-firstrow">
              <p class="chat-name"><?= chatEscape($chat['_displayName']) ?><?php if ($chat['_unreadCount'] > 0): ?> <span class="unread-badge"><?= $chat['_unreadCount'] > 99 ? '99+' : $chat['_unreadCount'] ?></span><?php endif; ?></p>
              <p class="last-message-sent-time"><?= $latest ? chatEscape(chatMessageTime($latest['sentAt'] ?? null)) : '' ?></p>
            </div><div class="chat-info-container-secondrow"><?= chatEscape($latest['text'] ?? 'Повідомлень ще немає') ?></div></div>
          </a>
          <?php if ($index < count($visibleChats) - 1): ?><hr /><?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$visibleChats): ?><p class="chat-empty">Чатів не знайдено.</p><?php endif; ?>
      </div>
    </div>
    <div class="active-chat-window-container">
      <div class="active-chat-header"><img src="<?= chatEscape($activePhoto) ?>" alt="active-chat-picture" class="active-chat-picture" /><p class="active-chat-name"><?= chatEscape($activeTitle) ?></p></div>
      <div class="active-chat-main">
        <?php foreach ($activeMessages as $message): ?>
          <?php $mine = (string) ($message['senderId'] ?? '') === (string) $currentUser['_id']; $senderEmployee = $employeeByUserId[(string) ($message['senderId'] ?? '')] ?? null; $senderPhoto = $senderEmployee && !empty($senderEmployee['Фото']) ? 'src/employees/' . rawurlencode((string) $senderEmployee['Фото']) : 'src/no-profile-photo.png'; ?>
          <div class="message-row-container" <?= $mine ? 'id="my-message-row"' : '' ?>><div class="message-row" <?= $mine ? 'id="my-message"' : '' ?>>
            <img src="<?= chatEscape($senderPhoto) ?>" alt="sender-photo" class="sender-photo" />
            <div class="message-content"><div class="message-first-row"><p class="message-sender"><?= $mine ? 'Ви' : chatEscape($message['senderName'] ?? 'Користувач') ?></p><p class="message-sent-time"><?= chatEscape(chatMessageTime($message['sentAt'] ?? null)) ?></p></div><p class="message"><?= nl2br(chatEscape($message['text'] ?? '')) ?></p></div>
          </div></div>
        <?php endforeach; ?>
      </div>
      <div class="active-chat-footer"><form method="post" action="services/chatPollProcess.php" id="message-form" class="search-bar-container message-compose">
        <input type="hidden" name="chat" value="<?= chatEscape($activeChat['key'] ?? 'general') ?>" />
        <input type="text" name="message" id="message-input" class="search-bar" placeholder="Напишіть повідомлення..." maxlength="4000" autocomplete="off" required />
        <button class="send-message-button" aria-label="Надіслати"><img src="src/send-message.png" alt="send-message-button" draggable="false" /></button>
      </form></div>
    </div>
  </div></div>
  <div id="chat-notification" class="chat-notification" role="status" aria-live="polite"></div>
  <script>
    document.getElementById("header-logo").addEventListener("click", () => window.location.href = "main.php");
    const activeKey = <?= json_encode((string) ($activeChat['key'] ?? 'general'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const chatSearch = document.getElementById("chat-search");
    const chatList = document.querySelector(".chat-list");
    const messageWindow = document.querySelector(".active-chat-main");
    const messageForm = document.getElementById("message-form");
    const messageInput = document.getElementById("message-input");
    let messageCount = <?= count($activeMessages) ?>;
    let polling = false;
    messageWindow.scrollTop = messageWindow.scrollHeight;

    function appendMessage(message) {
      const rowContainer = document.createElement("div");
      rowContainer.className = "message-row-container";
      if (message.mine) rowContainer.id = "my-message-row";
      const row = document.createElement("div");
      row.className = "message-row";
      if (message.mine) row.id = "my-message";
      const image = document.createElement("img");
      image.src = message.photo;
      image.alt = "sender-photo";
      image.className = "sender-photo";
      const content = document.createElement("div");
      content.className = "message-content";
      const firstLine = document.createElement("div");
      firstLine.className = "message-first-row";
      const sender = document.createElement("p");
      sender.className = "message-sender";
      sender.textContent = message.senderName;
      const time = document.createElement("p");
      time.className = "message-sent-time";
      time.textContent = message.time;
      firstLine.append(sender, time);
      const text = document.createElement("p");
      text.className = "message";
      String(message.text).split("\n").forEach((line, index) => {
        if (index) text.appendChild(document.createElement("br"));
        text.appendChild(document.createTextNode(line));
      });
      content.append(firstLine, text);
      row.append(image, content);
      rowContainer.appendChild(row);
      messageWindow.appendChild(rowContainer);
    }

    function renderChatList(chats) {
      const needle = chatSearch.value.trim().toLocaleLowerCase();
      const filtered = chats.filter(chat => !needle || `${chat.name} ${chat.preview}`.toLocaleLowerCase().includes(needle));
      chatList.replaceChildren();
      filtered.forEach((chat, index) => {
        const link = document.createElement("a");
        link.className = "chat-element";
        if (chat.unreadCount > 0) link.classList.add("has-unread");
        link.dataset.chatKey = chat.key;
        if (chat.key === activeKey) link.id = "active-chat";
        const chatUrl = new URL("chat.php", window.location.href);
        chatUrl.searchParams.set("chat", chat.key);
        if (chatSearch.value.trim()) chatUrl.searchParams.set("search", chatSearch.value.trim());
        link.href = chatUrl.toString();
        const pictureContainer = document.createElement("div");
        pictureContainer.className = "chat-picture-container";
        const picture = document.createElement("img");
        picture.src = chat.photo;
        picture.alt = "chat-picture";
        picture.className = "chat-picture";
        picture.draggable = false;
        pictureContainer.appendChild(picture);
        const info = document.createElement("div");
        info.className = "chat-info-container";
        const firstRow = document.createElement("div");
        firstRow.className = "chat-info-container-firstrow";
        const name = document.createElement("p");
        name.className = "chat-name";
        name.textContent = chat.name;
        if (chat.unreadCount > 0) {
          const badge = document.createElement("span");
          badge.className = "unread-badge";
          badge.textContent = chat.unreadCount > 99 ? "99+" : String(chat.unreadCount);
          name.append(" ", badge);
        }
        const sentTime = document.createElement("p");
        sentTime.className = "last-message-sent-time";
        sentTime.textContent = chat.time;
        firstRow.append(name, sentTime);
        const secondRow = document.createElement("div");
        secondRow.className = "chat-info-container-secondrow";
        secondRow.textContent = chat.preview;
        info.append(firstRow, secondRow);
        link.append(pictureContainer, info);
        chatList.appendChild(link);
        if (index < filtered.length - 1) chatList.appendChild(document.createElement("hr"));
      });
      if (!filtered.length) {
        const empty = document.createElement("p");
        empty.className = "chat-empty";
        empty.textContent = "Чатів не знайдено.";
        chatList.appendChild(empty);
      }
    }

    const notification = document.getElementById("chat-notification");
    let notificationTimer;
    function showNotification(message) {
      notification.textContent = message;
      notification.classList.add("visible");
      window.clearTimeout(notificationTimer);
      notificationTimer = window.setTimeout(() => notification.classList.remove("visible"), 5000);
    }

    function applyChatData(data) {
      if (data.messageCount > messageCount) {
        data.messages.filter(message => !message.mine).forEach(message => showNotification(`Нове повідомлення від ${message.senderName}: ${message.text}`));
        data.messages.forEach(appendMessage);
        messageCount = data.messageCount;
        messageWindow.scrollTop = messageWindow.scrollHeight;
      }
      renderChatList(data.chats);
    }

    async function refreshChat(after = messageCount) {
      if (polling) return;
      polling = true;
      try {
        const response = await fetch(`services/chatPollProcess.php?chat=${encodeURIComponent(activeKey)}&after=${after}`, { headers: { "Accept": "application/json" } });
        if (!response.ok) throw new Error("Chat refresh failed");
        const data = await response.json();
        applyChatData(data);
      } catch (error) {
        // Keep current messages visible; next poll retries.
      } finally {
        polling = false;
      }
    }

    messageForm.addEventListener("submit", async event => {
      event.preventDefault();
      if (!messageInput.value.trim()) return;
      const body = new FormData(messageForm);
      body.set("after", String(messageCount));
      try {
        const response = await fetch(messageForm.action, { method: "POST", body, headers: { "Accept": "application/json" } });
        if (!response.ok) throw new Error("Message send failed");
        const data = await response.json();
        applyChatData(data);
        messageInput.value = "";
        messageWindow.scrollTop = messageWindow.scrollHeight;
      } catch (error) {
        alert("Повідомлення не вдалося надіслати. Спробуйте ще раз.");
      }
    });
    chatSearch.addEventListener("input", () => refreshChat());
    window.setInterval(() => refreshChat(), 2000);
    refreshChat();
  </script>
  <script src="scripts/chat-notifications.js" defer></script>
</body>
</html>
