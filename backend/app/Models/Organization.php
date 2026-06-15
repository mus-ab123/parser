<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'yandex_id',
        'url',
        'name',
        'rating',
        'review_count',
        'rating_count',
        'status',
        'error_message',
        'last_scraped_at',
    ];

    protected $casts = [
        'rating' => 'float',
        'review_count' => 'integer',
        'rating_count' => 'integer',
        'last_scraped_at' => 'datetime',
    ];

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
