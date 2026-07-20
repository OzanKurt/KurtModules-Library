<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Access;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Kurt\Modules\ResourceLibrary\Contracts\LibrarySubjectResolver;
use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Values\Subject;

final class PermissionResolver
{
    public function __construct(private readonly LibrarySubjectResolver $subjectResolver) {}

    public function highestCapability(?Authenticatable $user, Folder $folder): ?Capability
    {
        $subjects = $this->subjectResolver->subjects($user);

        // Walk ancestry from self upward. On self the row need not cascade;
        // on ancestors only cascading rows apply.
        //
        // ACL DESIGN (pinned by tests, intentional): the walk STOPS at the
        // nearest folder that yields any match. A permission on a closer
        // ancestor therefore shadows one on a farther ancestor even when the
        // farther grant is higher — resolution is "nearest wins", not "highest
        // across the whole chain". Only rows on the SAME folder are compared by
        // rank (see matchOn).
        foreach ($this->ancestry($folder) as $ancestor) {
            $best = $this->matchOn($ancestor, $subjects, allowCascadeOnly: $ancestor->id !== $folder->id);
            if ($best !== null) {
                return $best;
            }
        }

        // Fallback to visibility on the target folder itself.
        //
        // ACL DESIGN (pinned by tests, intentional): the visibility fallback is
        // reached only when NO permission row matched anywhere in the chain.
        // A Restricted folder still inherits cascading ancestor grants (they are
        // handled by the loop above); "Restricted" caps the *fallback*, it does
        // not sever inheritance.
        return match ($folder->visibility) {
            FolderVisibility::Public => Capability::Download,
            FolderVisibility::Restricted => null,
            FolderVisibility::Private => $user !== null && $folder->owner_id === $user->getAuthIdentifier()
                ? Capability::Manage
                : null,
        };
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
