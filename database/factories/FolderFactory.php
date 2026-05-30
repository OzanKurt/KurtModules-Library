<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\ResourceLibrary;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;
use Kurt\Modules\ResourceLibrary\Models\Folder;

/**
 * @extends Factory<Folder>
 */
class FolderFactory extends Factory
{
    /** @var class-string<Folder> */
    protected $model = Folder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        $slug = str($name)->slug()->toString();

        return [
            'parent_id' => null,
            'slug' => $slug,
            'name' => ['en' => $name],
            'path' => '/'.$slug,
            'depth' => 0,
            'position' => 0,
            'visibility' => FolderVisibility::Public,
            'item_count' => 0,
        ];
    }

    public function visibility(FolderVisibility $visibility): static
    {
        return $this->state(fn () => ['visibility' => $visibility]);
    }

    public function child(Folder $parent): static
    {
        return $this->state(function () use ($parent) {
            $name = $this->faker->unique()->words(2, true);
            $slug = str($name)->slug()->toString();

            return [
                'parent_id' => $parent->id,
                'slug' => $slug,
                'name' => ['en' => $name],
                'path' => $parent->path.'/'.$slug,
                'depth' => $parent->depth + 1,
            ];
        });
    }
}
