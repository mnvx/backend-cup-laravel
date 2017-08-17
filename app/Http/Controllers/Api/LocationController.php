<?php

namespace App\Http\Controllers\Api;

use App\Model\Entity\Location;
use App\Model\Entity\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class LocationController extends ApiController
{
    protected $spaceName = 'location';

    protected $fields = [
        1 => 'id',
        2 => 'place',
        3 => 'country',
        4 => 'city',
        5 => 'distance',
    ];

    public function getAverage($id, Request $request)
    {
        if (!$this->customValidate($request, [
            'fromDate' => 'int',
            'toDate' => 'int',
            'fromAge' => 'int',
            'toAge' => 'int',
            'gender' => 'in:m,f',
        ])) {
            return $this->get400();
        }

        if (!$this->isCorrectId($id)) {
            return $this->get404();
        }

        $entity = Location::find($id);

        if (!$entity) {
            return $this->get404();
        }

        $query = Visit::select(DB::raw('COALESCE(AVG(mark), 0) as res'))
            ->where('location', '=', $id);

        if ($fromDate = request()->get('fromDate')) {
            $query->where('visited_at', '>', $fromDate);
        }
        if ($toDate = request()->get('toDate')) {
            $query->where('visited_at', '<', $toDate);
        }

        $fromAge = request()->get('fromAge');
        $toAge = request()->get('toAge');
        $gender = request()->get('gender');

        if ($fromAge || $toAge || $gender) {
            $query->join('profile', 'profile.id', '=', 'visit.user');

            $time = time();
            if ($fromAge && $toAge) {
                $query->whereRaw('profile.birth_date BETWEEN ' . ($time - $fromAge * 31536000) . ' AND ' . ($time - $toAge * 31536000));
            }
            elseif ($fromAge) {
                $query->whereRaw('profile.birth_date < ' . ($time - $fromAge * 31536000));
            }
            elseif ($toAge) {
                $query->whereRaw('profile.birth_date > ' . ($time - $toAge * 31536000));
            }

            if ($gender) {
                $query->where('profile.gender', '=', $gender);
            }
        }

        $res = $query->first();

        return $this->jsonResponse('{"avg": ' . round($res->res, 5) . '}');
    }

    public function create(Request $request)
    {
//        if (!$this->customValidate($request, [
//            'id' => 'required|int',
//            'email' => 'required|max:100',
//            'first_name' => 'required|max:50',
//            'last_name' => 'required|max:50',
//            'gender' => 'required|in:m,f',
//            'birth_date' => 'required|int',
//        ])) {
//            return $this->get400();
//        }

        return $this->insert($request->json()->all());
    }

    public function edit($id, Request $request)
    {
//        if (!$this->customValidate($request, [
//            'email' => 'required|max:100',
//            'first_name' => 'required|max:50',
//            'last_name' => 'required|max:50',
//            'gender' => 'required|in:m,f',
//            'birth_date' => 'required|int',
//        ])) {
//            return $this->get400();
//        }

        return $this->update($id, $request->json()->all());
    }
}