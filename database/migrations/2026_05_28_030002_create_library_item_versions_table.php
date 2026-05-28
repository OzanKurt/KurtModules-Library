<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_item_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id'); // FK added later (after items table exists)
            $table->unsignedInteger('version');
            $table->string('external_url')->nullable();
            $table->string('media_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('byte_size')->nullable();
            $table->string('checksum')->nullable();
            $table->text('changelog')->nullable();
            $table->foreignId('created_by')->constrained(config('auth.providers.users.table', 'users'))->restrictOnDelete();
            $table->timestamps();
            $table->unique(['item_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_item_versions');
    }
};
