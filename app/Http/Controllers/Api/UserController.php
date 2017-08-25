<?php

namespace App\Http\Controllers\Api;

use App\Model\Entity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class UserController extends ApiController
{
    protected $collection = 'user';

    public function getVisits($id, Request $request)
    {
        $requestData = $request->all();

        $fromDate = $requestData['fromDate'] ?? null;
        $toDate = $requestData['toDate'] ?? null;
        $distance = $requestData['toDistance'] ?? null;
        $country = $requestData['country'] ?? null;

        if (
            ($fromDate && !ctype_digit($fromDate)) ||
            ($toDate && !ctype_digit($toDate)) ||
            ($distance && !ctype_digit($distance))
        ) {
            return $this->get400();
        }

        if (!ctype_digit($id)) {
            return $this->get404();
        }

        $redis = App::make('Redis');
        $entity = $redis->hget($this->collection, $id);
        if (!$entity) {
            return $this->get404();
        }

        /** @var PDO $pdo */
        $pdo = DB::connection()->getPdo();

        $sql = 'SELECT mark, visited_at, place
        FROM visit
        JOIN location ON location.id = visit.location
        WHERE "user" = ' . $id;

        if ($fromDate) {
            $sql .= ' AND visited_at > ' . $fromDate;
        }
        if ($toDate) {
            $sql .= ' AND visited_at < ' . $toDate;
        }
        if ($country) {
            $sql .= ' AND country = ' . $pdo->quote($country);
        }
        if ($distance) {
            $sql .= ' AND distance < ' . $distance;
        }
        $sql .= ' ORDER BY visited_at';

        $data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return $this->jsonResponse('{"visits": ' . json_encode($data) . '}');
    }

    public function create(Request $request)
    {
        $requestData = $request->json()->all();

        try {
            User::insert($requestData);
        }
        catch (Throwable $e) {
            return $this->get400();
        }

        $redis = App::make('Redis');
        $redis->hset($this->collection, $requestData['id'], $request->getContent());

        return $this->jsonResponse('{}');
    }

    public function update($id, Request $request)
    {
        $requestData = $request->json()->all();

        if (!ctype_digit($id)) {
            return $this->get404();
        }

        $entity = User::find($id);

        if (!$entity) {
            return $this->get404();
        }

        if (isset($requestData['id'])) {
            return $this->get400();
        }

        try {
            User::where('id', '=', $id)
                ->update($requestData);
        }
        catch (Throwable $e) {
            return $this->get400();
        }

        $redis = App::make('Redis');
        $redis->hset($this->collection, $id, json_encode($requestData + $entity->toArray()));

        return $this->jsonResponse('{}');
    }

}