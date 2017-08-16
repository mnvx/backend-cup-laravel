<?php

namespace App\Http\Controllers\Api;

use App\Model\Entity\User;
use App\Model\Entity\Visit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends ApiController
{
    protected $spaceName = 'profile';

    protected $fields = [
        1 => 'id',
        2 => 'email',
        3 => 'first_name',
        4 => 'last_name',
        5 => 'gender',
        6 => 'birth_date',
    ];

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

    /**
     * Process standard POST (new) query
     * @param Request $request
     * @return Response
     */
    public function create(Request $request)
    {
        if (!$this->customValidate($request, [
            'id' => 'required|int',
            'email' => 'required|max:100',
            'first_name' => 'required|max:50',
            'last_name' => 'required|max:50',
            'gender' => 'required|in:m,f',
            'birth_date' => 'required|int',
        ])) {
            return $this->get400();
        }

        return $this->insert($request->json()->all());
    }

    /**
     * Process standard POST (edit) query
     * @param int $id
     * @param Request $request
     * @return Response
     */
    public function edit($id, Request $request)
    {
        if (!$this->customValidate($request, [
            'email' => 'required|max:100',
            'first_name' => 'required|max:50',
            'last_name' => 'required|max:50',
            'gender' => 'required|in:m,f',
            'birth_date' => 'required|int',
        ])) {
            return $this->get400();
        }

        return $this->update($id, $request->json()->all());
    }

}