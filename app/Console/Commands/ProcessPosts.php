<?php

namespace App\Console\Commands;

use App\Model\Keys;
use ClickHouseDB\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class ProcessPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cup:process-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load initial data from disk';

    protected $redis;

    /** @var Client */
    protected $clickhouse;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo 'process updates...' . PHP_EOL;

        $this->redis = App::make('Redis');

        $this->clickhouse = App::make('Clickhouse');

        while (true) {
            $hasNews = false;

            $updateData = $this->getUserUpdates();
            $insertData = $this->getUserInserts();
            if (!empty($insertData)) {
                $hasNews = true;
                try {
                    $this->clickhouse->insert('profile', $insertData, ['id', 'email', 'first_name', 'last_name', 'gender', 'birth_date', 'version']);
                }
                catch (\Throwable $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }
            if (!empty($updateData)) {
                $hasNews = true;
                try {
                    $this->clickhouse->insert('profile', $insertData, ['id', 'email', 'first_name', 'last_name', 'gender', 'birth_date', 'version']);
                }
                catch (\Throwable $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }

            $updateData = $this->getLocationUpdates();
            $insertData = $this->getLocationInserts();
            if ($insertData) {
                $hasNews = true;
                try {
                    $this->clickhouse->insert('location', $insertData, ['id', 'place', 'country', 'city', 'distance', 'version']);
                }
                catch (\Throwable $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }
            if (!empty($updateData)) {
                $hasNews = true;
                try {
                    $this->clickhouse->exec($updateData);
                }
                catch (\Throwable $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }

            $updateData = $this->getVisitUpdates();
            $insertData = $this->getVisitInserts();
            if ($insertData) {
                $hasNews = true;
                try {
                    $this->clickhouse->insert('visit', $insertData, ['id', 'location', 'user', 'visited_at', 'mark', 'version']);
                }
                catch (\Throwable $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }
            if (!empty($updateData)) {
                $hasNews = true;
                try {
                    $this->clickhouse->exec($updateData);
                }
                catch (\Throwable $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }

            //echo 'Process posts step ' . ++$count . PHP_EOL;

            if (!$hasNews) {
                sleep(1);
            }
        }
    }

    protected function getUserUpdates()
    {
        $sql = null;
        $updates = [];
        while ($json = $this->redis->rpop(Keys::USER_UPDATE_KEY)) {
            $data = json_decode($json, true);
            $data['version']++;
            $updates[$data['id']] = $data;
        }
        return $updates;
    }

    protected function getUserInserts()
    {
        $values = [];
        while ($json = $this->redis->rpop(Keys::USER_INSERT_KEY)) {
            $data = json_decode($json, true);

            $values[] = [
                $data['id'],
                $data['email'],
                $data['first_name'],
                $data['last_name'],
                $data['gender'],
                $data['birth_date'],
                1,
            ];
        }

        return $values;
    }

    protected function getLocationUpdates()
    {
        $sql = null;
        $updates = [];
        while ($json = $this->redis->rpop(Keys::LOCATION_UPDATE_KEY)) {
            $data = json_decode($json, true);
            $data['version']++;
            $updates[$data['id']] = $data;
        }
        return $updates;
    }

    protected function getLocationInserts()
    {
        $values = [];
        while ($json = $this->redis->rpop(Keys::LOCATION_INSERT_KEY)) {
            $data = json_decode($json, true);

            $values[] = [
                $data['id'],
                $data['place'],
                $data['country'],
                $data['city'],
                $data['distance'],
                1,
            ];
        }

        return $values;
    }

    protected function getVisitUpdates()
    {
        $sql = null;
        $updates = [];
        while ($json = $this->redis->rpop(Keys::VISIT_UPDATE_KEY)) {
            $data = json_decode($json, true);
            $data['version']++;
            $updates[$data['id']] = $data;
        }
        return $sql;
    }

    protected function getVisitInserts()
    {
        $values = [];
        while ($json = $this->redis->rpop(Keys::VISIT_INSERT_KEY)) {
            $data = json_decode($json, true);

            $values[] = [
                $data['id'],
                $data['location'],
                $data['user'],
                $data['visited_at'],
                $data['mark'],
                1,
            ];
        }

        return $values;
    }
}
