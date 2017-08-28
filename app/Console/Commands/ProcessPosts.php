<?php

namespace App\Console\Commands;

use App\Model\Keys;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use PDO;

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

    /** @var PDO */
    protected $pdo;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo 'process updates...' . PHP_EOL;

        $this->redis = App::make('Redis');

        /** @var PDO $pdo */
        $this->pdo = DB::connection()->getPdo();

        $count = 0;

        while (true) {
            $hasNews = false;

            $sqlUpdates = $this->getUserUpdates($updateEntities);
            $sqlInserts = $this->getUserInserts();
            if ($sqlInserts) {
                $hasNews = true;
                try {
                    $this->pdo->exec($sqlInserts);
                }
                catch (\PDOException $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }
            if ($sqlUpdates) {
                $hasNews = true;
                try {
                    $this->pdo->exec($sqlUpdates);
                    if (!empty($updateEntities)) {
                        $this->redis->hmset(Keys::USER_COLLECTION, $updateEntities);
                    }
                }
                catch (\PDOException $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }

            $sqlUpdates = $this->getLocationUpdates($updateEntities);
            $sqlInserts = $this->getLocationInserts();
            if ($sqlInserts) {
                $hasNews = true;
                try {
                    $this->pdo->exec($sqlInserts);
                }
                catch (\PDOException $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }
            if ($sqlUpdates) {
                $hasNews = true;
                try {
                    $this->pdo->exec($sqlUpdates);
                    if (!empty($updateEntities)) {
                        $this->redis->hmset(Keys::LOCATION_COLLECTION, $updateEntities);
                    }
                }
                catch (\PDOException $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }

            $sqlUpdates = $this->getVisitUpdates($updateEntities);
            $sqlInserts = $this->getVisitInserts();
            if ($sqlInserts) {
                $hasNews = true;
                try {
                    $this->pdo->exec($sqlInserts);
                }
                catch (\PDOException $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }
            if ($sqlUpdates) {
                $hasNews = true;
                try {
                    $this->pdo->exec($sqlUpdates);
                    if (!empty($updateEntities)) {
                        $this->redis->hmset(Keys::VISIT_COLLECTION, $updateEntities);
                    }
                }
                catch (\PDOException $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }

            //echo 'Process posts step ' . ++$count . PHP_EOL;

            if (!$hasNews) {
                sleep(1);
            }
        }
    }

    protected function getUserUpdates(&$entities)
    {
        $collection = Keys::USER_COLLECTION;
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

        $entities = [];
        foreach ($updates as $id => $data) {
            $set = [];
            if (isset($data['email'])) {
                $set[] = 'email = '  . $this->pdo->quote($data['email']);
            }
            if (isset($data['first_name'])) {
                $set[] = 'first_name = '  . $this->pdo->quote($data['first_name']);
            }
            if (isset($data['last_name'])) {
                $set[] = 'last_name = '  . $this->pdo->quote($data['last_name']);
            }
            if (isset($data['gender'])) {
                $set[] = 'gender = '  . $this->pdo->quote($data['gender']);
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

            if (!isset($entities[$id])) {
                $entities[$id] = $this->redis->hget($collection, $id);
            }
            $entities[$id] = json_encode($data + json_decode($entities[$id], true));
        }
        return $sql;
    }

    protected function getUserInserts()
    {
        $sql = 'INSERT INTO profile (id, email, first_name, last_name, gender, birth_date) VALUES ';
        $values = [];
        while ($json = $this->redis->rpop(Keys::USER_INSERT_KEY)) {
            $data = json_decode($json, true);

            $values[] = '('
                . $data['id'] . ','
                . $this->pdo->quote($data['email']) . ','
                . $this->pdo->quote($data['first_name']) . ','
                . $this->pdo->quote($data['last_name']) . ','
                . $this->pdo->quote($data['gender']) . ','
                . $data['birth_date']
                . ')';

            $this->redis->hset(Keys::USER_COLLECTION, $data['id'], json_encode($data));
        }
        if (empty($values)) {
            return null;
        }

        return $sql . implode(', ', $values) . ';';
    }

    protected function getLocationUpdates(&$entities)
    {
        $collection = Keys::LOCATION_COLLECTION;
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

        $entities = [];
        foreach ($updates as $id => $data) {
            $set = [];
            if (isset($data['place'])) {
                $set[] = 'place = '  . $this->pdo->quote($data['place']);
            }
            if (isset($data['country'])) {
                $set[] = 'country = '  . $this->pdo->quote($data['country']);
            }
            if (isset($data['city'])) {
                $set[] = 'city = '  . $this->pdo->quote($data['city']);
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

            if (!isset($entities[$id])) {
                $entities[$id] = $this->redis->hget($collection, $id);
            }
            $entities[$id] = json_encode($data + json_decode($entities[$id], true));
        }
        return $sql;
    }

    protected function getLocationInserts()
    {
        $sql = 'INSERT INTO location (id, place, country, city, distance) VALUES ';
        $values = [];
        while ($json = $this->redis->rpop(Keys::LOCATION_INSERT_KEY)) {
            $data = json_decode($json, true);

            $values[] = '('
                . $data['id'] . ','
                . $this->pdo->quote($data['place']) . ','
                . $this->pdo->quote($data['country']) . ','
                . $this->pdo->quote($data['city']) . ','
                . $data['distance']
                . ')';

            $this->redis->hset(Keys::LOCATION_COLLECTION, $data['id'], json_encode($data));
        }
        if (empty($values)) {
            return null;
        }

        return $sql . implode(', ', $values) . ';';
    }

    protected function getVisitUpdates(&$entities)
    {
        $collection = Keys::VISIT_COLLECTION;
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

        $entities = [];
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

            if (!isset($entities[$id])) {
                $entities[$id] = $this->redis->hget($collection, $id);
            }
            $entities[$id] = json_encode($data + json_decode($entities[$id], true));
        }
        return $sql;
    }

    protected function getVisitInserts()
    {
        $sql = 'INSERT INTO visit (id, location, "user", visited_at, mark) VALUES ';
        $values = [];
        while ($json = $this->redis->rpop(Keys::VISIT_INSERT_KEY)) {
            $data = json_decode($json, true);

            $values[] = '('
                . $data['id'] . ','
                . $data['location'] . ','
                . $data['user'] . ','
                . $data['visited_at'] . ','
                . $data['mark']
                . ')';

            $this->redis->hset(Keys::VISIT_COLLECTION, $data['id'], json_encode($data));
        }
        if (empty($values)) {
            return null;
        }

        return $sql . implode(', ', $values) . ';';
    }
}
