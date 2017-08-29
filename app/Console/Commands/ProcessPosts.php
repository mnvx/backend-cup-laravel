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

            $sqlUpdates = $this->getUserUpdates();
            $insertData = $this->getUserInserts();
            if (!empty($insertData)) {
                $hasNews = true;
                try {
                    $this->clickhouse->insert('profile', $insertData, ['id', 'birth_date', 'email', 'first_name', 'last_name', 'gender']);
                }
                catch (\Throwable $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }
            if ($sqlUpdates) {
                $hasNews = true;
                try {
                    $this->clickhouse->exec($sqlUpdates);
                }
                catch (\Throwable $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }

            $sqlUpdates = $this->getLocationUpdates();
            $insertData = $this->getLocationInserts();
            if ($insertData) {
                $hasNews = true;
                try {
                    $this->clickhouse->insert('location', $insertData, ['id', 'place', 'country', 'city', 'distance']);
                }
                catch (\Throwable $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }
            if ($sqlUpdates) {
                $hasNews = true;
                try {
                    $this->clickhouse->exec($sqlUpdates);
                }
                catch (\Throwable $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }

            $sqlUpdates = $this->getVisitUpdates();
            $insertData = $this->getVisitInserts();
            if ($insertData) {
                $hasNews = true;
                try {
                    $this->clickhouse->insert('visit', $insertData, ['id', 'location', 'user', 'visited_at', 'mark']);
                }
                catch (\Throwable $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }
            if ($sqlUpdates) {
                $hasNews = true;
                try {
                    $this->clickhouse->exec($sqlUpdates);
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
            if (!isset($updates[$data['id']])) {
                $updates[$data['id']] = $data;
            }
            else {
                $updates[$data['id']] = $data + $updates[$data['id']];
            }
        }

        foreach ($updates as $id => $data) {
            $set = [];
            if (isset($data['email'])) {
                $set[] = 'email = '  . $this->clickhouse->quote($data['email']);
            }
            if (isset($data['first_name'])) {
                $set[] = 'first_name = '  . $this->clickhouse->quote($data['first_name']);
            }
            if (isset($data['last_name'])) {
                $set[] = 'last_name = '  . $this->clickhouse->quote($data['last_name']);
            }
            if (isset($data['gender'])) {
                $set[] = 'gender = '  . $this->clickhouse->quote($data['gender']);
            }
            if (isset($data['birth_date'])) {
                $set[] = 'birth_date = '  . $data['birth_date'];
            }

            if (empty($set)) {
                continue;
            }
            $sql .= 'UPDATE profile SET ' .
                implode(', ', $set) .
                ' WHERE id = ' . $data['id'] . ';';
        }
        return $sql;
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
            if (!isset($updates[$data['id']])) {
                $updates[$data['id']] = $data;
            }
            else {
                $updates[$data['id']] = $data + $updates[$data['id']];
            }
        }

        foreach ($updates as $id => $data) {
            $set = [];
            if (isset($data['place'])) {
                $set[] = 'place = '  . $this->clickhouse->quote($data['place']);
            }
            if (isset($data['country'])) {
                $set[] = 'country = '  . $this->clickhouse->quote($data['country']);
            }
            if (isset($data['city'])) {
                $set[] = 'city = '  . $this->clickhouse->quote($data['city']);
            }
            if (isset($data['distance'])) {
                $set[] = 'distance = '  . $data['distance'];
            }

            if (empty($set)) {
                continue;
            }
            $sql .= 'UPDATE location SET ' .
                implode(', ', $set) .
                ' WHERE id = ' . $data['id'] . ';';
        }
        return $sql;
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
            if (!isset($updates[$data['id']])) {
                $updates[$data['id']] = $data;
            }
            else {
                $updates[$data['id']] = $data + $updates[$data['id']];
            }
        }

        foreach ($updates as $id => $data) {
            $set = [];
            if (isset($data['location'])) {
                $set[] = 'location = '  . $data['location'];
            }
            if (isset($data['user'])) {
                $set[] = '"user" = '  . $data['user'];
            }
            if (isset($data['visited_at'])) {
                $set[] = 'visited_at = '  . $data['visited_at'];
            }
            if (isset($data['mark'])) {
                $set[] = 'mark = '  . $data['mark'];
            }

            if (empty($set)) {
                continue;
            }
            $sql .= 'UPDATE visit SET ' .
                implode(', ', $set) .
                ' WHERE id = ' . $data['id'] . ';';
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
            ];
        }

        return $values;
    }
}
