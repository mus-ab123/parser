<?php

namespace App\Repositories\Eloquent;

use App\Models\Organization;
use App\Models\Review;
use App\Repositories\ReviewRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReviewRepository implements ReviewRepositoryInterface
{
    public function getPaginatedForOrganization(Organization $organization, ?int $rating, int $perPage = 50): LengthAwarePaginator
    {
        $query = $organization->reviews();

        if ($rating !== null) {
            $query->where('rating', $rating);
        }

        return $query->orderBy('published_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function deleteForOrganization(Organization $organization): bool
    {
        return $organization->reviews()->delete() >= 0;
    }

    public function insertMultiple(array $records): bool
    {
        if (empty($records)) {
            return true;
        }

        foreach (array_chunk($records, 100) as $chunk) {
            Review::insert($chunk);
        }

        return true;
    }
}
