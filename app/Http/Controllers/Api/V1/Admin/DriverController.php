<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class DriverController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Driver::query()->with('company');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('license_number', 'like', "%{$search}%");
            });
        }

        if ($companyId = $request->input('company_id')) {
            $query->where('company_id', $companyId);
        }

        $drivers = $query->latest()->paginate((int) $request->input('per_page', 15));

        return $this->success(DriverResource::collection($drivers)->response()->getData(true));
    }

    public function show(Driver $driver): JsonResponse
    {
        $driver->load('company', 'trips');

        return $this->success(new DriverResource($driver));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'uuid', 'exists:companies,id'],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'license_number' => ['required', 'string', 'max:50', 'unique:drivers'],
            'license_type' => ['nullable', 'string', 'max:10'],
            'license_expiry' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        $driver = Driver::query()->create($validated);
        $driver->load('company');

        return $this->created(new DriverResource($driver));
    }

    public function update(Request $request, Driver $driver): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['sometimes', 'uuid', 'exists:companies,id'],
            'firstname' => ['sometimes', 'string', 'max:255'],
            'lastname' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'license_number' => ['sometimes', 'string', 'max:50', Rule::unique('drivers')->ignore($driver->id)],
            'license_type' => ['nullable', 'string', 'max:10'],
            'license_expiry' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        $driver->update($validated);
        $driver->load('company');

        return $this->success(new DriverResource($driver));
    }

    public function destroy(Driver $driver): JsonResponse
    {
        $driver->delete();

        return $this->noContent();
    }
}
