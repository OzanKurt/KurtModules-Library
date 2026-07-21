<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Access;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Kurt\Modules\ResourceLibrary\Contracts\ResourceLibrarySubjectResolver;
use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Values\Subject;

final class PermissionResolver
{
    public function __construct(private readonly ResourceLibrarySubjectResolver $subjectResolver) {}

    public function highestCapability(?Authenticatable $user, Folder $folder): ?Capability
    {
        $subjects = $this->subjectResolver->subjects($user);

        // ADDITIVE resolution (intentional design): the effective capability is
        // the MAXIMUM capability rank across the WHOLE ancestor chain, not the
        // nearest match. We walk every folder from self upward and keep the
        // single highest grant we can find. On self a row need not cascade; on
        // ancestors only cascading rows apply.
        //
        // This deliberately removes the old "nearest wins" footgun where a
        // closer, lower grant (e.g. `Everyone: View` on the parent) shadowed a
        // farther, higher grant (e.g. `user: Manage` on the grandparent) and so
        // silently downgraded a user. Now the farther Manage still wins.
        $best = null;

        foreach ($this->ancestry($folder) as $ancestor) {
            $match = $this->matchOn($ancestor, $subjects, allowCascadeOnly: $ancestor->id !== $folder->id);

            if ($match !== null && ($best === null || $match->rank() > $best->rank())) {
                $best = $match;
            }
        }

        // The visibility fallback of the target folder contributes its own
        // baseline capability to the SAME maximum rather than only applying when
        // nothing matched. A Restricted folder contributes nothing here yet
        // still inherits cascading ancestor grants (handled by the loop above):
        // "Restricted" caps only this fallback, it does not sever inheritance.
        $fallback = match ($folder->visibility) {
            FolderVisibility::Public => Capability::Download,
            FolderVisibility::Restricted => null,
            FolderVisibility::Private => $user !== null && $folder->owner_id === $user->getAuthIdentifier()
                ? Capability::Manage
                : null,
        };

        if ($fallback !== null && ($best === null || $fallback->rank() > $best->rank())) {
            $best = $fallback;
        }

        return $best;
    }

    /**
     * The folder itself plus every ancestor, ordered nearest-first (self, then
     * parent, up to the root).
     *
     * The whole chain and each node's permission rows are loaded in a single
     * query pair (a path-prefix `whereIn` + eager-loaded `permissions`), so a
     * check on a deeply nested folder stays bounded instead of issuing one lazy
     * `->parent` load and one `permissions()` query per level (the old O(depth)
     * N+1). Path-derived ancestry matches the model's own `ancestors()` helper.
     *
     * @return iterable<Folder>
     */
    private function ancestry(Folder $folder): iterable
    {
        /** @var Collection<int, Folder> $chain */
        $chain = $folder->newQuery()
            ->whereIn('path', $this->ancestorPaths($folder->path))
            ->with('permissions')
            ->get()
            ->sortByDesc('depth')
            ->values();

        return $chain;
    }

    /**
     * Every anchored path prefix of the given folder path, including the folder
     * itself: "/a/b/c" -> ["/a", "/a/b", "/a/b/c"].
     *
     * @return list<string>
     */
    private function ancestorPaths(string $path): array
    {
        $segments = array_values(array_filter(explode('/', $path), static fn (string $s): bool => $s !== ''));

        $paths = [];
        $accumulated = '';
        foreach ($segments as $segment) {
            $accumulated .= '/'.$segment;
            $paths[] = $accumulated;
        }

        return $paths;
    }

    /**
     * @param  array<int, Subject>  $subjects
     */
    private function matchOn(Folder $folder, array $subjects, bool $allowCascadeOnly): ?Capability
    {
        $best = null;

        // Permissions are eager-loaded by ancestry(); iterate the loaded
        // collection and filter cascade in PHP rather than issuing a query.
        foreach ($folder->permissions as $row) {
            if ($allowCascadeOnly && ! $row->cascade) {
                continue;
            }

            foreach ($subjects as $subject) {
                if ($subject->matches($row->subject_type->value, $row->subject_value)) {
                    if ($best === null || $row->capability->rank() > $best->rank()) {
                        $best = $row->capability;
                    }
                }
            }
        }

        return $best;
    }
}
