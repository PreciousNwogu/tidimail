<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class EnsureVapidKeysCommand extends Command
{
    protected $signature = 'tidimail:ensure-vapid';

    protected $description = 'Create VAPID keys in .env if they are missing';

    public function handle(): int
    {
        $path = base_path('.env');
        $env = file_exists($path) ? (string) file_get_contents($path) : '';

        if (preg_match('/^VAPID_PUBLIC_KEY=.+$/m', $env) && preg_match('/^VAPID_PRIVATE_KEY=.+$/m', $env)) {
            $this->info('VAPID keys already set.');

            return self::SUCCESS;
        }

        try {
            $keys = VAPID::createVapidKeys();
        } catch (\Throwable $exception) {
            $this->error('Could not create VAPID keys on this PHP install. Notifications still show in the app.');

            return self::SUCCESS;
        }
        $block = PHP_EOL.'VAPID_SUBJECT=mailto:hello@tidimail.local'.PHP_EOL
            .'VAPID_PUBLIC_KEY='.$keys['publicKey'].PHP_EOL
            .'VAPID_PRIVATE_KEY='.$keys['privateKey'].PHP_EOL;

        file_put_contents($path, rtrim($env).$block);
        $this->info('VAPID keys written.');

        return self::SUCCESS;
    }
}
