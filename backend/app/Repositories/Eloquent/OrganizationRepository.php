<?php

namespace App\Repositories\Eloquent;

use App\Models\Organization;
use App\Repositories\OrganizationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class OrganizationRepository implements OrganizationRepositoryInterface
{
    public function all(): Collection
    {
        return Organization::orderBy('updated_at', 'desc')->get();
    }

    public function find(int $id): ?Organization
    {
        return Organization::find($id);
    }

    public function findByYandexId(string $yandexId): ?Organization
    {
        return Organization::where('yandex_id', $yandexId)->first();
    }

    public function create(array $data): Organization
    {
        return Organization::create($data);
    }

    public function update(Organization $organization, array $data): bool
    {
        return $organization->update($data);
    }
}
