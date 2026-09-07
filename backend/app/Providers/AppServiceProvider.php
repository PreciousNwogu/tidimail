<?php

namespace App\Providers;

use App\Services\Gmail\FakeGmailGateway;
use App\Services\Gmail\GmailGateway;
use App\Services\Gmail\HttpGmailGateway;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GmailGateway::class, function ($app) {
            return config('tidimail.gmail_driver') === 'fake'
                ? $app->make(FakeGmailGateway::class)
                : $app->make(HttpGmailGateway::class);
        });
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();
    }
}
