<?php

declare(strict_types=1);

use Kurt\Modules\ResourceLibrary\Enums\ItemKind;

it('has expected cases and values', function () {
    expect(ItemKind::VideoLink->value)->toBe('video_link');
    expect(ItemKind::File->value)->toBe('file');
    expect(ItemKind::Document->value)->toBe('document');
    expect(ItemKind::ExternalUrl->value)->toBe('external_url');
});
