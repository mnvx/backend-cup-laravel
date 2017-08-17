<?php

namespace App\Http\Controllers\Api;

use App\Model\Entity\Visit;
use Illuminate\Http\Request;
use Throwable;

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
//        if (!$this->customValidate($request, [
//            'id' => 'required|int',
//            'email' => 'required|max:100',
//            'first_name' => 'required|max:50',
//            'last_name' => 'required|max:50',
//            'gender' => 'required|in:m,f',
//            'birth_date' => 'required|int',
//        ])) {
//            return $this->get400();
//        }

        return $this->insert($request->json()->all());
    }

    public function edit($id, Request $request)
    {
//        if (!$this->customValidate($request, [
//            'email' => 'required|max:100',
//            'first_name' => 'required|max:50',
//            'last_name' => 'required|max:50',
//            'gender' => 'required|in:m,f',
//            'birth_date' => 'required|int',
//        ])) {
//            return $this->get400();
//        }

        return $this->update($id, $request->json()->all());
    }

}