<?php

namespace App\Model\Repository;

use Exception;
use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Packer\PurePacker;

abstract class AbstractRepository
{
    /**
     * @var Client
     */
    protected $client;

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
    }

    /**
     * @param int $id
     */
    public function exists($id)
    {
        
    }

    /**
     * @param int $id
     * @return array Entity
     * @throws Exception
     */
    public function find($id)
    {
        $sql = 'SELECT * FROM ' . $this->spaceName . ' WHERE id = ' . $id;
        $data = $this->client
                ->evaluate('return box.sql.execute([[' . $sql . ';]])')
                ->getData()[0] ?? [];

        $entity = [];
        foreach ($data[0] ?? [] as $key => $value) {
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