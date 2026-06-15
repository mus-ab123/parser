<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Jobs\ScrapeYandexReviewsJob;
use App\Models\Organization;
use App\Repositories\OrganizationRepositoryInterface;
use App\Repositories\ReviewRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(
        protected OrganizationRepositoryInterface $organizationRepository,
        protected ReviewRepositoryInterface $reviewRepository
    ) {}

    public function index(): JsonResponse
    {
        $organizations = $this->organizationRepository->all();
        return response()->json($organizations);
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $url = $request->input('url');

        preg_match('/\/org\/([^\/]+\/)?(\d+)/', $url, $matches);
        $yandexId = $matches[2];
        $cleanUrl = "https://yandex.ru/maps/org/" . ($matches[1] ?? '') . $yandexId . "/reviews/";

        $organization = $this->organizationRepository->findByYandexId($yandexId);
        $queued = false;

        if (!$organization) {
            $organization = $this->organizationRepository->create([
                'yandex_id' => $yandexId,
                'url' => $cleanUrl,
                'status' => 'pending',
            ]);

            ScrapeYandexReviewsJob::dispatch($organization);
            $queued = true;
        } else {
            $isStale = !$organization->last_scraped_at || $organization->last_scraped_at->diffInHours(now()) >= 24;
            
            if ($organization->status === 'failed' || $isStale) {
                $this->organizationRepository->update($organization, [
                    'status' => 'pending',
                    'error_message' => null
                ]);
                
                ScrapeYandexReviewsJob::dispatch($organization);
                $queued = true;
            }
        }

        return response()->json([
            'message' => $queued ? 'Организация добавлена, запущен сбор отзывов' : 'Организация уже отслеживается',
            'organization' => $organization,
            'queued' => $queued
        ]);
    }

    public function refresh(Organization $organization): JsonResponse
    {
        $isRateLimited = $organization->last_scraped_at && 
            $organization->last_scraped_at->diffInMinutes(now()) < 10 &&
            $organization->status === 'completed';

        if ($isRateLimited) {
            return response()->json([
                'message' => 'Обновление возможно не чаще одного раза в 10 минут.',
            ], 429);
        }

        $this->organizationRepository->update($organization, [
            'status' => 'pending',
            'error_message' => null
        ]);

        ScrapeYandexReviewsJob::dispatch($organization);

        return response()->json([
            'message' => 'Запущен процесс обновления данных',
            'organization' => $organization
        ]);
    }

    public function status(Organization $organization): JsonResponse
    {
        return response()->json([
            'id' => $organization->id,
            'yandex_id' => $organization->yandex_id,
            'name' => $organization->name,
            'status' => $organization->status,
            'error_message' => $organization->error_message,
            'rating' => $organization->rating,
            'review_count' => $organization->review_count,
            'rating_count' => $organization->rating_count,
            'last_scraped_at' => $organization->last_scraped_at?->toIso8601String(),
        ]);
    }

    public function reviews(Request $request, Organization $organization): JsonResponse
    {
        $rating = $request->has('rating') && $request->input('rating') !== 'all' 
            ? $request->integer('rating') 
            : null;

        $reviews = $this->reviewRepository->getPaginatedForOrganization($organization, $rating, 50);

        return response()->json([
            'organization' => $organization,
            'reviews' => $reviews
        ]);
    }
}
