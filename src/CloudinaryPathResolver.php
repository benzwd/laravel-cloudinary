<?php

declare(strict_types=1);

namespace LaravelCloudinary\LaravelCloudinary;

final class CloudinaryPathResolver
{
    /**
     * @return array{assetFolder: string, publicId: string}
     */
    public static function resolve(string $path): array
    {
        $path = ltrim($path, '/');
        $withoutExtension = preg_replace('/\.[^.\/]+$/', '', $path);

        $segments = explode('/', $withoutExtension);
        array_pop($segments);
        $assetFolder = implode('/', $segments);

        return [
            'assetFolder' => $assetFolder,
            'publicId' => $withoutExtension,
        ];
    }
}
