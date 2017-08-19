<?php

namespace App\Model\Repository;

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

}