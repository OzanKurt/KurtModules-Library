<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Access;

use Kurt\Modules\ResourceLibrary\Enums\PermissionSubjectType;

/**
 * Tells the admin UI whether `role` permission subjects actually resolve.
 *
 * Out of the box the shipped {@see DefaultSubjectResolver} emits only `everyone`
 * + `user` subjects, so a `role` grant is inert (it can never match). Role
 * grants become live in either of two ways:
 *
 *  1. Configure a role source callable at `resource-library.roles.resolver`,
 *     which the default resolver reads to emit `role` subjects; or
 *  2. Bind a custom LibrarySubjectResolver via
 *     `config('resource-library.subject_resolver')` that emits `role` subjects.
 *
 * The Filament ACL relation managers use this to hide the `role` option (and
 * flag it) when neither is set, so admins are not offered a grant that silently
 * does nothing.
 */
final class RoleSubjectSupport
{
    /**
     * Role subjects are considered supported when a role source is configured
     * for the default resolver, or when a custom resolver replaces the default
     * (everyone + user only) one.
     */
    public static function enabled(): bool
    {
        if (is_callable(config('resource-library.roles.resolver'))) {
            return true;
        }

        return config('resource-library.subject_resolver') !== DefaultSubjectResolver::class;
    }

    /**
     * Subject-type options for the ACL form, omitting `role` when unsupported.
     *
     * @return array<string, string>
     */
    public static function subjectTypeOptions(): array
    {
        $options = [];

        foreach (PermissionSubjectType::cases() as $case) {
            if ($case === PermissionSubjectType::Role && ! self::enabled()) {
                continue;
            }

            $options[$case->value] = ucfirst($case->value);
        }

        return $options;
    }
}
