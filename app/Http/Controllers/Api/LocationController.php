<?php

namespace App\Http\Controllers\Api;

use App\Model\Entity\Location;
use App\Model\Entity\Visit;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class LocationController extends ApiController
{
    public function get($id)
    {
        $entity = Location::find($id);

        if (!$entity) {
            $response = new Response();
            $response->setStatusCode(404);
            return $response;
        }

        return $entity->toJson();
    }

    public function getAverage($id)
    {
        $query = Visit::select(DB::raw('COALESCE(AVG(mark), 0) as res'))
            ->where('location', '=', $id);

        if ($fromDate = request()->get('fromDate')) {
            $query->where('visited_at', '>', $fromDate);
        }
        if ($toDate = request()->get('toDate')) {
            $query->where('visited_at', '<', $toDate);
        }

        $fromAge = request()->get('fromAge');
        $toAge = request()->get('toAge');
        $gender = request()->get('gender');

        if ($fromAge || $toAge || $gender) {
            $query->join('profile', 'profile.id', '=', 'visit.user_id');

            if ($fromAge) {
                $query->where('profile.birth_date', '<', $fromAge);
            }
            if ($toAge) {
                $query->where('profile.birth_date', '>', $toAge);
            }
            if ($gender) {
                $query->where('profile.gender', '=', $gender);
            }
        }

        $res = $query->first();

        return '{
    "avg": ' . $res->res . '
}';
    }

    public function create()
    {
        Location::insert(request()->json()->all());
        return '{}';
    }

    public function update($id)
    {
        $entity = Location::find($id);

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