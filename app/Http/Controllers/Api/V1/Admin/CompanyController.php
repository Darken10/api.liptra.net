<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class CompanyController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Company::query()->withCount('stations', 'buses', 'drivers');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $companies = $query->orderBy('name')->paginate((int) $request->input('per_page', 15));

        return $this->success(CompanyResource::collection($companies)->response()->getData(true));
    }

    public function show(Company $company): JsonResponse
    {
        $company->load('stations.city')->loadCount('stations', 'buses', 'drivers');

        return $this->success(new CompanyResource($company));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:companies'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']).'-'.Str::random(4);

        $company = Company::query()->create($validated);

        return $this->created(new CompanyResource($company));
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('companies')->ignore($company->id)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        if (isset($validated['name']) && $validated['name'] !== $company->name) {
            $validated['slug'] = Str::slug($validated['name']).'-'.Str::random(4);
        }

        $company->update($validated);

        return $this->success(new CompanyResource($company->fresh()));
    }

    public function destroy(Company $company): JsonResponse
    {
        $company->delete();

        return $this->noContent();
    }
}
