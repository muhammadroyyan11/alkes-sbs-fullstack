<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'icon', 'route', 'url', 'group', 'group_order',
        'order', 'badge_text', 'badge_color', 'parent_id', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'menu_user')->withTimestamps();
    }

    /**
     * Get URL for this menu item
     */
    public function getMenuUrlAttribute(): string
    {
        if ($this->url) {
            return $this->url;
        }
        if ($this->route) {
            try {
                return route($this->route);
            } catch (\Exception $e) {
                return '#';
            }
        }
        return '#';
    }

    /**
     * Check if this menu is currently active
     */
    public function getIsCurrentAttribute(): bool
    {
        if ($this->route) {
            $routePrefix = str_replace('.index', '.*', $this->route);
            return request()->routeIs($routePrefix);
        }
        return false;
    }

    /**
     * Get menus grouped for sidebar, filtered by user access
     */
    public static function getForUser(User $user): array
    {
        $menus = self::where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('group_order')
            ->orderBy('order')
            ->with('children')
            ->get();

        // Admin melihat semua; staf (gudang/operasional) hanya menu sesuai peta akses role
        if (!$user->isAdmin()) {
            $menus = $menus
                ->filter(fn ($menu) => $user->canAccessAdminRoute($menu->route))
                ->values();

            foreach ($menus as $menu) {
                $menu->setRelation(
                    'children',
                    $menu->children->filter(fn ($child) => $user->canAccessAdminRoute($child->route))->values()
                );
            }
        }

        // Group by group name
        $grouped = [];
        foreach ($menus as $menu) {
            $group = $menu->group ?: 'Lainnya';
            if (!isset($grouped[$group])) {
                $grouped[$group] = [
                    'order' => $menu->group_order,
                    'items' => [],
                ];
            }
            $grouped[$group]['items'][] = $menu;
        }

        // Sort groups by order
        uasort($grouped, fn($a, $b) => $a['order'] <=> $b['order']);

        return $grouped;
    }
}
