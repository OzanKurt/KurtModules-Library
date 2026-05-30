<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\ResourceLibrary;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Enums\PermissionSubjectType;
use Kurt\Modules\ResourceLibrary\Models\FolderPermission;

/**
 * @extends Factory<FolderPermission>
 */
class FolderPermissionFactory extends Factory
{
    /** @var class-string<FolderPermission> */
    protected $model = FolderPermission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_type' => PermissionSubjectType::Everyone,
            'subject_value' => null,
            'capability' => Capability::View,
            'cascade' => true,
        ];
    }

    public function forUser(int|string $userId, Capability $capability = Capability::View, bool $cascade = true): static
    {
        return $this->state(fn () => [
            'subject_type' => PermissionSubjectType::User,
            'subject_value' => (string) $userId,
            'capability' => $capability,
            'cascade' => $cascade,
        ]);
    }

    public function forRole(string $role, Capability $capability = Capability::View, bool $cascade = true): static
    {
        return $this->state(fn () => [
            'subject_type' => PermissionSubjectType::Role,
            'subject_value' => $role,
            'capability' => $capability,
            'cascade' => $cascade,
        ]);
    }

    public function forEveryone(Capability $capability = Capability::View, bool $cascade = true): static
    {
        return $this->state(fn () => [
            'subject_type' => PermissionSubjectType::Everyone,
            'subject_value' => null,
            'capability' => $capability,
            'cascade' => $cascade,
        ]);
    }
}
