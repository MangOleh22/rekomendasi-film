<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('synopsis')->nullable();
            $table->integer('year');
            $table->decimal('rating', 3, 1)->default(0.0);
            $table->unsignedTinyInteger('age_limit')->default(0); // 0 (SU), 13 (13+), 17 (17+), 21 (21+)
            $table->string('poster_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['age_limit', 'rating']);
            $table->index('title');
        });

        Schema::create('genre_movie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $table->unique(['movie_id', 'genre_id']);
        });

        Schema::create('user_favorite_genres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'genre_id']);
            $table->timestamps();
        });

        Schema::create('watch_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->timestamp('watched_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'movie_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_histories');
        Schema::dropIfExists('user_favorite_genres');
        Schema::dropIfExists('genre_movie');
        Schema::dropIfExists('movies');
        Schema::dropIfExists('genres');
    }
};
