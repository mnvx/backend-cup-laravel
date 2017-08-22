<?php

namespace App\Http\Controllers\Api;

use App\Model\Entity\Visit;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class VisitController extends ApiController
{
    public function get($id)
    {
        if (!$this->isCorrectId($id)) {
            return $this->get404();
        }

        $entity = Visit::find($id);

        if (!$entity) {
            return $this->get404();
        }

        return $this->jsonResponse($entity->toJson());
    }

    public function create(ServerRequestInterface $request)
    {
        $requestData = $request->getParsedBody();
        try {
            Visit::insert($requestData);
        }
        catch (Throwable $e) {
            return $this->get400();
        }
        return $this->jsonResponse('{}');
    }

    public function update($id, ServerRequestInterface $request)
    {
        $requestData = $request->getParsedBody();

        if (!$this->isCorrectId($id)) {
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

        return $this->jsonResponse('{}');
    }

}