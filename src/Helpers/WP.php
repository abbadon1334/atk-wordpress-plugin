<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Helpers;

class WP
{
    public static function getDbPrefix(): string
    {
        global $wpdb;

        return $wpdb->prefix;
    }

    public static function getDbCharsetCollate(): string
    {
        global $wpdb;

        return $wpdb->get_charset_collate();
    }

    public static function isAdmin(): bool
    {
        return is_admin();
    }

    public static function getBaseAdminUrl(): string
    {
        return admin_url();
    }

    public static function createWpNounce(string $name): string
    {
        return wp_create_nonce($name);
    }
}
