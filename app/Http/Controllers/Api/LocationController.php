<?php

namespace App\Http\Controllers\Api;

use App\Model\Entity\Location;
use App\Model\Entity\Visit;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class LocationController extends ApiController
{
    public function get($id)
    {
        if (!$this->isCorrectId($id)) {
            return $this->get404();
        }

        $entity = Location::find($id);

        if (!$entity) {
            return $this->get404();
        }

        return $this->jsonResponse($entity->toJson());
    }

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

            if ($fromAge && $toAge) {
                $from = strtotime((new Datetime())->sub(new DateInterval('P' . $fromAge . 'Y'))->format('Y-m-d H:i:s'));
                $to = strtotime((new Datetime())->sub(new DateInterval('P' . $toAge . 'Y'))->format('Y-m-d H:i:s'));
                $query->whereRaw('profile.birth_date BETWEEN ' . $to . ' AND ' . $from);
            }
            elseif ($fromAge) {
                $from = strtotime((new Datetime())->sub(new DateInterval('P' . $fromAge . 'Y'))->format('Y-m-d H:i:s'));
                $query->whereRaw('profile.birth_date < ' . $from);
            }
            elseif ($toAge) {
                $to = strtotime((new Datetime())->sub(new DateInterval('P' . $toAge . 'Y'))->format('Y-m-d H:i:s'));
                $query->whereRaw('profile.birth_date > ' . $to);
            }

            if ($gender) {
                $query->where('profile.gender', '=', $gender);
            }
        }

        $res = $query->first();

        return $this->jsonResponse('{"avg": ' . round($res->res, 5) . '}');
    }

    public function create(ServerRequestInterface $request)
    {
        $requestData = $request->getParsedBody();

        try {
            Location::insert($requestData);
        }
        catch (Throwable $e) {
            return $this->get400();
        }
        return $this->jsonResponse('{}');
    }

    public function update($id, ServerRequestInterface $request)
    {
        $requestData = $request->getParsedBody();

        if (!$this->isCorrectId($id)) {
            return $this->get404();
        }

        $entity = Location::find($id);

        if (!$entity) {
            return $this->get404();
        }

        if (isset($requestData['id'])) {
            return $this->get400();
        }

        try {
            Location::where('id', '=', $id)
                ->update($requestData);
        }
        catch (Throwable $e) {
            return $this->get400();
        }

        return $this->jsonResponse('{}');
    }
}