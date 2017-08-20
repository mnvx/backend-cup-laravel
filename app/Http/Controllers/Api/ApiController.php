<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Repository\AbstractRepository;
use Exception;
use Illuminate\Http\Request;
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

    /**
     * @var AbstractRepository
     */
    protected $repo;

    public function __construct()
    {
        $conn = new StreamConnection('tcp://' . env('TARANTOOL_HOST') . ':' . env('TARANTOOL_PORT'));
        $this->client = new Client($conn, new PurePacker());
        $this->space = $this->client->getSpace($this->spaceName);
        $repositoryClassName = '\\App\\Model\\Repository\\' . ucfirst($this->spaceName) . 'Repository';
        $this->repo = new $repositoryClassName;
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
            if ($entity = $this->repo->find($id)) {
                return $this->jsonResponse(json_encode($entity));
            }
            else {
                return $this->get404();
            }
        }
        catch (Exception $e) {
            return $this->get400($e->getMessage());
        }
    }

    /**
     * @param array $params
     * @return Response
     */
    public function insert($params)
    {
        if (!$this->isCorrectId($params['id'] ?? null)) {
            return $this->get400();
        }

        foreach ($params as $value) {
            if ($value === null) {
                return $this->get404();
            }
        }

        foreach ($params as $value) {
            if ($value === null) {
                return $this->get400();
            }
        }

        try {
            if ($this->repo->insert($params)) {
                return $this->jsonResponse('{}');
            }
            else {
                return $this->get404();
            }
        }
        catch (Exception $e) {
            return $this->get400($e->getMessage());
        }
    }

    /**
     * @param int $id
     * @param array $params
     * @param callable $validation
     * @param Request $request
     * @return Response
     */
    public function update($id, $params, $validation, $request)
    {
        if (!$this->isCorrectId($id)) {
            return $this->get404();
        }

        if (!$this->repo->exists($id)) {
            return $this->get404();
        }

        if (!$validation($request)) {
            return $this->get400();
        }

        if (isset($params['id'])) {
            return $this->get400();
        }

        foreach ($params as $value) {
            if ($value === null) {
                return $this->get400();
            }
        }

        try {
            if ($this->repo->update($id, $params)) {
                return $this->jsonResponse('{}');
            }
            else {
                return $this->get404();
            }
        }
        catch (Exception $e) {
            return $this->get400($e->getMessage());
        }
    }

    protected function isCorrectId($id)
    {
        return (string)(int)$id === (string)$id;
    }

    protected function get400($text = null)
    {
        $response = new Response();
        if ($text) {
            $response->header('error-text', $text);
        }
        $response->setStatusCode(400);
        return $response;
    }

    protected function get404()
    {
        $response = new Response();
        $response->setStatusCode(404);
        return $response;
    }

    protected function customValidate($request, $rules)
    {
        try {
            $this->validate($request, $rules);
        }
        catch (ValidationException $e) {
            return false;
        }
        return true;
    }

    protected function jsonResponse($json)
    {
        return response($json)
            ->header('Content-Type', 'application/json')
            ->header('Content-Length', strlen($json))
        ;
    }
}