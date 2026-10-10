<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        Paginator::useBootstrapFive();

        if (! app()->isLocal()) {
            return;
        }

        DB::listen(function (QueryExecuted $query): void {
            Log::info('SQL query executed', [
                'sql' => $query->sql,
                'time_ms' => $query->time,
            ]);
        });
    }
}
