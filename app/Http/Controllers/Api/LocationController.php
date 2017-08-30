<?php

namespace App\Http\Controllers\Api;

use App\Model\Keys;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;

class LocationController extends ApiController
{
    protected $collection = 'location';

    protected $table = 'location';

    public function getAverage($id, Request $request)
    {
        if (!ctype_digit($id)) {
            return $this->get404();
        }

        $entity = $this->getRecord($id);
        if (!$entity) {
            return $this->get404();
        }

        $requestData = $request->all();

        $fromDate = $requestData['fromDate'] ?? null;
        $toDate = $requestData['toDate'] ?? null;
        $fromAge = $requestData['fromAge'] ?? null;
        $toAge = $requestData['toAge'] ?? null;
        $gender = $requestData['gender'] ?? null;

        if (
            ($fromDate && !ctype_digit($fromDate)) ||
            ($toDate && !ctype_digit($toDate)) ||
            ($fromAge && !ctype_digit($fromAge)) ||
            ($toAge && !ctype_digit($toAge)) ||
            ($gender && ($gender !== 'm' && $gender !== 'f'))
        ) {
            return $this->get400();
        }

        $sql = 'SELECT AVG(mark) AS res
            FROM visit t';
        $where = ' WHERE (SELECT t2.id FROM visit AS t2 WHERE t2.id = t.id AND t2.version > t.version) IS NULL
            AND t.location = ' . $id;

        if ($fromDate !== null) {
            $where .= ' AND t.visited_at > ' . $fromDate;
        }
        if ($toDate !== null) {
            $where .= ' AND t.visited_at < ' . $toDate;
        }

        if ($fromAge || $toAge !== null || $gender !== null) {
            $sql .= ' ANY LEFT JOIN (
                    SELECT id AS user, birth_date, gender 
                    FROM profile as p
                    WHERE (SELECT p2.id FROM profile AS p2 WHERE p2.id = p.id AND p2.version > p.version) IS NULL
                ) USING (user)';

            if ($fromAge && $toAge) {
                $from = strtotime((new Datetime())->sub(new DateInterval('P' . $fromAge . 'Y'))->format('Y-m-d H:i:s'));
                $to = strtotime((new Datetime())->sub(new DateInterval('P' . $toAge . 'Y'))->format('Y-m-d H:i:s'));
                $where .= ' AND birth_date BETWEEN ' . $to . ' AND ' . $from;
            }
            elseif ($fromAge) {
                $from = strtotime((new Datetime())->sub(new DateInterval('P' . $fromAge . 'Y'))->format('Y-m-d H:i:s'));
                $where .= ' AND birth_date < ' . $from;
            }
            elseif ($toAge) {
                $to = strtotime((new Datetime())->sub(new DateInterval('P' . $toAge . 'Y'))->format('Y-m-d H:i:s'));
                $where .= ' AND birth_date > ' . $to;
            }

            if ($gender) {
                $where .= " AND gender = '" . $gender . "'";
            }
        }

        $data = $this->clickhouse->select($sql . $where)->fetchOne();

        return $this->jsonResponse('{"avg": ' . round($data['res'] ?? 0, 5) . '}');
    }

    public function create(Request $request)
    {
        $requestData = $request->json()->all();

        $id = $requestData['id'] ?? null;
        $place = $requestData['place'] ?? null;
        $country = $requestData['country'] ?? null;
        $city = $requestData['city'] ?? null;
        $distance = $requestData['distance'] ?? null;
        if ($place === null || $country === null || $city === null || $distance === null || $id === null) {
            return $this->get400();
        }
        if (
            (mb_strlen($country) > 50) ||
            (mb_strlen($city) > 50) ||
            (!is_int($distance))
        ) {
            return $this->get400();
        }

        $this->redis->lpush(Keys::LOCATION_INSERT_KEY, json_encode($requestData));

        return $this->jsonResponse('{}');
    }

    public function update($id, Request $request)
    {
        $requestData = $request->json()->all();

        $place = $requestData['place'] ?? null;
        $country = $requestData['country'] ?? null;
        $city = $requestData['city'] ?? null;
        $distance = $requestData['distance'] ?? null;
        if ($place === null && $country === null && $city === null && $distance === null) {
            return $this->get400();
        }
        if (
            (array_key_exists('place', $requestData) && ($place === null)) ||
            (array_key_exists('country', $requestData) && (mb_strlen($country) > 50 || $country === null)) ||
            (array_key_exists('city', $requestData) && (mb_strlen($city) > 50 || $city === null)) ||
            (array_key_exists('distance', $requestData) && (!is_int($distance) || $distance === null))
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

        if (isset($requestData['id'])) {
            return $this->get400();
        }

        $requestData['id'] = (int)$id;
        $this->redis->lpush(Keys::LOCATION_UPDATE_KEY, json_encode($requestData + $entity));

        return $this->jsonResponse('{}');
    }
}