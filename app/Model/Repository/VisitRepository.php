<?php

namespace App\Model\Repository;

use Exception;

class VisitRepository extends AbstractRepository
{
    protected $spaceName = 'visit';

    protected $fields = [
        1 => 'id',
        2 => 'location',
        3 => 'user',
        4 => 'visited_at',
        5 => 'mark',
    ];

    /**
     * @param array $params
     * @return boolean
     * @throws Exception
     */
    public function insert($params)
    {
        $sql = 'INSERT INTO ' . $this->spaceName . ' (' . implode(', ', $this->fields) . ')
        VALUES (' . $params['id'] . ', ' .
            "'" . $this->quote($params['location']) . "', " .
            $params['user'] . ', ' .
            $params['visited_at'] . ', ' .
            $params['mark'] .
            ')';
        $this->client->evaluate('box.sql.execute([[' . $sql . ';]])');
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

        if (!$this->find($id)) {
            return false;
        }

        if (isset($params['id'])) {
            return false;
        }

        $sql = 'UPDATE ' . $this->spaceName . ' SET ';
        $set = [];
        if (isset($params['location'])) {
            $set[] = " location = " . $params['location'];
        }
        if (isset($params['user'])) {
            $set[] = " user = " . $params['user'];
        }
        if (isset($params['visited_at'])) {
            $set[] = " visited_at = " . $params['visited_at'];
        }
        if (isset($params['mark'])) {
            $set[] = " mark = " . $params['mark'];
        }
        $sql .= implode(', ', $set) . ' WHERE id = ' . $id;
        $this->client->evaluate('box.sql.execute([[' . $sql . ';]])');

        return true;
    }

}