<?php

namespace App\Http\Controllers\Api;

use App\Model\Entity\Location;
use App\Model\Entity\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends ApiController
{
    public function get($id)
    {
        if (!$this->isCorrectId($id)) {
            return $this->get404();
        }

        $entity = Location::find($id);

        if (!$entity) {
            return $this->get404();
        }

        return $this->jsonResponse($entity->toJson());
    }

    public function getAverage($id, Request $request)
    {
        if (!$this->customValidate($request, [
            'fromDate' => 'int',
            'toDate' => 'int',
            'fromAge' => 'int',
            'toAge' => 'int',
            'gender' => 'in:m,f',
        ])) {
            return $this->get400();
        }

        if (!$this->isCorrectId($id)) {
            return $this->get404();
        }

        $entity = Location::find($id);

        if (!$entity) {
            return $this->get404();
        }

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
            $query->join('profile', 'profile.id', '=', 'visit.user');

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

        return $this->jsonResponse('{
    "avg": ' . round($res->res, 5) . '
}');
    }

    public function create()
    {
        Location::insert(request()->json()->all());
        return $this->jsonResponse('{}');
    }

    public function update($id)
    {
        if (!$this->isCorrectId($id)) {
            return $this->get404();
        }

        $entity = Location::find($id);

        if (!$entity) {
            return $this->get404();
        }

        $entity->where('id', '=', $id)
            ->update(request()->json()->all());

        return $this->jsonResponse('{}');
    }
}