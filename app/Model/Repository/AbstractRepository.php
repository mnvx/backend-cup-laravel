<?php

namespace App\Model\Repository;

use Exception;
use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Schema\Space;

abstract class AbstractRepository
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
     * @return bool
     */
    public function exists($id)
    {
        try {
            $data = $this->space->select([(int)$id])->getData();
            if (empty($data)) {
                return false;
            }
        }
        catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * @param int $id
     * @return array Entity
     * @throws Exception
     */
    public function find($id)
    {
        try {
            $data = $this->space->select([(int)$id])->getData()[0] ?? null;
            if (empty($data)) {
                return null;
            }
        }
        catch (Exception $e) {
            return null;
        }

        $entity = [];
        foreach ($data as $key => $value) {
            $entity[$this->fields[$key + 1]] = $value;
        }

        if (empty($entity)) {
            throw new Exception;
        }

        return $entity;
    }

    /**
     * @param array $params
     * @return boolean
     * @throws Exception
     */
    abstract public function insert($params);

    /**
     * @param int $id
     * @param array $params
     * @return boolean
     * @throws Exception
     */
    abstract public function update($id, $params);

    protected function isCorrectId($id)
    {
        return (string)(int)$id === (string)$id;
    }

    protected function quote($value)
    {
        return str_replace("'", "\\'", $value);
    }
}