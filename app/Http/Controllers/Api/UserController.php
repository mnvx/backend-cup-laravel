<?php

namespace App\Http\Controllers\Api;

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

        $entity = $this->repo->find($id);

        if (!$entity) {
            return $this->get404();
        }

        $data = $this->repo->getVisits($id, $request->all());
        return $this->jsonResponse('{"visits": ' . json_encode($data) . '}');
    }

    /**
     * Process standard POST (new) query
     * @param Request $request
     * @return Response
     */
    public function create(Request $request)
    {
        $validation = function ($request) {
            return $this->customValidate($request, [
                'id' => 'required|int',
                'email' => 'required|max:100',
                'first_name' => 'required|max:50',
                'last_name' => 'required|max:50',
                'gender' => 'required|in:m,f',
                'birth_date' => 'required|int',
            ]);
        };

        return $this->insert($request->json()->all(), $validation, $request);
    }

    /**
     * Process standard POST (edit) query
     * @param int $id
     * @param Request $request
     * @return Response
     */
    public function edit($id, Request $request)
    {
        $validation = function ($request) {
            return $this->customValidate($request, [
                'email'      => 'max:100',
                'first_name' => 'max:50',
                'last_name'  => 'max:50',
                'gender'     => 'in:m,f',
                'birth_date' => 'int',
            ]);
        };

        return $this->update($id, $request->json()->all(), $validation, $request);
    }

}