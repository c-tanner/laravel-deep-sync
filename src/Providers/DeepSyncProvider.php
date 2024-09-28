<?php

declare(strict_types=1);

namespace CTanner\LaravelDeepSync\Providers;

use Illuminate\Support\ServiceProvider;

class DeepSyncProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/deepsync.php' => config_path('deepsync.php'),
        ], 'deepsync');
    }
}
