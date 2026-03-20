<?php

/**
 * Auth Module — Models
 */

function auth_find_by_email(string $email): ?array
{
    return db_fetch('SELECT * FROM users WHERE email = ?', [$email]);
}

function auth_create_user(array $data): string
{
    return db_insert('users', [
        'name'     => $data['name'],
        'email'    => $data['email'],
        'password' => password_hash($data['password'], PASSWORD_DEFAULT),
        'role'     => $data['role'] ?? 'student',
    ]);
}
