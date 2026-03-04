<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\RouteResource;
use App\Models\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RouteController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Route::query()->with('company', 'departureCity', 'arrivalCity');

        if ($companyId = $request->input('company_id')) {
            $query->where('company_id', $companyId);
        }

        if ($departureCityId = $request->input('departure_city_id')) {
            $query->where('departure_city_id', $departureCityId);
        }

        if ($arrivalCityId = $request->input('arrival_city_id')) {
            $query->where('arrival_city_id', $arrivalCityId);
        }

        $routes = $query->latest()->paginate((int) $request->input('per_page', 15));

        return $this->success(RouteResource::collection($routes)->response()->getData(true));
    }

    public function show(Route $route): JsonResponse
    {
        $route->load('company', 'departureCity', 'arrivalCity', 'trips');

        return $this->success(new RouteResource($route));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'uuid', 'exists:companies,id'],
            'departure_city_id' => ['required', 'uuid', 'exists:cities,id'],
            'arrival_city_id' => ['required', 'uuid', 'exists:cities,id', 'different:departure_city_id'],
            'distance_km' => ['nullable', 'integer', 'min:1'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $route = Route::query()->create($validated);
        $route->load('company', 'departureCity', 'arrivalCity');

        return $this->created(new RouteResource($route));
    }

    public function update(Request $request, Route $route): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['sometimes', 'uuid', 'exists:companies,id'],
            'departure_city_id' => ['sometimes', 'uuid', 'exists:cities,id'],
            'arrival_city_id' => ['sometimes', 'uuid', 'exists:cities,id'],
            'distance_km' => ['nullable', 'integer', 'min:1'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $route->update($validated);
        $route->load('company', 'departureCity', 'arrivalCity');

        return $this->success(new RouteResource($route));
    }

    public function destroy(Route $route): JsonResponse
    {
        $route->delete();

        return $this->noContent();
    }
}
