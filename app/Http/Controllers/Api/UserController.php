<?php

namespace App\Http\Controllers\Api;

use App\Model\Entity\User;
use App\Model\Entity\Visit;
use Illuminate\Http\Request;
use Throwable;

class UserController extends ApiController
{
    public function get($id)
    {
        if (!$this->isCorrectId($id)) {
            return $this->get404();
        }

        $entity = User::find($id);

        if (!$entity) {
            return $this->get404();
        }

        return $this->jsonResponse($entity->toJson());
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

    public function create()
    {
        try {
            User::insert(request()->json()->all());
        }
        catch (Throwable $e) {
            return $this->get400();
        }
        return $this->jsonResponse('{}');
    }

    public function update($id)
    {
        if (!$this->isCorrectId($id)) {
            return $this->get404();
        }

        $entity = User::find($id);

        if (!$entity) {
            return $this->get404();
        }

        if (request()->json()->get('id')) {
            return $this->get400();
        }

        try {
            User::where('id', '=', $id)
                ->update(request()->json()->all());
        }
        catch (Throwable $e) {
            return $this->get400();
        }

        return $this->jsonResponse('{}');
    }

}