<?php

return [

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3001'),

    'gmail_driver' => env('TIDIMAIL_GMAIL_DRIVER', 'http'),

    'undo_hours' => (int) env('TIDIMAIL_UNDO_HOURS', 24),

    'delete_after_days' => (int) env('TIDIMAIL_DELETE_AFTER_DAYS', 30),

    'sync_lookback_days' => (int) env('TIDIMAIL_SYNC_LOOKBACK_DAYS', 30),

    // Full pass: enough for a congested inbox. First pass unlocks review much sooner.
    'sync_max_messages' => (int) env('TIDIMAIL_SYNC_MAX_MESSAGES', 5000),

    'sync_ready_messages' => (int) env('TIDIMAIL_SYNC_READY_MESSAGES', 300),

    'daily_sync_at' => env('TIDIMAIL_DAILY_SYNC_AT', '06:00'),

    'daily_sync_skip_hours' => (int) env('TIDIMAIL_DAILY_SYNC_SKIP_HOURS', 18),

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:hello@tidimail.local'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    'sweep_first_run_limit' => 20,

    'sweep_daily_limit' => 8,

    'labels' => [
        'digest' => 'Tidimail/Digest',
        'keep' => 'Tidimail/Keep',
        'unsubscribed' => 'Tidimail/Unsubscribed',
    ],

];
