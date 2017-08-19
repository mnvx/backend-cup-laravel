<?php

namespace App\Model\Repository;

use Exception;
use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Schema\Space;

class AbstractRepository
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
     * @param int $id
     * @return array Entity
     * @throws Exception
     */
    public function find($id)
    {
        if (!$this->isCorrectId($id)) {
            throw new Exception;
        }

        $data = $this->space->select([(int)$id])->getData();

        $entity = [];
        foreach ($data[0] ?? [] as $key => $value) {
            $entity[$this->fields[$key + 1]] = $value;
        }

        return $entity;
    }

    /**
     * @param array $params
     * @return boolean
     * @throws Exception
     */
    public function insert($params)
    {
        $values = [];
        foreach ($this->fields as $field) {
            $values[] = $params[$field];
        }

        $this->space->insert($values);

        return true;
    }

    /**
     * @param int $id
     * @param array $params
     * @return boolean
     * @throws Exception
     */
    public function update($id, $params)
    {
        if (!$this->isCorrectId($id)) {
            throw new Exception;
        }
        $id = (int)$id;

        $this->space->select([$id]);

        if (isset($params['id'])) {
            return false;
        }

        $values = [];
        foreach ($this->fields as $key => $field) {
            if (isset($params[$field])) {
                $values[] = ['=', $key - 1, $params[$field]];
            }
        }
        $this->space->update($id, $values);

        return true;
    }

    protected function isCorrectId($id)
    {
        return (string)(int)$id === (string)$id;
    }
}