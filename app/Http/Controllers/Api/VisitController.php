<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class VisitController extends ApiController
{
    protected $spaceName = 'visit';

    protected $fields = [
        1 => 'id',
        2 => 'location',
        3 => 'user',
        4 => 'visited_at',
        5 => 'mark',
    ];

    public function create(Request $request)
    {
        $validation = function ($request) {
            return $this->customValidate($request, [
                'id' => 'required|int',
                'location' => 'required|int',
                'user' => 'required|int',
                'visited_at' => 'required|int|min:946674000|max:1420145999',
                'mark' => 'required|int|min:0|max:5',
            ]);
        };

        return $this->insert($request->json()->all(), $validation, $request);
    }

    public function edit($id, Request $request)
    {
        $validation = function ($request) {
            return $this->customValidate($request, [
                'location' => 'int',
                'user' => 'int',
                'visited_at' => 'int|min:946674000|max:1420145999',
                'mark' => 'int|min:0|max:5',
            ]);
        };

        return $this->update($id, $request->json()->all(), $validation, $request);
    }

}