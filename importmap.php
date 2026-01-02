<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
    'bootstrap' => [
        'version' => '5.3.7',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.7',
        'type' => 'css',
    ],
    'htmx.org' => [
        'version' => '2.0.7',
    ],
    'scripts/specific/travel' => [
        'path' => './assets/scripts/specific/travel.js',
        'entrypoint' => true,
    ],
    'scripts/specific/admin' => [
        'path' => './assets/scripts/specific/admin.js',
        'entrypoint' => true,
    ],
    'scripts/htmx-url-template' => [
        'path' => './assets/scripts/htmx-url-template.js',
        'entrypoint' => true,
    ],
    'scripts/form-regex-validation' => [
        'path' => './assets/scripts/form-regex-validation.js',
        'entrypoint' => true,
    ],
    'travel.css' => [
        'path' => './assets/styles/specific/travel/travel.css',
        'type' => 'css',
        'entrypoint' => true,
    ],
    'admin.css' => [
        'path' => './assets/styles/specific/admin/admin.css',
        'type' => 'css',
        'entrypoint' => true,
    ],
    'leaflet' => [
        'version' => '1.9.4',
    ],
    'leaflet/dist/leaflet.min.css' => [
        'version' => '1.9.4',
        'type' => 'css',
    ],
    '@symfony/ux-leaflet-map' => [
        'path' => './vendor/symfony/ux-leaflet-map/assets/dist/map_controller.js',
    ],
    'chart.js' => [
        'version' => '4.5.1',
    ],
    '@kurkle/color' => [
        'version' => '0.3.4',
    ],
];
