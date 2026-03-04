<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class CompanyController extends ApiController
{
    public function index(): JsonResponse
    {
        $companies = Company::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate(15);

        return $this->success(CompanyResource::collection($companies)->response()->getData(true));
    }

    public function show(string $id): JsonResponse
    {
        $company = Company::query()
            ->with(['stations.city'])
            ->find($id);

        if (! $company) {
            return $this->notFound('Compagnie non trouvée');
        }

        return $this->success(new CompanyResource($company));
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(4);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('companies/logos', 'public');
        }

        $company = Company::query()->create($data);

        return $this->created(new CompanyResource($company), 'Compagnie créée avec succès');
    }

    public function update(StoreCompanyRequest $request, string $id): JsonResponse
    {
        $company = Company::query()->find($id);

        if (! $company) {
            return $this->notFound('Compagnie non trouvée');
        }

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('companies/logos', 'public');
        }

        $company->update($data);

        return $this->success(new CompanyResource($company->fresh()), 'Compagnie mise à jour');
    }

    public function destroy(string $id): JsonResponse
    {
        $company = Company::query()->find($id);

        if (! $company) {
            return $this->notFound('Compagnie non trouvée');
        }

        $company->update(['is_active' => false]);

        return $this->success(message: 'Compagnie désactivée');
    }
}
