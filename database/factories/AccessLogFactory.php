<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\ResourceLibrary;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\ResourceLibrary\Enums\AccessAction;
use Kurt\Modules\ResourceLibrary\Models\AccessLog;

/**
 * @extends Factory<AccessLog>
 */
class AccessLogFactory extends Factory
{
    /** @var class-string<AccessLog> */
    protected $model = AccessLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'action' => AccessAction::Download,
            'ip' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'occurred_at' => now(),
        ];
    }
}
