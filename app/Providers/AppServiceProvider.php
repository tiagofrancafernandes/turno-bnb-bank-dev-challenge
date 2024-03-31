<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->validateTempDirs();
    }

    public function validateTempDirs(): void
    {
        if (!config('filesystems.use_temp_dir', false)) {
            return;
        }

        collect(config('filesystems.disks'))
            ->filter(fn ($item) => ($item['driver'] ?? null) === 'local')
            ->pluck('root')
            ->each(fn ($dir) => !is_dir($dir) && mkdir($dir, 0777, true));
    }
}
