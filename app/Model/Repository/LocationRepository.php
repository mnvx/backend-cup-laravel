<?php

namespace App\Model\Repository;

use Exception;

class LocationRepository extends AbstractRepository
{
    protected $spaceName = 'location';

    protected $fields = [
        1 => 'id',
        2 => 'place',
        3 => 'country',
        4 => 'city',
        5 => 'distance',
    ];

    /**
     * @param int $locationId
     * @param array $params
     * @return float
     */
    public function getAverage($locationId, $params)
    {
        $sql = 'SELECT COALESCE(AVG(mark), 0) as res
            FROM visit';
        $where = ' WHERE location = ' . $locationId;

        if ($fromDate = $params['fromDate'] ?? null) {
            $where .= ' AND visited_at > ' . $fromDate;
        }
        if ($toDate = $params['toDate'] ?? null) {
            $where .= ' AND visited_at < ' . $toDate;
        }

        $fromAge = $params['fromAge'] ?? null;
        $toAge = $params['toAge'] ?? null;
        $gender = $params['gender'] ?? null;

        if ($fromAge || $toAge || $gender) {
            $sql .= ' JOIN profile ON profile.id = visit.user';

            $time = time();
            if ($fromAge && $toAge) {
                $where .= ' AND profile.birth_date BETWEEN ' . ($time - $fromAge * 31536000) . ' AND ' . ($time - $toAge * 31536000);
            }
            elseif ($fromAge) {
                $where .= ' AND profile.birth_date < ' . ($time - $fromAge * 31536000);
            }
            elseif ($toAge) {
                $where .= ' AND profile.birth_date > ' . ($time - $toAge * 31536000);
            }

            if ($gender) {
                $where .= " AND profile.gender = '" . $gender . "'";
            }
        }

        $sql .= $where;

        return $this->client
            ->evaluate('return box.sql.execute([[' . $sql . ';]])')
            ->getData()[0][0][0] ?? 0;

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
            "'" . $this->quote($params['place']) . "', " .
            "'" . $this->quote($params['country']) . "', " .
            "'" . $this->quote($params['city']) . "', " .
            $params['distance'] .
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

        $sql = 'UPDATE ' . $this->spaceName . ' SET ';
        $set = [];
        if (isset($params['place'])) {
            $set[] = " place = '" . $this->quote($params['place']) . "'";
        }
        if (isset($params['country'])) {
            $set[] = " country = '" . $this->quote($params['country']) . "'";
        }
        if (isset($params['city'])) {
            $set[] = " city = '" . $this->quote($params['city']) . "'";
        }
        if (isset($params['distance'])) {
            $set[] = " distance = " . $params['distance'];
        }
        $sql .= implode(', ', $set) . ' WHERE id = ' . $id;
        $this->client->evaluate('box.sql.execute([[' . $sql . ';]])');

        return true;
    }
}