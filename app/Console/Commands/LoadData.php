<?php

namespace App\Console\Commands;

use App\Model\Repository\LocationRepository;
use App\Model\Repository\ProfileRepository;
use App\Model\Repository\VisitRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Packer\PurePacker;

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

    /**
     * @var Client
     */
    protected $client;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo 'load_files' . PHP_EOL;

        $conn = new StreamConnection('tcp://' . env('TARANTOOL_HOST') . ':' . env('TARANTOOL_PORT'));
        $this->client = new Client($conn, new PurePacker());

//        $this->load('users', new ProfileRepository());
//        $this->load('locations', new LocationRepository());
//        $this->load('visits', new VisitRepository());

        $this->loadUsers();
        $this->loadLocations();
        $this->loadVisits();

        echo 'files_loaded' . PHP_EOL;
    }

    protected function quote($value)
    {
        //return DB::connection()->getPdo()->quote($value);
        return "'" . str_replace("'", "\\'", $value) . "'";
    }

    protected function load($entityName, $repo)
    {
        $zip = zip_open($this->path);

        while ($zip_entry = zip_read($zip)) {
            $filename = zip_entry_name($zip_entry);
            echo $filename . "\n";
            if (
                substr($filename, -5) !== '.json'
                || (
                    explode('_', $filename)[0] !== $entityName
                    && explode('_', $filename)[0] !== 'data/data/' . $entityName
                )
            ) {
                continue;
            }
            echo $entityName . "..." . PHP_EOL;
            zip_entry_open($zip, $zip_entry, "r");
            $json = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
            zip_entry_close($zip_entry);
            $data = json_decode($json, true)[$entityName];

            foreach ($data as $item)
            {
                $repo->insert($item);
            }
        }

        zip_close($zip);
    }

    protected function loadUsers()
    {
        $zip = zip_open($this->path);

        while ($zip_entry = zip_read($zip)) {
            $filename = zip_entry_name($zip_entry);
            echo $filename . "\n";
            if (
                substr($filename, -5) !== '.json'
                || (
                    explode('_', $filename)[0] !== 'users'
                    && explode('_', $filename)[0] !== 'data/data/users'
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
                    $this->quote($item['email']) . ", " .
                    $this->quote($item['first_name']) . ", " .
                    $this->quote($item['last_name']) . ", " .
                    "'" . $item['gender'] . "'" .
                ')';

                $first = false;
            }

            $this->executeSql($sql);
        }
        $this->alterSequence('profile');

        zip_close($zip);
    }

    protected function loadLocations()
    {
        $zip = zip_open($this->path);

        while ($zip_entry = zip_read($zip)) {
            $filename = zip_entry_name($zip_entry);
            if (
                substr($filename, -5) !== '.json'
                || (
                    explode('_', $filename)[0] !== 'locations'
                    && explode('_', $filename)[0] !== 'data/data/locations'
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
                    $this->quote($item['place']) . ", " .
                    $this->quote($item['country']) . ", " .
                    $this->quote($item['city']) . ", " .
                    $item['distance'] .
                ')';

                $first = false;
            }

            $this->executeSql($sql);
        }
        $this->alterSequence('location');

        zip_close($zip);
    }

    protected function loadVisits()
    {
        $zip = zip_open($this->path);

        while ($zip_entry = zip_read($zip)) {
            $filename = zip_entry_name($zip_entry);
            if (
                substr($filename, -5) !== '.json'
                || (
                    explode('_', $filename)[0] !== 'visits'
                    && explode('_', $filename)[0] !== 'data/data/visits'
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
                $this->client->evaluate('box.sql.execute([[' . $sql . ';]])');
                //DB::statement($sql);
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
//        $newId = DB::table($tableName)->select(DB::raw('MAX(id) as max_id'))->first()->max_id + 1;
//        DB::statement('ALTER SEQUENCE ' . $tableName . '_id_seq' . ' RESTART WITH ' . $newId);
    }

}
