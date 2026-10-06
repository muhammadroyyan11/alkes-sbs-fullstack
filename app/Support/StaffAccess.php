<?php

namespace App\Support;

/**
 * Peta akses role staf ke route admin.
 *
 * Sumber kebenaran tunggal untuk:
 *  - AdminMiddleware (larangan akses route)
 *  - Menu::getForUser (visibilitas sidebar)
 *  - User::canAccessAdminRoute()
 *
 * Format route: nama route Laravel, `*` = wildcard satu segmen akhir.
 */
final class StaffAccess
{
    private const MAP = [
        'gudang' => [
            'admin.dashboard',
            'admin.products.*',
            'admin.variants.*',
            'admin.categories.*',
            'admin.stocks.*',
            'admin.stock-opnames.*',
            'admin.stock-logs.*',
            'admin.suppliers.*',
            'admin.purchase-orders.*',
            'admin.purchase-receives.*',
        ],
        'operasional' => [
            'admin.dashboard',
            'admin.orders.*',
        ],
    ];

    /** @return string[] daftar role staf non-admin */
    public static function roles(): array
    {
        return array_keys(self::MAP);
    }

    public static function allows(?string $role, ?string $routeName): bool
    {
        if ($role === null || $routeName === null) {
            return false;
        }

        $patterns = self::MAP[$role] ?? [];
        foreach ($patterns as $pattern) {
            if (self::matches($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    private static function matches(string $pattern, string $routeName): bool
    {
        if ($pattern === $routeName) {
            return true;
        }

        if (str_ends_with($pattern, '.*')) {
            return str_starts_with($routeName, substr($pattern, 0, -1));
        }

        return false;
    }
}
