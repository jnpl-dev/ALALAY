<?php

return [
    'path' => env('BACKUP_PATH') ?: storage_path('app/backups'),

    'encrypt_pass' => env('BACKUP_ENCRYPT_PASS'),

    'retention_days' => env('BACKUP_RETENTION_DAYS', 30),

    'supabase_bucket' => env('SUPABASE_BACKUP_BUCKET', 'alalay-backups'),

    'supabase_endpoint' => env('SUPABASE_STORAGE_ENDPOINT'),

    'supabase_key' => env('SUPABASE_KEY'),

    'supabase_secret' => env('SUPABASE_SECRET'),

    'test_database' => env('BACKUP_TEST_DATABASE', 'alalay_backup_test'),
];
