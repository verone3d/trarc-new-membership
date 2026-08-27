<?php
/**
 * Small shared helpers used by index.php and submit.php.
 */

function trarc_e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function trarc_old(array $old, string $key, string $default = ''): string
{
    return $old[$key] ?? $default;
}

function trarc_checked(array $old, string $key, string $matchValue): string
{
    $current = $old[$key] ?? '';
    return ($current === $matchValue) ? 'checked' : '';
}

function trarc_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<span class="field-error">' . trarc_e($errors[$key]) . '</span>' : '';
}

function trarc_error_class(array $errors, string $key): string
{
    return isset($errors[$key]) ? 'has-error' : '';
}

/**
 * Strip characters that could be used for email header injection
 * (e.g. via a Reply-To built from a user-supplied address).
 */
function trarc_clean_header_value(string $value): string
{
    return trim(str_replace(["\r", "\n", "%0a", "%0d"], '', $value));
}
