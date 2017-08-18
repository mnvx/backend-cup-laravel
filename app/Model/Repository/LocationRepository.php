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

}