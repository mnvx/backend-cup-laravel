<?php

namespace App\Model\Repository;

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

}