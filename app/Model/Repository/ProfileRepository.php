<?php

namespace App\Model\Repository;

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

}