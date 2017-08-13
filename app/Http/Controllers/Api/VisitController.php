<?php

namespace App\Http\Controllers\Api;

use App\Model\Entity\Visit;
use Illuminate\Http\Response;

class VisitController extends ApiController
{
    public function get($id)
    {
        $entity = Visit::find($id);

        if (!$entity) {
            $response = new Response();
            $response->setStatusCode(404);
            return $response;
        }

        return $entity->toJson();
    }

    public function create()
    {
        Visit::insert(request()->json()->all());
        return '{}';
    }

    public function update($id)
    {
        $entity = Visit::find($id);

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