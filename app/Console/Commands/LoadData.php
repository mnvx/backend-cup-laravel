<?php

namespace App\Console\Commands;

use App\Model\Entity\Location;
use App\Model\Entity\User;
use App\Model\Entity\Visit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

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

    protected $redis;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo 'load_files' . PHP_EOL;

        $this->redis = App::make('Redis');

        $this->loadUsers();
        $this->loadLocations();
        $this->loadVisits();

        echo 'files_loaded' . PHP_EOL;
    }

    protected function loadUsers()
    {
        $this->executeSql('TRUNCATE TABLE profile');

        $zip = zip_open($this->path);

        while ($zip_entry = zip_read($zip)) {
            $filename = zip_entry_name($zip_entry);
            echo $filename . "\n";
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
            echo "users..." . PHP_EOL;
            zip_entry_open($zip, $zip_entry, "r");
            $json = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
            zip_entry_close($zip_entry);
            $data = json_decode($json, true)['users'];

            $sql = 'INSERT INTO profile (id, birth_date, email, first_name, last_name, gender) VALUES ';
            $first = true;
            foreach ($data as $item)
            {
                if (!$first) {
                    $sql .= ', ';
                }

                $sql .= '(' .
                    $item['id'] . ", " .
                    $item['birth_date'] . ", " .
                    DB::connection()->getPdo()->quote($item['email']) . ", " .
                    DB::connection()->getPdo()->quote($item['first_name']) . ", " .
                    DB::connection()->getPdo()->quote($item['last_name']) . ", " .
                    "'" . $item['gender'] . "'" .
                ')';

                $this->redis->hset('user', $item['id'], json_encode($item));

                $first = false;
            }

            $this->executeSql($sql);
        }
        $this->alterSequence('profile');

        zip_close($zip);
    }

    protected function loadLocations()
    {
        $this->executeSql('TRUNCATE TABLE location');

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
            echo "locations...\n";
            zip_entry_open($zip, $zip_entry, "r");
            $json = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
            zip_entry_close($zip_entry);
            $data = json_decode($json, true)['locations'];

            $sql = 'INSERT INTO location (id, place, country, city, distance) VALUES ';
            $first = true;
            foreach ($data as $item)
            {
                if (!$first) {
                    $sql .= ', ';
                }

                $sql .= '(' .
                    $item['id'] . ", " .
                    DB::connection()->getPdo()->quote($item['place']) . ", " .
                    DB::connection()->getPdo()->quote($item['country']) . ", " .
                    DB::connection()->getPdo()->quote($item['city']) . ", " .
                    $item['distance'] .
                ')';

                $this->redis->hset('location', $item['id'], json_encode($item));

                $first = false;
            }

            $this->executeSql($sql);
        }
        $this->alterSequence('location');

        zip_close($zip);
    }

    protected function loadVisits()
    {
        $this->executeSql('TRUNCATE TABLE visit');

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
            echo "visits...\n";
            zip_entry_open($zip, $zip_entry, "r");
            $json = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
            zip_entry_close($zip_entry);
            $data = json_decode($json, true)['visits'];

            $sql = 'INSERT INTO visit (id, location, "user", visited_at, mark) VALUES ';
            $first = true;
            foreach ($data as $item)
            {
                if (!$first) {
                    $sql .= ', ';
                }

                $sql .= '(' .
                    $item['id'] . ", " .
                    $item['location'] . ", " .
                    $item['user'] . ", " .
                    $item['visited_at'] . ", " .
                    $item['mark'] .
                ')';

                $this->redis->hset('visit', $item['id'], json_encode($item));

                $first = false;
            }

            $this->executeSql($sql);
        }
        $this->alterSequence('visit');

        zip_close($zip);
    }

    protected function executeSql($sql)
    {
        $completed = false;
        while (!$completed) {
            try {
                DB::statement($sql);
                $completed = true;
            }
            catch (\Throwable $e) {
                echo 'Error: ' . $e->getMessage() . PHP_EOL;
                sleep(5);
            }
        }
    }

    /**
     * Установка корректного sequence
     * @param string $tableName
     */
    protected function alterSequence($tableName)
    {
        $newId = DB::table($tableName)->select(DB::raw('MAX(id) as max_id'))->first()->max_id + 1;
        DB::statement('ALTER SEQUENCE ' . $tableName . '_id_seq' . ' RESTART WITH ' . $newId);
    }

}
