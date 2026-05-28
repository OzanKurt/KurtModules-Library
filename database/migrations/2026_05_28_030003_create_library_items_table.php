<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('library_folders')->cascadeOnDelete();
            $table->string('slug');
            $table->json('title');
            $table->json('description')->nullable();
            $table->string('kind');
            $table->foreignId('owner_id')->constrained(config('auth.providers.users.table', 'users'))->restrictOnDelete();
            $table->foreignId('current_version_id')->nullable()->constrained('library_item_versions')->nullOnDelete();
            $table->string('external_url')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('byte_size')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->unsignedBigInteger('download_count')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['folder_id', 'slug']);
            $table->index('kind');
        });

        Schema::table('library_item_versions', function (Blueprint $table) {
            $table->foreign('item_id')->references('id')->on('library_items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('library_item_versions', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
        });
        Schema::dropIfExists('library_items');
    }
};
