<?php

use MongoDB\BSON\UTCDateTime;

function ensureDirectChatsForUser($user, $usersCollection, $chatsCollection): void
{
    $userId = $user['_id'];
    $others = $usersCollection->find(['_id' => ['$ne' => $userId]])->toArray();
    foreach ($others as $other) {
        $ids = [(string) $userId, (string) $other['_id']];
        sort($ids, SORT_STRING);
        $key = 'direct:' . implode(':', $ids);
        if ($chatsCollection->findOne(['key' => $key])) continue;
        $chatsCollection->insertOne([
            'key' => $key,
            'type' => 'direct',
            'members' => [$userId, $other['_id']],
            'messages' => [],
            'createdAt' => new UTCDateTime()
        ]);
    }
}

function chatLatestMessage($chat)
{
    $messages = $chat['messages'] ?? [];
    return count($messages) ? $messages[count($messages) - 1] : null;
}

function chatMembers($chat): array
{
    $members = $chat['members'] ?? [];
    return is_array($members) ? $members : iterator_to_array($members, false);
}

function canAccessChat($chat, $user): bool
{
    $type = (string) ($chat['type'] ?? '');
    if ($type === 'direct') {
        foreach (chatMembers($chat) as $memberId) {
            if ((string) $memberId === (string) ($user['_id'] ?? '')) return true;
        }
        return false;
    }
    if ($type === 'role') {
        if (($user['Роль'] ?? '') === 'Адміністратор') return true;
        $chatRole = (string) ($chat['role'] ?? substr((string) ($chat['key'] ?? ''), 5));
        return $chatRole !== '' && $chatRole === (string) ($user['Роль'] ?? '');
    }
    return true;
}

function unreadCountForChat($chat, $user): int
{
    $userId = (string) ($user['_id'] ?? '');
    return max(0, (int) ($chat['unreadCounts'][$userId] ?? 0));
}

function markChatRead($chatsCollection, $chat, $user): void
{
    $userId = (string) ($user['_id'] ?? '');
    if ($userId === '') return;
    $chatsCollection->updateOne(['_id' => $chat['_id']], ['$set' => ['unreadCounts.' . $userId => 0]]);
}

function incrementChatUnreadForRecipients($chat, $sender, $usersCollection, $chatsCollection): void
{
    foreach ($usersCollection->find()->toArray() as $recipient) {
        if ((string) $recipient['_id'] === (string) $sender['_id'] || !canAccessChat($chat, $recipient)) continue;
        $userId = (string) $recipient['_id'];
        $chatsCollection->updateOne(['_id' => $chat['_id']], ['$inc' => ['unreadCounts.' . $userId => 1]]);
    }
}

function chatMessageTime($date): string
{
    if ($date instanceof UTCDateTime) return $date->toDateTime()->setTimezone(new DateTimeZone('Europe/Kyiv'))->format('H:i');
    return '';
}

function chatMessageDate($date): int
{
    if ($date instanceof UTCDateTime) return (int) $date->toDateTime()->format('U');
    return 0;
}

function chatEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
