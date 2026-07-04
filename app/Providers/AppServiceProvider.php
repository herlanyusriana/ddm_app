<?php

namespace App\Providers;

use App\Models\Process;
use Illuminate\Support\Facades\View;
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
        View::composer('production.layout', function ($view): void {
            $view->with(
                'sidebarWipProcesses',
                Process::where('is_input_process', true)
                    ->where('is_fg_process', false)
                    ->whereRaw('LOWER(name) != ?', ['packing'])
                    ->orderBy('sort_order')
                    ->get()
            );
        });
    }
}
