<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    public function __construct()
    {
        //response()->header('Content-Transfer-Encoding', 'binary');
    }

    public function isCorrectId($id)
    {
        return (string)(int)$id === (string)$id;
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

    public function customValidate($request, $rules)
    {
        try {
            $this->validate($request, $rules);
        }
        catch (ValidationException $e) {
            return false;
        }
        return true;
    }

    public function jsonResponse($json)
    {
        return response($json)
            ->header('Content-Type', 'application/json')
            ->header('Content-Length', strlen($json))
        ;
    }
}