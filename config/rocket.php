<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Panel
    |--------------------------------------------------------------------------
    |
    | This option defines the panel that Rocket will use by default when no
    | panel is explicitly resolved. You may register multiple panels with
    | Rocket (admin, customer, etc.) and switch between them at runtime.
    |
    */

    'default_panel' => env('ROCKET_DEFAULT_PANEL', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Inertia Settings
    |--------------------------------------------------------------------------
    |
    | Rocket uses Inertia.js to render its pages. You may customize the root
    | Blade view that wraps every Rocket page here. The default view ships
    | with the package but you can publish and override it if you wish.
    |
    */

    'inertia' => [
        'root_view' => env('ROCKET_ROOT_VIEW', 'rocket::app'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend Assets
    |--------------------------------------------------------------------------
    |
    | Rocket ships a self-contained React entry compiled through Vite. The
    | path below is the entry included in the Blade root view. If you have
    | published Rocket's assets to your application, update this path to
    | point at your published copy.
    |
    */

    'assets' => [
        'js_entry' => env('ROCKET_JS_ENTRY', 'packages/rocketphp/resources/js/rocket.tsx'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Defaults
    |--------------------------------------------------------------------------
    |
    | These options control the default route group attributes Rocket will
    | apply when registering a panel. Individual panels may override these
    | values through the fluent Panel API (e.g. `->middleware()`).
    |
    */

    'routes' => [
        'middleware' => ['web'],
        'auth_middleware' => ['auth'],
        'domain' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination options for resource list pages. The per-page value
    | is clamped between `min_per_page` and `max_per_page` when resolving a
    | request to prevent abusive page sizes.
    |
    */

    'pagination' => [
        'per_page' => 25,
        'min_per_page' => 1,
        'max_per_page' => 100,
        'per_page_options' => [5, 10, 25, 50, 100],
        // Footer pagination style: 'simple' (prev/next only), 'numbered'
        // (page numbers + jump-to-page input), or 'compact' ("Page X of Y").
        'style' => 'numbered',
        'relation_manager' => [
            'per_page' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    |
    | Default branding applied to panels that do not set their own brand
    | through the Panel API. The value here is rendered inside the panel
    | sidebar header and the browser tab title.
    |
    */

    'brand' => [
        'name' => env('ROCKET_BRAND', 'Rocket'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Exports
    |--------------------------------------------------------------------------
    |
    | Settings for CSV exports dispatched via Bus batches. `chunk_size` is the
    | number of records per chunk job — tune to balance DB round-trips against
    | peak memory. `disk` is the filesystem disk used for chunk files and the
    | final assembled CSV; defaults to the app's default disk.
    |
    */

    'exports' => [
        'chunk_size' => (int) env('ROCKET_EXPORT_CHUNK_SIZE', 100),
        'disk' => env('ROCKET_EXPORT_DISK'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Imports
    |--------------------------------------------------------------------------
    |
    | Settings for CSV imports dispatched via Bus batches. `chunk_size` is the
    | number of CSV rows processed per chunk job.
    |
    */

    'imports' => [
        'chunk_size' => (int) env('ROCKET_IMPORT_CHUNK_SIZE', 100),
        'disk' => env('ROCKET_IMPORT_DISK'),
    ],

];
