<?php

namespace App\Repositories;

use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReviewRepositoryInterface
{
    public function getPaginatedForOrganization(Organization $organization, ?int $rating, int $perPage = 50): LengthAwarePaginator;
    
    public function deleteForOrganization(Organization $organization): bool;
    
    public function insertMultiple(array $records): bool;
}
