<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\JsonResponse;

final class CityController extends ApiController
{
    public function index(): JsonResponse
    {
        $cities = City::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->success(CityResource::collection($cities));
    }
}
