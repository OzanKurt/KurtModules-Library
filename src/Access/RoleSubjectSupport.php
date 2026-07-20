<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Access;

use Kurt\Modules\ResourceLibrary\Enums\PermissionSubjectType;

/**
 * Tells the admin UI whether `role` permission subjects actually resolve.
 *
 * The shipped {@see DefaultSubjectResolver} only emits `everyone` + `user`
 * subjects, so a `role` grant created against it is inert (it can never match).
 * Role grants only work when the host app binds a custom LibrarySubjectResolver
 * that emits `role` subjects via `config('resource-library.subject_resolver')`.
 *
 * The Filament ACL relation manager uses this to hide the `role` option (and
 * flag it) when the default resolver is in use, so admins are not offered a
 * grant that silently does nothing.
 */
final class RoleSubjectSupport
{
    /**
     * Role subjects are considered supported when the configured resolver is
     * anything other than the default (everyone + user only) one.
     */
    public static function enabled(): bool
    {
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
