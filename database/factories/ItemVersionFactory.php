<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\ResourceLibrary;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\ResourceLibrary\Models\ItemVersion;

/**
 * @extends Factory<ItemVersion>
 */
class ItemVersionFactory extends Factory
{
    /** @var class-string<ItemVersion> */
    protected $model = ItemVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version' => 1,
            'media_path' => null,
            'external_url' => null,
            'mime_type' => null,
            'byte_size' => null,
            'checksum' => null,
            'changelog' => null,
        ];
    }
}
