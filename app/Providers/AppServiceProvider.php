<?php

namespace App\Providers;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use ClickHouseDB;

class AppServiceProvider extends ServiceProvider
{
    public static $clickhouse;

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

        $this->app->singleton('Clickhouse', function ($app) {
            if (!self::$clickhouse) {
                $config = [
                    'host'     => '127.0.0.1',
                    'port'     => '8123',
                    'username' => 'default',
                    'password' => ''
                ];

                $db = new ClickHouseDB\Client($config);
                $db->database('default');
                $db->setTimeout(2);
                $db->setConnectTimeOut(2);

                self::$clickhouse = $db;
            }
            return self::$clickhouse;
        });
    }
}
