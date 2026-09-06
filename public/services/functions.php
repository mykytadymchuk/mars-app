<?php

/** Generates a readable, random first password for a new employee. */
function generateEmployeePassword(int $length = 12): string
{
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $password = '';
    $lastIndex = strlen($characters) - 1;

    for ($index = 0; $index < $length; $index++) {
        $password .= $characters[random_int(0, $lastIndex)];
    }

    return $password;
}

/** Converts Ukrainian names to the Latin alphabet used in usernames. */
function transliterateEmployeeName(string $value): string
{
    return strtr($value, [
        'А' => 'A',
        'а' => 'a',
        'Б' => 'B',
        'б' => 'b',
        'В' => 'V',
        'в' => 'v',
        'Г' => 'H',
        'г' => 'h',
        'Ґ' => 'G',
        'ґ' => 'g',
        'Д' => 'D',
        'д' => 'd',
        'Е' => 'E',
        'е' => 'e',
        'Є' => 'Ye',
        'є' => 'ie',
        'Ж' => 'Zh',
        'ж' => 'zh',
        'З' => 'Z',
        'з' => 'z',
        'И' => 'Y',
        'и' => 'y',
        'І' => 'I',
        'і' => 'i',
        'Ї' => 'Yi',
        'ї' => 'i',
        'Й' => 'Y',
        'й' => 'i',
        'К' => 'K',
        'к' => 'k',
        'Л' => 'L',
        'л' => 'l',
        'М' => 'M',
        'м' => 'm',
        'Н' => 'N',
        'н' => 'n',
        'О' => 'O',
        'о' => 'o',
        'П' => 'P',
        'п' => 'p',
        'Р' => 'R',
        'р' => 'r',
        'С' => 'S',
        'с' => 's',
        'Т' => 'T',
        'т' => 't',
        'У' => 'U',
        'у' => 'u',
        'Ф' => 'F',
        'ф' => 'f',
        'Х' => 'Kh',
        'х' => 'kh',
        'Ц' => 'Ts',
        'ц' => 'ts',
        'Ч' => 'Ch',
        'ч' => 'ch',
        'Ш' => 'Sh',
        'ш' => 'sh',
        'Щ' => 'Shch',
        'щ' => 'shch',
        'Ь' => '',
        'ь' => '',
        'Ю' => 'Yu',
        'ю' => 'iu',
        'Я' => 'Ya',
        'я' => 'ia',
        "'" => '',
        '’' => '',
        '`' => ''
    ]);
}

/** Returns a unique username based on the employee's first and last name. */
function generateEmployeeUsername(string $lastName, string $firstName, $usersCollection): string
{
    $base = strtolower(transliterateEmployeeName($firstName . $lastName));
    $base = preg_replace('/[^a-z0-9]/', '', $base) ?: 'employee';
    $username = $base;
    $suffix = 1;

    while ($usersCollection->findOne(['Логін' => $username])) {
        $username = $base . $suffix;
        $suffix++;
    }

    return $username;
}
