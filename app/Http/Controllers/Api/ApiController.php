<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Schema\Space;

class ApiController extends Controller
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Space
     */
    protected $space;

    /**
     * @var string Abstract. Override it!
     */
    protected $spaceName;

    /**
     * @var array Abstract. Override it!
     */
    protected $fields;

    public function __construct()
    {
        $conn = new StreamConnection('tcp://' . env('TARANTOOL_HOST') . ':' . env('TARANTOOL_PORT'));
        $this->client = new Client($conn, new PurePacker());
        $this->space = $this->client->getSpace($this->spaceName);
    }

    /**
     * Process standard GET query
     * @param $id
     * @return Response
     */
    public function get($id)
    {
        if (!$this->isCorrectId($id)) {
            return $this->get404();
        }

        try {
            $data = $this->space->select([(int)$id])->getData();
        }
        catch (Exception $e) {
            return $this->get404();
        }

        $entity = [];
        foreach ($data[0] as $key => $value) {
            $entity[$this->fields[$key + 1]] = $value;
        }

        return $this->jsonResponse(json_encode($entity));
    }

    /**
     * @param array $params
     * @return Response
     */
    public function insert($params)
    {
        $values = [];
        foreach ($this->fields as $field) {
            $values[] = $params[$field];
        }
        try {
            $this->space->insert($values);
        }
        catch (Exception $e) {
            return $this->get400($e->getMessage());
        }
        return $this->jsonResponse('{}');
    }

    /**
     * @param int $id
     * @param array $params
     * @return Response
     */
    public function update($id, $params)
    {
        if (!$this->isCorrectId($id)) {
            return $this->get404();
        }
        $id = (int)$id;

        try {
            $this->space->select([$id]);
        }
        catch (Exception $e) {
            return $this->get404();
        }

        if (isset($params['id'])) {
            return $this->get400();
        }

        $values = [];
        foreach ($this->fields as $key => $field) {
            if (isset($params[$field])) {
                $values[] = ['=', $key - 1, $params[$field]];
            }
        }
        $this->space->update($id, $values);
        return $this->jsonResponse('{}');
    }


    public function isCorrectId($id)
    {
        return (string)(int)$id === (string)$id;
    }

    public function get400($text = null)
    {
        $response = new Response();
        if ($text) {
            $response->header('error-text', $text);
        }
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