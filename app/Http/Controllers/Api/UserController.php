<?php

namespace App\Http\Controllers\Api;

use App\Model\Entity\User;
use App\Model\Keys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class UserController extends ApiController
{
    protected $collection = Keys::USER_COLLECTION;

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

        $email = $requestData['email'] ?? null;
        $firstName = $requestData['first_name'] ?? null;
        $lastName = $requestData['last_name'] ?? null;
        $gender = $requestData['gender'] ?? null;
        $birthDate = $requestData['birth_date'] ?? null;
        if ($email === null || $firstName === null || $lastName === null || $gender === null || $birthDate === null) {
            return $this->get400();
        }
        if (
            (mb_strlen($email) > 100) ||
            (mb_strlen($firstName) > 50) ||
            (mb_strlen($lastName) > 50) ||
            ($gender !== 'm' && $gender !== 'f') ||
            ($birthDate < -1262311200 || $birthDate >= 915224400)
        ) {
            return $this->get400();
        }

        $redis = App::make('Redis');
        $redis->lpush(Keys::USER_INSERT_KEY, json_encode($requestData));

        return $this->jsonResponse('{}');
    }

    public function update($id, Request $request)
    {
        $requestData = $request->json()->all();

        $email = $requestData['email'] ?? null;
        $firstName = $requestData['first_name'] ?? null;
        $lastName = $requestData['last_name'] ?? null;
        $gender = $requestData['gender'] ?? null;
        $birthDate = $requestData['birth_date'] ?? null;
        if (
            ($email && mb_strlen($email) > 100) ||
            ($firstName && mb_strlen($firstName) > 50) ||
            ($lastName && mb_strlen($lastName) > 50) ||
            ($gender && $gender !== 'm' && $gender !== 'f') ||
            ($birthDate && ($birthDate < -1262311200 || $birthDate >= 915224400))
        ) {
            return $this->get400();
        }

        if (!ctype_digit($id)) {
            return $this->get404();
        }

        $redis = App::make('Redis');

        if (!$entity = $redis->hget($this->collection, $id)) {
            return $this->get404();
        }

        if (isset($requestData['id'])) {
            return $this->get400();
        }

        $requestData['id'] = $id;
        $redis->lpush(Keys::USER_UPDATE_KEY, json_encode($requestData));

        return $this->jsonResponse('{}');
    }

}