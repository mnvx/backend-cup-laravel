<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use ClickHouseDB\Client;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;

class ApiController extends Controller
{
    protected $redis;

    /** @var Client */
    protected $clickhouse;

    protected $table; // override it

    public function __construct()
    {
        $this->redis = App::make('Redis');
        $this->clickhouse = App::make('Clickhouse');
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
        $sql = 'SELECT * 
            FROM ' . $this->table . ' 
            WHERE id = ' . $id . ' 
            ORDER BY version DESC
            LIMIT 1';
        return $this->clickhouse->select($sql)->fetchOne();
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