<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class LocationController extends ApiController
{
    protected $spaceName = 'location';

    protected $fields = [
        1 => 'id',
        2 => 'place',
        3 => 'country',
        4 => 'city',
        5 => 'distance',
    ];

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

        $entity = $this->repo->find($id);

        if (!$entity) {
            return $this->get404();
        }

        $avg = $this->repo->getAverage($id, $request->all());
        return $this->jsonResponse('{"avg": ' . round($avg, 5) . '}');
    }

    public function create(Request $request)
    {
        if (!$this->customValidate($request, [
            'id' => 'required|int',
            'place' => 'required',
            'country' => 'required|max:50',
            'city' => 'required|max:50',
            'distance' => 'required|int',
        ])) {
            return $this->get400();
        }

        return $this->insert($request->json()->all());
    }

    public function edit($id, Request $request)
    {
        if (!$this->customValidate($request, [
            'place' => 'required',
            'country' => 'required|max:50',
            'city' => 'required|max:50',
            'distance' => 'required|int',
        ])) {
            return $this->get400();
        }

        return $this->update($id, $request->json()->all());
    }
}