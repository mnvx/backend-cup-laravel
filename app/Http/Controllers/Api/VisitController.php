<?php

namespace App\Http\Controllers\Api;

use App\Model\Entity\Visit;
use Throwable;

class VisitController extends ApiController
{

    public function create()
    {
        try {
            Visit::insert(request()->json()->all());
        }
        catch (Throwable $e) {
            return $this->get400();
        }
        return $this->jsonResponse('{}');
    }

    public function edit($id)
    {
        if (!$this->isCorrectId($id)) {
            return $this->get404();
        }

        $entity = Visit::find($id);

        if (!$entity) {
            return $this->get404();
        }

        try {
            Visit::where('id', '=', $id)
                ->update(request()->json()->all());
        }
        catch (Throwable $e) {
            return $this->get400();
        }

        return $this->jsonResponse('{}');
    }

}