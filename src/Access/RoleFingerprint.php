<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Access;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Deterministic fingerprint of a subject's current roles, used as a dimension of
 * the ACL cache key. Roles come from the SAME authoritative source the
 * DefaultSubjectResolver reads (`resource-library.roles.resolver`), so a role
 * change produces a different key and self-invalidates the cached capability
 * WITHOUT any domain event — the one staleness source that has no bump signal
 * (see the ACL generational-cache design, security invariant "input integrity").
 *
 * The hash is order-independent (roles are sorted) so the same role set always
 * maps to the same key. An anonymous subject, or one with no roles / no role
 * source configured, maps to the stable token "none".
 */
final class RoleFingerprint
{
    public static function for(?Authenticatable $user): string
    {
        if ($user === null) {
            return 'none';
        }

        $roles = self::roleIds($user);

        if ($roles === []) {
            return 'none';
        }

        sort($roles);

        return substr(sha1(implode(',', $roles)), 0, 12);
    }

    /**
     * Role identifiers for the user per the configured role source
     * (`resource-library.roles.resolver`). Mirrors DefaultSubjectResolver so the
     * fingerprint and the resolver never disagree on which roles a subject has.
     *
     * @return list<string>
     */
    private static function roleIds(Authenticatable $user): array
    {
        $resolver = config('resource-library.roles.resolver');

        if (! is_callable($resolver)) {
            return [];
        }

        /** @var mixed $result */
        $result = $resolver($user);

        if ($result instanceof Arrayable) {
            $result = $result->toArray();
        }

        if (! is_iterable($result)) {
            return [];
        }

        $ids = [];

        foreach ($result as $roleId) {
            if (is_scalar($roleId)) {
                $ids[] = (string) $roleId;
            }
        }

        return $ids;
    }
}
