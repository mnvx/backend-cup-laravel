<?php

namespace App\Console\Commands;

use ClickHouseDB\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class LoadData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cup:load-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load initial data from disk';

    protected $path = '/tmp/data/data.zip';

    /** @var Client */
    protected $clickhouse;

    protected $usersCount = 0;
    protected $locationsCount = 0;
    protected $visitCount = 0;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo 'load_files' . PHP_EOL;

        $this->clickhouse = App::make('Clickhouse');

        $this->loadUsers();
        echo 'files_loaded u:' . $this->usersCount . '/l:' . $this->locationsCount . '/v:' . $this->visitCount . PHP_EOL;

        $this->loadLocations();
        echo 'files_loaded u:' . $this->usersCount . '/l:' . $this->locationsCount . '/v:' . $this->visitCount . PHP_EOL;

        $this->loadVisits();
        echo 'files_loaded u:' . $this->usersCount . '/l:' . $this->locationsCount . '/v:' . $this->visitCount . PHP_EOL;
    }

    protected function loadUsers()
    {
        $this->clickhouse->truncateTable('profile');

        $zip = zip_open($this->path);

        while ($zip_entry = zip_read($zip)) {
            $filename = zip_entry_name($zip_entry);
            if (
                substr($filename, -5) !== '.json'
                || (
                    explode('_', $filename)[0] !== 'users'
                    && explode('_', $filename)[0] !== 'data/data/users'
                    && explode('_', $filename)[0] !== 'data/FULL/data/users'
                )
            ) {
                continue;
            }
            //echo "users... from $filename" . PHP_EOL;
            zip_entry_open($zip, $zip_entry, "r");
            $json = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
            zip_entry_close($zip_entry);
            $data = json_decode($json, true)['users'];

            $rows = [];
            foreach ($data as $item)
            {
                $rows[] = [
                    $item['id'],
                    $item['birth_date'],
                    $item['email'],
                    $item['first_name'],
                    $item['last_name'],
                    $item['gender'],
                ];

                $this->usersCount++;
            }

            $this->clickhouse->insert('profile', $rows, ['id', 'birth_date', 'email', 'first_name', 'last_name', 'gender']);
        }

        zip_close($zip);
    }

    protected function loadLocations()
    {
        $this->clickhouse->truncateTable('location');

        $zip = zip_open($this->path);

        while ($zip_entry = zip_read($zip)) {
            $filename = zip_entry_name($zip_entry);
            if (
                substr($filename, -5) !== '.json'
                || (
                    explode('_', $filename)[0] !== 'locations'
                    && explode('_', $filename)[0] !== 'data/data/locations'
                    && explode('_', $filename)[0] !== 'data/FULL/data/locations'
                )
            ) {
                continue;
            }
            //echo "locations from $filename...\n";
            zip_entry_open($zip, $zip_entry, "r");
            $json = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
            zip_entry_close($zip_entry);
            $data = json_decode($json, true)['locations'];

            $rows = [];
            foreach ($data as $item)
            {
                $rows[] = [
                    $item['id'],
                    $item['place'],
                    $item['country'],
                    $item['city'],
                    $item['distance'],
                ];

                $this->locationsCount++;
            }

            $this->clickhouse->insert('location', $rows, ['id', 'place', 'country', 'city', 'distance']);
        }

        zip_close($zip);
    }

    protected function loadVisits()
    {
        $this->clickhouse->truncateTable('visit');

        $zip = zip_open($this->path);

        while ($zip_entry = zip_read($zip)) {
            $filename = zip_entry_name($zip_entry);
            if (
                substr($filename, -5) !== '.json'
                || (
                    explode('_', $filename)[0] !== 'visits'
                    && explode('_', $filename)[0] !== 'data/data/visits'
                    && explode('_', $filename)[0] !== 'data/FULL/data/visits'
                )
            ) {
                continue;
            }
            //echo "visits from $filename...\n";
            zip_entry_open($zip, $zip_entry, "r");
            $json = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
            zip_entry_close($zip_entry);
            $data = json_decode($json, true)['visits'];

            $rows = [];
            foreach ($data as $item)
            {
                $rows[] = [
                    $item['id'],
                    $item['location'],
                    $item['user'],
                    $item['visited_at'],
                    $item['mark'],
                ];

                $this->visitCount++;
            }

            $this->clickhouse->insert('visit', $rows, ['id', 'location', 'user', 'visited_at', 'mark']);
        }

        zip_close($zip);
    }

}
