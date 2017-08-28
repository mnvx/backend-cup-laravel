<?php

namespace App\Http\Controllers\Api;

use App\Model\Keys;
use Illuminate\Http\Request;

class VisitController extends ApiController
{
    protected $collection = 'visit';

    protected $table = 'visit';

    public function create(Request $request)
    {
        $requestData = $request->json()->all();

        $id = $requestData['id'] ?? null;
        $location = $requestData['location'] ?? null;
        $user = $requestData['user'] ?? null;
        $visited_at = $requestData['visited_at'] ?? null;
        $mark = $requestData['mark'] ?? null;
        if ($location === null || $user === null || $visited_at === null || $mark === null || $id === null) {
            return $this->get400();
        }
        if (
            ($visited_at < 946674000 || $visited_at >= 1420146000) ||
            ($mark < 0 || $mark > 5)
        ) {
            return $this->get400();
        }

        $this->redis->lpush(Keys::VISIT_INSERT_KEY, json_encode($requestData));

        return $this->jsonResponse('{}');
    }

    public function update($id, Request $request)
    {
        $requestData = $request->json()->all();

        $location = $requestData['location'] ?? null;
        $user = $requestData['user'] ?? null;
        $visitedAt = $requestData['visited_at'] ?? null;
        $mark = $requestData['mark'] ?? null;
        if ($location === null && $user === null && $visitedAt === null && $mark === null) {
            return $this->get400();
        }
        if (
            (array_key_exists('location', $requestData) && !is_int($location)) ||
            (array_key_exists('user', $requestData) && !is_int($user)) ||
            (array_key_exists('visited_at', $requestData) && ($visitedAt < 946674000 || $visitedAt >= 1420146000 || !is_int($visitedAt))) ||
            (array_key_exists('mark', $requestData) && ($mark < 0 || $mark > 5 || !is_int($mark)))
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
        $this->redis->lpush(Keys::VISIT_UPDATE_KEY, json_encode($requestData));

        return $this->jsonResponse('{}');
    }

}