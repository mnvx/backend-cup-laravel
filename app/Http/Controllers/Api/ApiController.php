<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use PDO;

class ApiController extends Controller
{
    protected $redis;

    protected $table; // override it

    public function __construct()
    {
        $this->redis = App::make('Redis');
    }

    public function get($id)
    {
        if (!ctype_digit($id)) {
            return new Response(null, 404);
        }

        $entity = $this->getRecord($id);

        if ($entity) {
            $entity = json_encode($entity);
            return (new Response($entity, 200, [
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($entity),
            ]));
        }

        return new Response(null, 404);
    }

    protected function getRecord($id)
    {
        /** @var PDO $pdo */
        $pdo = App::make('PDO');
        $sql = 'SELECT * FROM ' . $this->table . ' WHERE id = ' . $id;
        try {
            $entity = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
        }
        catch (\Throwable $e) {
            AppServiceProvider::$pdo = $pdo = DB::connection()->getPdo();
            $entity = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
        }
        return $entity;
    }

    public function get400()
    {
        return new Response(null, 400);
    }

    public function get404()
    {
        return new Response(null, 404);
    }

    public function jsonResponse($json)
    {
        return (new Response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($json),
            //'Connection' => 'Keep-alive',
        ]));
    }
}