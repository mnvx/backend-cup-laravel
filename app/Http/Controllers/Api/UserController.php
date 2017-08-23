<?php

namespace App\Http\Controllers\Api;

use App\Model\Entity\User;
use App\Model\Entity\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Throwable;

class UserController extends ApiController
{
    protected $collection = 'user';

    public function get($id)
    {
        if (!$this->isCorrectId($id)) {
            return $this->get404();
        }

        $redis = App::make('Redis');
        $user = $redis->hget($this->collection, $id);
        if ($user) {
            return $this->jsonResponse($user);
        }

        $entity = User::find($id);

        if (!$entity) {
            return $this->get404();
        }

        $json = $entity->toJson();
        $redis->hset($this->collection, $id, $json);

        return $this->jsonResponse($json);
    }

    public function getVisits($id, Request $request)
    {
        if (!$this->customValidate($request, [
            'fromDate' => 'int',
            'toDate' => 'int',
            'toDistance' => 'int',
        ])) {
            return $this->get400();
        }

        if (!$this->isCorrectId($id)) {
            return $this->get404();
        }

        $entity = User::find($id);

        if (!$entity) {
            return $this->get404();
        }

        $query = Visit::select('mark', 'visited_at', 'place')
            ->where('user', '=', $id)
            ->join('location', 'location.id', '=', 'visit.location');

        if ($fromDate = request()->get('fromDate')) {
            $query->where('visited_at', '>', $fromDate);
        }
        if ($toDate = request()->get('toDate')) {
            $query->where('visited_at', '<', $toDate);
        }
        if ($country = request()->get('country')) {
            $query->where('country', '=', $country);
        }
        if ($distance = request()->get('toDistance')) {
            $query->where('distance', '<', $distance);
        }
        $query->orderBy('visited_at');

        return $this->jsonResponse('{"visits": ' . $query->get()->toJson() . '}');
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

        if (!$this->isCorrectId($id)) {
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