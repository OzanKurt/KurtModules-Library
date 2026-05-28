<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_item_tag', function (Blueprint $table) {
            $table->foreignId('item_id')->constrained('library_items')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('library_tags')->cascadeOnDelete();
            $table->primary(['item_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_item_tag');
    }
};
