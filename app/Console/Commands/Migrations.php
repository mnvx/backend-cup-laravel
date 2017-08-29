<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ClickHouseDB;
use Illuminate\Support\Facades\App;

class Migrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cup:migrations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database schema';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $db = App::make('Clickhouse');

        $db->write('
            CREATE TABLE profile (
                id Int32,
                email String,
                first_name String,
                last_name String,
                gender String,
                birth_date Int32,
                version Int32
            ) 
            ENGINE = Memory
        ');

        $db->write('
            CREATE TABLE location (
                id Int32,
                place String,
                country String,
                city String,
                distance Int32,
                version Int32
            ) 
            ENGINE = Memory
        ');

        $db->write('
            CREATE TABLE visit (
                id Int32,
                location Int32,
                user Int32,
                visited_at Int32,
                mark Int32,
                version Int32
            ) 
            ENGINE = Memory
        ');
    }
}
