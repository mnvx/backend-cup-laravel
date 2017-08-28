<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use PDO;

class AppServiceProvider extends ServiceProvider
{
    /** @var PDO */
    public static $pdo;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Redis', function ($app) {
            return Redis::connection('default');
        });

        $this->app->bind('PDO', function ($app) {
            if (!self::$pdo) {
                self::$pdo = DB::connection()->getPdo();
            }
            return self::$pdo;
        });
    }
}
