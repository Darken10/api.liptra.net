<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class CityController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = City::query()->withCount('stations');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('region', 'like', "%{$search}%");
        }

        $cities = $query->orderBy('name')->paginate((int) $request->input('per_page', 15));

        return $this->success($cities);
    }

    public function show(City $city): JsonResponse
    {
        $city->loadCount('stations');

        return $this->success($city);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:cities'],
            'region' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['boolean'],
        ]);

        $city = City::query()->create($validated);

        return $this->created($city);
    }

    public function update(Request $request, City $city): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('cities')->ignore($city->id)],
            'region' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['boolean'],
        ]);

        $city->update($validated);

        return $this->success($city);
    }

    public function destroy(City $city): JsonResponse
    {
        $city->delete();

        return $this->noContent();
    }
}
