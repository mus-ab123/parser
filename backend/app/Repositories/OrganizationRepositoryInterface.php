<?php

namespace App\Repositories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;

interface OrganizationRepositoryInterface
{
    public function all(): Collection;
    
    public function find(int $id): ?Organization;
    
    public function findByYandexId(string $yandexId): ?Organization;
    
    public function create(array $data): Organization;
    
    public function update(Organization $organization, array $data): bool;
}
