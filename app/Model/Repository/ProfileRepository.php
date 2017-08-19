<?php

namespace App\Model\Repository;

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

}