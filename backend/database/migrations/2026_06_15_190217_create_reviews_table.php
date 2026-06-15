<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('yandex_review_id')->index();
            $table->string('author_name');
            $table->unsignedTinyInteger('rating');
            $table->text('text')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'yandex_review_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
