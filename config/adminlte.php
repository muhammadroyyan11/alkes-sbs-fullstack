<?php

return [
    'title' => 'ALKES SBS',
    'title_prefix' => '',
    'title_postfix' => '',

    'use_ico_only' => false,
    'use_full_favicon' => false,

    'logo' => '<b>ALKES</b> SBS',
    'logo_img' => 'vendor/adminlte/dist/assets/img/AdminLTELogo.png',
    'logo_img_class' => 'brand-image opacity-75 shadow',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs opacity-75',
    'logo_img_alt' => 'ALKES SBS',

    'auth_logo' => [
        'enabled' => false,
        'img' => [
            'path' => 'vendor/adminlte/dist/assets/img/AdminLTELogo.png',
            'alt' => 'ALKES SBS',
            'class' => '',
            'width' => 50,
            'height' => 50,
        ],
    ],

    'preloader' => [
        'enabled' => true,
        'mode' => 'fullscreen',
        'img' => [
            'path' => 'vendor/adminlte/dist/assets/img/AdminLTELogo.png',
            'alt' => 'AdminLTE Preloader Image',
            'effect' => 'animation__shake',
            'width' => 60,
            'height' => 60,
        ],
    ],

    'usermenu_enabled' => true,
    'usermenu_header' => false,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => false,
    'usermenu_desc' => false,
    'usermenu_profile_url' => false,

    'use_route_url' => false,
    'dashboard_url' => 'admin.dashboard',
    'logout_url' => 'logout',
    'logout_method' => null,
    'login_url' => 'login',
    'register_url' => 'register',
    'password_reset_url' => 'password/reset',
    'password_email_url' => 'password/email',
    'profile_url' => false,

    'assets' => [
        'mode' => 'local',
        'cdn_fallback' => true,
        'adminlte_version' => null,
        'extended_colors' => false,
        'extended_colors_v3_aliases' => false,
        'palette' => [
            'primary' => null,
            'contrast' => null,
        ],
        'bootstrap_js' => true,
        'bootstrap_icons' => true,
        'overlayscrollbars' => true,
        'local' => [
            'adminlte_css' => 'vendor/adminlte/dist/css/adminlte.min.css',
            'adminlte_rtl_css' => 'vendor/adminlte/dist/css/adminlte.rtl.min.css',
            'adminlte_js' => 'vendor/adminlte/dist/js/adminlte.min.js',
            'colors_css' => 'vendor/adminlte/dist/css/adminlte-colors.min.css',
            'colors_rtl_css' => 'vendor/adminlte/dist/css/adminlte-colors.rtl.min.css',
            'colors_v3_css' => 'vendor/adminlte/dist/css/adminlte-colors-v3.min.css',
            'colors_v3_rtl_css' => 'vendor/adminlte/dist/css/adminlte-colors-v3.rtl.min.css',
            'bootstrap_js' => 'vendor/bootstrap/js/bootstrap.bundle.min.js',
            'bootstrap_icons_css' => 'vendor/bootstrap-icons/font/bootstrap-icons.min.css',
            'overlayscrollbars_css' => 'vendor/overlayscrollbars/styles/overlayscrollbars.min.css',
            'overlayscrollbars_js' => 'vendor/overlayscrollbars/browser/overlayscrollbars.browser.es6.min.js',
            'fonts_css' => 'vendor/fonts/source-sans-3/index.css',
        ],
        'cdn' => [
            'adminlte_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte.min.css',
            'adminlte_rtl_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte.rtl.min.css',
            'adminlte_js' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/js/adminlte.min.js',
            'colors_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte-colors.min.css',
            'colors_rtl_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte-colors.rtl.min.css',
            'colors_v3_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte-colors-v3.min.css',
            'colors_v3_rtl_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte-colors-v3.rtl.min.css',
            'bootstrap_js' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js',
            'bootstrap_icons_css' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css',
            'overlayscrollbars_css' => 'https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css',
            'overlayscrollbars_js' => 'https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js',
            'fonts_css' => 'https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css',
        ],
    ],

    'google_fonts' => [
        'allowed' => true,
    ],

    'laravel_asset_bundling' => false,
    'laravel_css_path' => 'resources/css/app.css',
    'laravel_js_path' => 'resources/js/app.js',

    'color_mode' => [
        'enabled' => true,
        'default' => 'light',
        'remember' => true,
        'no_flash_script' => true,
        'routes' => true,
        'theme_color' => [
            'light' => '#dc2626',
            'dark' => '#1a1a1a',
        ],
    ],

    'rtl' => [
        'enabled' => null,
        'locales' => ['ar', 'arc', 'ckb', 'dv', 'fa', 'ha', 'he', 'khw', 'ks', 'ps', 'sd', 'ug', 'ur', 'uz-AF', 'yi'],
    ],

    'print' => [],

    'layout_topnav' => null,
    'layout_fixed_sidebar' => true,
    'layout_fixed_navbar' => null,
    'layout_fixed_footer' => null,
    'layout_compact' => false,

    'classes_body' => 'bg-body-tertiary',
    'classes_brand' => '',
    'classes_brand_text' => 'fw-light',
    'classes_wrapper' => '',
    'classes_content_wrapper' => '',
    'classes_footer' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_content_top_area' => '',
    'classes_content_bottom_area' => '',
    'classes_sidebar' => 'bg-body-secondary shadow',
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'bg-body',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container-fluid',

    'sidebar_theme' => 'dark',
    'sidebar_mini' => true,
    'sidebar_collapse' => false,
    'sidebar_without_hover' => false,
    'sidebar_collapse_remember' => false,
    'sidebar_expand' => 'lg',
    'sidebar_breakpoint' => null,

    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'leave',
    'sidebar_scrollbar_click_scroll' => true,
    'sidebar_scrollbar_options' => [],
    'sidebar_scrollbar_disable_below' => 992,

    'sidebar_nav_aria_label' => null,
    'sidebar_nav_compact' => false,
    'sidebar_nav_indent' => false,
    'sidebar_nav_pills' => false,
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    'right_sidebar' => false,
    'right_sidebar_icon' => 'bi bi-gear',
    'right_sidebar_theme' => null,
    'right_sidebar_title' => null,
    'right_sidebar_placement' => 'end',
    'right_sidebar_backdrop' => true,
    'right_sidebar_scroll' => false,
    'right_sidebar_classes' => '',

    'css_variables' => [],
    'css_variables_scope' => ':root',
    'css_variables_sidebar' => [],

    'classes_auth_card' => 'card-outline card-primary',
    'classes_auth_header' => '',
    'classes_auth_body' => '',
    'classes_auth_footer' => '',
    'classes_auth_icon' => '',
    'classes_auth_btn' => 'btn-primary',

    'auth_social_links' => [],
    'auth_social_links_separator' => null,

    'lockscreen' => [
        'enabled' => false,
        'routes' => true,
        'guard' => null,
        'throttle' => [
            'max_attempts' => 5,
            'decay_seconds' => 60,
        ],
        'except' => [],
    ],

    'menu' => [
        ['type' => 'navbar-search', 'text' => 'search', 'topnav_right' => true],
        ['type' => 'darkmode-widget', 'topnav_right' => true],
        ['type' => 'fullscreen-widget', 'topnav_right' => true],

        ['type' => 'sidebar-menu-search', 'text' => 'search'],

        ['header' => 'MAIN NAVIGATION'],
        [
            'text' => 'Dashboard',
            'url' => 'admin/dashboard',
            'icon' => 'bi bi-speedometer2',
        ],

        ['header' => 'MANAJEMEN PRODUK'],
        [
            'text' => 'Produk',
            'url' => 'admin/products',
            'icon' => 'bi bi-box-seam',
            'can' => 'manage-products',
        ],
        [
            'text' => 'Variant',
            'url' => 'admin/variants',
            'icon' => 'bi bi-tags',
            'can' => 'manage-variants',
        ],

        ['header' => 'MANAJEMEN STOK'],
        [
            'text' => 'Stok',
            'url' => 'admin/stocks',
            'icon' => 'bi bi-archive',
            'can' => 'manage-stock',
        ],
        [
            'text' => 'Stok Opname',
            'url' => 'admin/stock-opnames',
            'icon' => 'bi bi-clipboard-check',
            'can' => 'manage-stock-opname',
        ],

        ['header' => 'PEMBELIAN'],
        [
            'text' => 'Purchase Order',
            'url' => 'admin/purchase-orders',
            'icon' => 'bi bi-cart-plus',
            'can' => 'manage-purchase-orders',
        ],
        [
            'text' => 'Purchase Receive',
            'url' => 'admin/purchase-receives',
            'icon' => 'bi bi-truck',
            'can' => 'manage-purchase-orders',
        ],

        ['header' => 'PENGATURAN'],
        [
            'text' => 'Users',
            'url' => 'admin/users',
            'icon' => 'bi bi-people',
            'can' => 'manage-users',
        ],
        [
            'text' => 'Website',
            'url' => 'admin/website',
            'icon' => 'bi bi-globe',
            'can' => 'manage-website',
        ],
    ],

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    'plugins' => [
        'Datatables' => [
            'active' => true,
            'files' => [
                ['type' => 'js', 'asset' => false, 'location' => '//cdn.datatables.net/2.1.8/js/dataTables.min.js'],
                ['type' => 'js', 'asset' => false, 'location' => '//cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js'],
                ['type' => 'css', 'asset' => false, 'location' => '//cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css'],
            ],
        ],
        'DatatablesButtons' => [
            'active' => true,
            'files' => [
                ['type' => 'js', 'asset' => false, 'location' => '//cdn.datatables.net/buttons/4.0.2/js/dataTables.buttons.min.js'],
                ['type' => 'js', 'asset' => false, 'location' => '//cdn.datatables.net/buttons/4.0.2/js/buttons.bootstrap5.min.js'],
                ['type' => 'js', 'asset' => false, 'location' => '//cdn.datatables.net/buttons/4.0.2/js/buttons.html5.min.js'],
                ['type' => 'js', 'asset' => false, 'location' => '//cdn.datatables.net/buttons/4.0.2/js/buttons.print.min.js'],
                ['type' => 'js', 'asset' => false, 'location' => '//cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js'],
                ['type' => 'js', 'asset' => false, 'location' => '//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.3.3/pdfmake.min.js'],
                ['type' => 'js', 'asset' => false, 'location' => '//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.3.3/vfs_fonts.js'],
                ['type' => 'css', 'asset' => false, 'location' => '//cdn.datatables.net/buttons/4.0.2/css/buttons.bootstrap5.min.css'],
            ],
        ],
        'Select2' => [
            'active' => true,
            'files' => [
                ['type' => 'js', 'asset' => false, 'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js'],
                ['type' => 'css', 'asset' => false, 'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css'],
                ['type' => 'css', 'asset' => false, 'location' => '//cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte-select2.min.css', 'rtl' => '//cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte-select2.rtl.min.css'],
            ],
        ],
        'Sweetalert2' => [
            'active' => true,
            'files' => [
                ['type' => 'js', 'asset' => false, 'location' => '//cdn.jsdelivr.net/npm/sweetalert2@11'],
            ],
        ],
        'Chartjs' => [
            'active' => true,
            'files' => [
                ['type' => 'js', 'asset' => false, 'location' => '//cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js'],
            ],
        ],
        'Pace' => [
            'active' => true,
            'files' => [
                ['type' => 'css', 'asset' => false, 'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.2.4/themes/blue/pace-theme-center-radar.min.css'],
                ['type' => 'js', 'asset' => false, 'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.2.4/pace.min.js'],
            ],
        ],
        'TomSelect' => ['active' => false, 'files' => []],
        'Tabulator' => ['active' => false, 'files' => []],
        'Flatpickr' => ['active' => false, 'files' => []],
        'Quill' => ['active' => false, 'files' => []],
        'NoUiSlider' => ['active' => false, 'files' => []],
    ],

    'iframe' => [
        'default_tab' => ['url' => null, 'title' => null],
        'buttons' => ['close' => true, 'close_all' => true, 'close_all_other' => true, 'scroll_left' => true, 'scroll_right' => true, 'fullscreen' => true],
        'options' => ['loading_screen' => 1000, 'auto_show_new_tab' => true, 'use_navbar_items' => true],
    ],

    'livewire' => false,
    'spa_navigation' => true,
];
