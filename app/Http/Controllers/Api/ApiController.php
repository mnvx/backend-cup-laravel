<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;

class ApiController extends Controller
{
    public function get($id)
    {
        if (!ctype_digit($id)) {
            return $this->get404();
        }

        if ($entity = App::make('Redis')->hget($this->collection, $id)) {
            return $this->jsonResponse($entity);
        }

        return $this->get404();
    }

    public function get400()
    {
        $response = new Response();
        $response->setStatusCode(400);
        return $response;
    }

    public function get404()
    {
        $response = new Response();
        $response->setStatusCode(404);
        return $response;
    }

    public function jsonResponse($json)
    {
        return response($json)
            ->header('Content-Type', 'application/json')
            ->header('Content-Length', strlen($json))
        ;
    }
}