<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_library_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('resource_library_folders')->restrictOnDelete();
            $table->string('slug');
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('path')->index();
            $table->unsignedInteger('depth')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->string('visibility')->default('public');
            $table->foreignId('owner_id')->constrained(config('auth.providers.users.table', 'users'))->restrictOnDelete();
            $table->unsignedBigInteger('item_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['parent_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_library_folders');
    }
};
