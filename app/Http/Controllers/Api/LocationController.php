<?php

namespace App\Http\Controllers\Api;

use App\Model\Keys;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDO;

class LocationController extends ApiController
{
    protected $collection = 'location';

    public function getAverage($id, Request $request)
    {
        if (!ctype_digit($id)) {
            return $this->get404();
        }

        $entity = $this->redis->hget($this->collection, $id);
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

        $sql = 'SELECT COALESCE(AVG(mark), 0) as res
        FROM visit';
        $where = ' WHERE location = ' . $id;

        if ($fromDate !== null) {
            $where .= ' AND visited_at > ' . $fromDate;
        }
        if ($toDate !== null) {
            $where .= ' AND visited_at < ' . $toDate;
        }

        if ($fromAge || $toAge !== null || $gender !== null) {
            $sql .= ' JOIN profile on profile.id = visit.user';

            if ($fromAge && $toAge) {
                $from = strtotime((new Datetime())->sub(new DateInterval('P' . $fromAge . 'Y'))->format('Y-m-d H:i:s'));
                $to = strtotime((new Datetime())->sub(new DateInterval('P' . $toAge . 'Y'))->format('Y-m-d H:i:s'));
                $where .= ' AND profile.birth_date BETWEEN ' . $to . ' AND ' . $from;
            }
            elseif ($fromAge) {
                $from = strtotime((new Datetime())->sub(new DateInterval('P' . $fromAge . 'Y'))->format('Y-m-d H:i:s'));
                $where .= ' AND profile.birth_date < ' . $from;
            }
            elseif ($toAge) {
                $to = strtotime((new Datetime())->sub(new DateInterval('P' . $toAge . 'Y'))->format('Y-m-d H:i:s'));
                $where .= ' AND profile.birth_date > ' . $to;
            }

            if ($gender) {
                $where .= " AND profile.gender = '" . $gender . "'";
            }
        }

        $pdo = DB::connection()->getPdo();
        $data = $pdo->query($sql . $where)->fetch(PDO::FETCH_ASSOC);

        return $this->jsonResponse('{"avg": ' . round($data['res'], 5) . '}');
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

        if (!$entity = $this->redis->hget($this->collection, $id)) {
            return $this->get404();
        }

        if (isset($requestData['id'])) {
            return $this->get400();
        }

        $requestData['id'] = (int)$id;
        $this->redis->lpush(Keys::LOCATION_UPDATE_KEY, json_encode($requestData));

        return $this->jsonResponse('{}');
    }
}