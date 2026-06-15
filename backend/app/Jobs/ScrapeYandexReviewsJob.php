<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Repositories\OrganizationRepositoryInterface;
use App\Repositories\ReviewRepositoryInterface;
use App\Services\YandexScraperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScrapeYandexReviewsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public function __construct(
        protected Organization $organization
    ) {}

    public function handle(
        YandexScraperService $scraperService,
        OrganizationRepositoryInterface $organizationRepository,
        ReviewRepositoryInterface $reviewRepository
    ): void {
        $organizationRepository->update($this->organization, [
            'status' => 'processing',
            'error_message' => null,
        ]);

        try {
            $data = $scraperService->scrape($this->organization->url, 600);

            $orgInfo = $data['orgInfo'] ?? [];
            $reviews = $data['reviews'] ?? [];

            DB::transaction(function () use ($orgInfo, $reviews, $organizationRepository, $reviewRepository) {
                $organizationRepository->update($this->organization, [
                    'name' => $orgInfo['name'] ?? $this->organization->name ?? 'Неизвестная организация',
                    'rating' => $orgInfo['rating'] ?? $this->organization->rating,
                    'review_count' => $orgInfo['reviewCount'] ?? $this->organization->review_count,
                    'rating_count' => $orgInfo['ratingCount'] ?? $this->organization->rating_count,
                    'status' => 'completed',
                    'error_message' => null,
                    'last_scraped_at' => Carbon::now(),
                ]);

                $reviewRepository->deleteForOrganization($this->organization);

                $reviewRecords = [];
                foreach ($reviews as $rev) {
                    $publishedAt = null;
                    if (!empty($rev['datePublished'])) {
                        try {
                            $publishedAt = Carbon::parse($rev['datePublished']);
                        } catch (\Exception $e) {
                            Log::warning("Date parse failed: " . $rev['datePublished']);
                        }
                    }

                    $reviewRecords[] = [
                        'organization_id' => $this->organization->id,
                        'yandex_review_id' => $rev['yandexId'],
                        'author_name' => $rev['author'] ?? 'Аноним',
                        'rating' => $rev['rating'] ?? 5,
                        'text' => $rev['text'] ?? '',
                        'published_at' => $publishedAt,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                }

                $reviewRepository->insertMultiple($reviewRecords);
            });

        } catch (\Exception $e) {
            $organizationRepository->update($this->organization, [
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'last_scraped_at' => Carbon::now(),
            ]);
        }
    }
}
