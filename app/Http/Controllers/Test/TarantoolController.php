<?php

namespace App\Http\Controllers\Test;

use Illuminate\Http\Request;
use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Packer\PackUtils;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Request\SelectRequest;

class TarantoolController
{
    public function index(Request $request)
    {
        //$conn = new StreamConnection();
        $conn = new StreamConnection('tcp://' . env('TARANTOOL_HOST') . ':' . env('TARANTOOL_PORT'));


        $packer = new PurePacker();
        $client = new Client($conn, $packer);

        $client->evaluate('box.cfg()');
        $client->evaluate('box.sql.execute([[CREATE TABLE table1 (column1 INTEGER PRIMARY KEY, column2 VARCHAR(100));]])');
        $client->evaluate('box.sql.execute([[INSERT INTO table1 VALUES (1, \'A\');]])');
        $result = $client->evaluate('box.sql.execute([[SELECT * FROM table1;]])');
        var_dump($result->getData());
        return;

        $space = $client->getSpace('profile');

        // Selecting all data
        $result = $space->select([1]);
        var_dump($result->getData());


        $stream = $conn->stream;
        $tr = new SelectRequest($space->getId(), 0, [1], 0, 10000000, 0);
        $data = $packer->pack($tr);
        fwrite($stream, $data);
        $length = stream_get_contents($stream, 5);
        $length = PackUtils::unpackLength($length);
        var_dump($length);
        $data = stream_get_contents($stream, $length);
        $data = $packer->unpack($data);
        var_dump($data);

        //$space->update()

//        // Result: inserted tuple { 1, 'foo', 'bar' }
//        $space->insert([1, 'foo', 'bar']);
//
//        // Result: inserted tuple { 2, 'baz', 'qux'}
//        $space->upsert([2, 'baz', 'qux'], [['=', 1, 'BAZ'], ['=', 2, 'QUX']]);
//
//        // Result: updated tuple { 2, 'baz', 'qux'} with { 2, 'BAZ', 'QUX' }
//        $space->upsert([2, 'baz', 'qux'], [['=', 1, 'BAZ'], ['=', 2, 'QUX']]);

//        $result = $space->select([2]);
//        var_dump($result->getData());
    }
}