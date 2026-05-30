<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_library_access_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('resource_library_items')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained(config('auth.providers.users.table', 'users'))->nullOnDelete();
            $table->string('action');
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_library_access_log');
    }
};
