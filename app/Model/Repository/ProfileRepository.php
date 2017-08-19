<?php

namespace App\Model\Repository;

use Exception;
use stdClass;

class ProfileRepository extends AbstractRepository
{
    protected $spaceName = 'profile';

    protected $fields = [
        1 => 'id',
        2 => 'email',
        3 => 'first_name',
        4 => 'last_name',
        5 => 'gender',
        6 => 'birth_date',
    ];

    /**
     * @param int $userId
     * @param array $params
     * @return array
     */
    public function getVisits($userId, $params)
    {
        $sql = 'SELECT mark, visited_at, place
            FROM visit 
            JOIN location on location.id = visit.location
            WHERE user = ' . $userId;

        if ($fromDate = $params['fromDate'] ?? null) {
            $sql .= ' AND visited_at > ' . $fromDate;
        }
        if ($toDate = $params['toDate'] ?? null) {
            $sql .= ' AND visited_at < ' . $toDate;
        }
        if ($country = $params['country'] ?? null) {
            $sql .= " AND country = '" . str_replace("'", "\\'", $country) . "'";
        }
        if ($distance = $params['toDistance'] ?? null) {
            $sql .= ' AND distance < ' . $distance;
        }
        $sql .= ' ORDER BY visited_at';

        $data = $this->client
            ->evaluate('return box.sql.execute([[' . $sql . ';]])')
            ->getData()[0] ?? [];

        // mapping
        $result = [];
        foreach ($data as $item) {
            $record = new StdClass();
            $record->mark = $item[0];
            $record->visited_at = $item[1];
            $record->place = $item[2];
            $result[] = $record;
        }
        return $result;
    }

    /**
     * @param array $params
     * @return boolean
     * @throws Exception
     */
    public function insert($params)
    {
        $sql = 'INSERT INTO ' . $this->spaceName . ' (' . implode(', ', $this->fields) . ')
        VALUES (' . $params['id'] . ', ' .
            "'" . $this->quote($params['email']) . "', " .
            "'" . $this->quote($params['first_name']) . "', " .
            "'" . $this->quote($params['last_name']) . "', " .
            "'" . $params['gender'] . "', " .
            $params['birth_date'] .
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

        $this->find($id);

        if (isset($params['id'])) {
            return false;
        }

        $sql = 'UPDATE ' . $this->spaceName . ' SET ';
        $set = [];
        if (isset($params['email'])) {
            $set[] = " email = '" . $this->quote($params['email']) . "'";
        }
        if (isset($params['first_name'])) {
            $set[] = " first_name = '" . $this->quote($params['first_name']) . "'";
        }
        if (isset($params['last_name'])) {
            $set[] = " last_name = '" . $this->quote($params['last_name']) . "'";
        }
        if (isset($params['gender'])) {
            $set[] = " gender = '" . $params['gender'] . "'";
        }
        if (isset($params['birth_date'])) {
            $set[] = " birth_date = " . $params['birth_date'];
        }
        $sql .= implode(', ', $set) . ' WHERE id = ' . $id;
        $this->client->evaluate('box.sql.execute([[' . $sql . ';]])');

        return true;
    }

}