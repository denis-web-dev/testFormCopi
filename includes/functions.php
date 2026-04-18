<?php
declare(strict_types=1);


function redirect(string $url): void
{
    header("Location: $url");
    exit;
}


function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}


function old(string $key, $default = ''): string
{
    return isset($_POST[$key]) ? e($_POST[$key]) : $default;
}


function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Вывод flash-сообщения
 */
function get_flash(): void
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        $class = $flash['type'] === 'success' ? 'success' : 'error';

        echo "<div class='flash-message {$class}'>{$flash['message']}</div>";
        unset($_SESSION['flash']);
    }
}
