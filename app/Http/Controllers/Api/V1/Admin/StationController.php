<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Station::query()->with('city', 'company');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($cityId = $request->input('city_id')) {
            $query->where('city_id', $cityId);
        }

        if ($companyId = $request->input('company_id')) {
            $query->where('company_id', $companyId);
        }

        $stations = $query->latest()->paginate((int) $request->input('per_page', 15));

        return $this->success($stations);
    }

    public function show(Station $station): JsonResponse
    {
        $station->load('city', 'company');

        return $this->success($station);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'uuid', 'exists:cities,id'],
            'company_id' => ['required', 'uuid', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['boolean'],
        ]);

        $station = Station::query()->create($validated);
        $station->load('city', 'company');

        return $this->created($station);
    }

    public function update(Request $request, Station $station): JsonResponse
    {
        $validated = $request->validate([
            'city_id' => ['sometimes', 'uuid', 'exists:cities,id'],
            'company_id' => ['sometimes', 'uuid', 'exists:companies,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['boolean'],
        ]);

        $station->update($validated);
        $station->load('city', 'company');

        return $this->success($station);
    }

    public function destroy(Station $station): JsonResponse
    {
        $station->delete();

        return $this->noContent();
    }
}
