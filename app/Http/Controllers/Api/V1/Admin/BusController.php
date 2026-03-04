<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\BusResource;
use App\Models\Bus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class BusController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Bus::query()->with('company');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('registration_number', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if ($companyId = $request->input('company_id')) {
            $query->where('company_id', $companyId);
        }

        $buses = $query->latest()->paginate((int) $request->input('per_page', 15));

        return $this->success(BusResource::collection($buses)->response()->getData(true));
    }

    public function show(Bus $bus): JsonResponse
    {
        $bus->load('company', 'photos');

        return $this->success(new BusResource($bus));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'uuid', 'exists:companies,id'],
            'registration_number' => ['required', 'string', 'max:20', 'unique:buses'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'total_seats' => ['required', 'integer', 'min:1', 'max:100'],
            'comfort_type' => ['required', Rule::in(['vip', 'classique', 'ordinaire'])],
            'manufacture_year' => ['nullable', 'integer', 'min:1990', 'max:2030'],
            'color' => ['nullable', 'string', 'max:50'],
            'has_air_conditioning' => ['boolean'],
            'has_wifi' => ['boolean'],
            'has_usb_charging' => ['boolean'],
            'has_toilet' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $bus = Bus::query()->create($validated);
        $bus->load('company');

        return $this->created(new BusResource($bus));
    }

    public function update(Request $request, Bus $bus): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['sometimes', 'uuid', 'exists:companies,id'],
            'registration_number' => ['sometimes', 'string', 'max:20', Rule::unique('buses')->ignore($bus->id)],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'total_seats' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'comfort_type' => ['sometimes', Rule::in(['vip', 'classique', 'ordinaire'])],
            'manufacture_year' => ['nullable', 'integer', 'min:1990', 'max:2030'],
            'color' => ['nullable', 'string', 'max:50'],
            'has_air_conditioning' => ['boolean'],
            'has_wifi' => ['boolean'],
            'has_usb_charging' => ['boolean'],
            'has_toilet' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $bus->update($validated);
        $bus->load('company');

        return $this->success(new BusResource($bus));
    }

    public function destroy(Bus $bus): JsonResponse
    {
        $bus->delete();

        return $this->noContent();
    }
}
