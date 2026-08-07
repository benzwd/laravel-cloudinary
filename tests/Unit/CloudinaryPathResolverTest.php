<?php

declare(strict_types=1);

use LaravelCloudinary\LaravelCloudinary\CloudinaryPathResolver;

it('splits a nested path into asset folder and public id', function () {
    $resolved = CloudinaryPathResolver::resolve('articles/renting-in-kitahama-a71be9-cover.webp');

    expect($resolved['assetFolder'])->toBe('articles')
        ->and($resolved['publicId'])->toBe('articles/renting-in-kitahama-a71be9-cover');
});

it('strips the extension regardless of file type', function (string $path, string $expectedPublicId) {
    $resolved = CloudinaryPathResolver::resolve($path);

    expect($resolved['publicId'])->toBe($expectedPublicId);
})->with([
    ['articles/cat.jpg', 'articles/cat'],
    ['articles/cat.jpeg', 'articles/cat'],
    ['articles/cat.PNG', 'articles/cat'],
    ['articles/cat', 'articles/cat'],
]);

it('handles deeply nested paths', function () {
    $resolved = CloudinaryPathResolver::resolve('users/42/avatar/image.webp');

    expect($resolved['assetFolder'])->toBe('users/42/avatar')
        ->and($resolved['publicId'])->toBe('users/42/avatar/image');
});

it('handles root-level files with no folder', function () {
    $resolved = CloudinaryPathResolver::resolve('logo.png');

    expect($resolved['assetFolder'])->toBe('')
        ->and($resolved['publicId'])->toBe('logo');
});

it('strips a leading slash before resolving', function () {
    $resolved = CloudinaryPathResolver::resolve('/articles/cat.jpg');

    expect($resolved['assetFolder'])->toBe('articles')
        ->and($resolved['publicId'])->toBe('articles/cat');
});

it('does not treat a dot in a folder name as an extension', function () {
    $resolved = CloudinaryPathResolver::resolve('v1.2/articles/cat.jpg');

    expect($resolved['assetFolder'])->toBe('v1.2/articles')
        ->and($resolved['publicId'])->toBe('v1.2/articles/cat');
});
