<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    // Disk used for task attachments. Set to 'supabase' once the
    // SUPABASE_STORAGE_* credentials below are configured.
    'attachments' => env('ATTACHMENT_DISK', 'public'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // Supabase Storage is S3-compatible — this disk points Laravel's
        // standard Storage facade at your Supabase project's Storage bucket.
        // See SETUP.md for where to find each of these values.
        'supabase' => [
            'driver' => 's3',
            'key' => env('SUPABASE_STORAGE_KEY'),
            'secret' => env('SUPABASE_STORAGE_SECRET'),
            'region' => env('SUPABASE_STORAGE_REGION', 'us-east-1'),
            'bucket' => env('SUPABASE_STORAGE_BUCKET'),
            'url' => env('SUPABASE_STORAGE_PUBLIC_URL'),
            'endpoint' => env('SUPABASE_STORAGE_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    'links' => [
        storage_path('app/public') => public_path('storage'),
    ],

];
