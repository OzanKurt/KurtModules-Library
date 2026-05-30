<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\ResourceLibrary;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\ResourceLibrary\Enums\ItemKind;
use Kurt\Modules\ResourceLibrary\Models\Item;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /** @var class-string<Item> */
    protected $model = Item::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(3);

        return [
            'slug' => str($title)->slug()->toString(),
            'title' => ['en' => $title],
            'description' => ['en' => $this->faker->sentence(20)],
            'kind' => ItemKind::File,
            'view_count' => 0,
            'download_count' => 0,
        ];
    }

    public function kind(ItemKind $kind): static
    {
        return $this->state(fn () => ['kind' => $kind]);
    }

    public function videoLink(string $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'): static
    {
        return $this->state(fn () => [
            'kind' => ItemKind::VideoLink,
            'external_url' => $url,
        ]);
    }

    public function externalUrl(string $url = 'https://example.com/resource'): static
    {
        return $this->state(fn () => [
            'kind' => ItemKind::ExternalUrl,
            'external_url' => $url,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn () => ['published_at' => now()]);
    }
}
