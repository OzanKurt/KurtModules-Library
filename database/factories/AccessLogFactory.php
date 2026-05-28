<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Library;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Library\Enums\AccessAction;
use Kurt\Modules\Library\Models\AccessLog;

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
