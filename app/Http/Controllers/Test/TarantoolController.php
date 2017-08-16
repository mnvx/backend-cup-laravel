<?php

namespace App\Http\Controllers\Test;

use Illuminate\Http\Request;
use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Packer\PurePacker;

class TarantoolController
{
    public function index(Request $request)
    {
        //$conn = new StreamConnection();
        $conn = new StreamConnection('tcp://' . env('TARANTOOL_HOST') . ':' . env('TARANTOOL_PORT'));

        $client = new Client($conn, new PurePacker());
        $space = $client->getSpace('profile');

        // Selecting all data
        $result = $space->select([1]);
        var_dump($result->getData());
        //$space->update()

//        // Result: inserted tuple { 1, 'foo', 'bar' }
//        $space->insert([1, 'foo', 'bar']);
//
//        // Result: inserted tuple { 2, 'baz', 'qux'}
//        $space->upsert([2, 'baz', 'qux'], [['=', 1, 'BAZ'], ['=', 2, 'QUX']]);
//
//        // Result: updated tuple { 2, 'baz', 'qux'} with { 2, 'BAZ', 'QUX' }
//        $space->upsert([2, 'baz', 'qux'], [['=', 1, 'BAZ'], ['=', 2, 'QUX']]);

        $result = $space->select([1]);
        var_dump($result->getData());
    }
}