<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Access;

use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\ResourceLibrary\Contracts\LibrarySubjectResolver;
use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\FolderPermission;
use Kurt\Modules\ResourceLibrary\Values\Subject;

final class PermissionResolver
{
    public function __construct(private readonly LibrarySubjectResolver $subjectResolver) {}

    public function highestCapability(?Authenticatable $user, Folder $folder): ?Capability
    {
        $subjects = $this->subjectResolver->subjects($user);

        // Walk ancestry from self upward. On self the row need not cascade;
        // on ancestors only cascading rows apply.
        foreach ($this->ancestry($folder) as $ancestor) {
            $best = $this->matchOn($ancestor, $subjects, allowCascadeOnly: $ancestor->id !== $folder->id);
            if ($best !== null) {
                return $best;
            }
        }

        // Fallback to visibility on the target folder itself.
        return match ($folder->visibility) {
            FolderVisibility::Public => Capability::Download,
            FolderVisibility::Restricted => null,
            FolderVisibility::Private => $user !== null && $folder->owner_id === $user->getAuthIdentifier()
                ? Capability::Manage
                : null,
        };
    }

    /**
     * @return iterable<Folder>
     */
    private function ancestry(Folder $folder): iterable
    {
        $current = $folder;
        while ($current !== null) {
            yield $current;
            $current = $current->parent;
        }
    }

    /**
     * @param  array<int, Subject>  $subjects
     */
    private function matchOn(Folder $folder, array $subjects, bool $allowCascadeOnly): ?Capability
    {
        /** @var array<int, FolderPermission> $rows */
        $rows = $folder->permissions()
            ->when($allowCascadeOnly, fn ($q) => $q->where('cascade', true))
            ->get()
            ->all();

        $best = null;
        foreach ($rows as $row) {
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
