<?php

namespace App\Http\Controllers\Api;

use App\Model\Entity\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Throwable;

class VisitController extends ApiController
{
    protected $collection = 'visit';

    public function create(Request $request)
    {
        $requestData = $request->json()->all();
        try {
            Visit::insert($requestData);
        }
        catch (Throwable $e) {
            return $this->get400();
        }

        $redis = App::make('Redis');
        $redis->hset($this->collection, $requestData['id'], $request->getContent());

        return $this->jsonResponse('{}');
    }

    public function update($id, Request $request)
    {
        $requestData = $request->json()->all();

        if (!ctype_digit($id)) {
            return $this->get404();
        }

        $entity = Visit::find($id);

        if (!$entity) {
            return $this->get404();
        }

        if (isset($requestData['id'])) {
            return $this->get400();
        }

        try {
            Visit::where('id', '=', $id)
                ->update($requestData);
        }
        catch (Throwable $e) {
            return $this->get400();
        }

        $redis = App::make('Redis');
        $redis->hset($this->collection, $id, json_encode($requestData + $entity->toArray()));

        return $this->jsonResponse('{}');
    }

}