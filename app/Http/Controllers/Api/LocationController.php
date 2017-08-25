<?php

namespace App\Http\Controllers\Api;

use App\Model\Entity\Location;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class LocationController extends ApiController
{
    protected $collection = 'location';

    public function getAverage($id, Request $request)
    {
        if (!ctype_digit($id)) {
            return $this->get404();
        }

        $redis = App::make('Redis');
        $entity = $redis->hget($this->collection, $id);
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

        try {
            Location::insert($requestData);
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

        $entity = Location::find($id);

        if (!$entity) {
            return $this->get404();
        }

        if (isset($requestData['id'])) {
            return $this->get400();
        }

        try {
            Location::where('id', '=', $id)
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