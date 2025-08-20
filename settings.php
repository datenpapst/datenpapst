<?php
require_once __DIR__ . '/db.php';
function get_setting(string $key, ?string $default = null): ?string {
    $stmt = get_db()->prepare('SELECT value FROM settings WHERE `key` = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row['value'] ?? $default;
}
function set_setting(string $key, string $value): void {
    $stmt = get_db()->prepare('REPLACE INTO settings (`key`,`value`) VALUES (?,?)');
    $stmt->execute([$key, $value]);
}
