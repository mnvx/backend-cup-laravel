<?php

namespace App\Http\Controllers\Api;

use App\Model\Entity\User;
use App\Model\Entity\Visit;
use Illuminate\Http\Response;

class UserController extends ApiController
{
    public function get($id)
    {
        $entity = User::find($id);

        if (!$entity) {
            $response = new Response();
            $response->setStatusCode(404);
            return $response;
        }

        return $entity->toJson();
    }

    public function getVisits($id)
    {
        $query = Visit::where('user', '=', $id);

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

        return $query->get()->toJson();
    }

    public function create()
    {
        User::insert(request()->json()->all());
        return '{}';
    }

    public function update($id)
    {
        $entity = User::find($id);

        if (!$entity) {
            $response = new Response();
            $response->setStatusCode(404);
            return $response;
        }

        $entity->where('id', '=', $id)
            ->update(request()->json()->all());

        return '{}';
    }

}