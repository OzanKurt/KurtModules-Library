<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\ResourceLibrary;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\ResourceLibrary\Models\Tag;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    /** @var class-string<Tag> */
    protected $model = Tag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'slug' => str($name)->slug()->toString(),
            'name' => ['en' => $name],
            'color' => null,
        ];
    }
}
