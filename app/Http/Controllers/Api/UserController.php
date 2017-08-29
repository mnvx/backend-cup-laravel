<?php

namespace App\Http\Controllers\Api;

use App\Model\Keys;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    protected $collection = Keys::USER_COLLECTION;

    protected $table = 'profile';

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

        $entity = $this->getRecord($id);
        if (!$entity) {
            return $this->get404();
        }

        // tail -f /var/www/cup-backend/storage/logs/laravel.log
        // Yes! It is some crazy but fast select for Clickhouse.
        $sql = 'SELECT t.mark, t.visited_at, place
            FROM visit AS t
            ANY LEFT JOIN (
                    SELECT id AS location, place, country, distance 
                    FROM location as l
                    WHERE (SELECT l2.id FROM location AS l2 WHERE l2.id = l.id AND l2.version > l.version) IS NULL
                ) USING (location)
            WHERE (SELECT t2.id FROM visit AS t2 WHERE t2.id = t.id AND t2.version > t.version) IS NULL
            AND t.user = ' . $id;

        if ($fromDate) {
            $sql .= ' AND t.visited_at > ' . $fromDate;
        }
        if ($toDate) {
            $sql .= ' AND t.visited_at < ' . $toDate;
        }
        if ($country) {
            $sql .= " AND country = '" . str_replace("'", '\\\'', $country) . "'";
        }
        if ($distance) {
            $sql .= ' AND distance < ' . $distance;
        }
        $sql .= ' ORDER BY t.visited_at';

        $data = $this->clickhouse->select($sql)->rows();

        return $this->jsonResponse('{"visits": ' . json_encode($data) . '}');
    }

    public function create(Request $request)
    {
        $requestData = $request->json()->all();

        $id = $requestData['id'] ?? null;
        $email = $requestData['email'] ?? null;
        $firstName = $requestData['first_name'] ?? null;
        $lastName = $requestData['last_name'] ?? null;
        $gender = $requestData['gender'] ?? null;
        $birthDate = $requestData['birth_date'] ?? null;
        if ($email === null || $firstName === null || $lastName === null || $gender === null || $birthDate === null || $id === null) {
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

        $this->redis->lpush(Keys::USER_INSERT_KEY, json_encode($requestData));

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
        if ($email === null && $firstName === null && $lastName === null && $gender === null && $birthDate === null) {
            return $this->get400();
        }
        if (
            (array_key_exists('email', $requestData) && (mb_strlen($email) > 100 || $email === null)) ||
            (array_key_exists('first_name', $requestData) && (mb_strlen($firstName) > 50 || $firstName === null)) ||
            (array_key_exists('last_name', $requestData) && (mb_strlen($lastName) > 50 || $lastName === null)) ||
            (array_key_exists('gender', $requestData) && (($gender !== 'm' && $gender !== 'f') || $gender === null)) ||
            (array_key_exists('birth_date', $requestData) && ($birthDate < -1262311200 || $birthDate >= 915224400 || !is_int($birthDate)))
        ) {
            return $this->get400();
        }

        if (!ctype_digit($id)) {
            return $this->get404();
        }

        $entity = $this->getRecord($id);
        if (empty($entity)) {
            return $this->get404();
        }

        if (isset($requestData['id'])) {
            return $this->get400();
        }

        $this->redis->lpush(Keys::USER_UPDATE_KEY, json_encode($requestData + $entity));

        return $this->jsonResponse('{}');
    }

}