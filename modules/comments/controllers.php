<?php

/**
 * Comments Module — Controllers
 */

function comments_normalize_body(string $body): string
{
    $normalized = str_replace(["\r\n", "\r"], "\n", $body);
    return trim($normalized);
}

function comments_validate_body(string $body): array
{
    $errors = [];
    $normalized = comments_normalize_body($body);

    if ($normalized === '') {
        $errors[] = 'Comment is required.';
    }

    if (strlen($normalized) > comments_max_body_length()) {
        $errors[] = 'Comment must be at most ' . comments_max_body_length() . ' characters.';
    }

    return [
        'errors' => $errors,
        'body' => $normalized,
    ];
}
