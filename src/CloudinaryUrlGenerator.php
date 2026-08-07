<?php

declare(strict_types=1);

namespace LaravelCloudinary\LaravelCloudinary\MediaLibrary;

use Cloudinary\Cloudinary;
use LaravelCloudinary\LaravelCloudinary\CloudinaryPathResolver;
use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

class CloudinaryUrlGenerator extends DefaultUrlGenerator
{
    public function getUrl(): string
    {
        $publicId = CloudinaryPathResolver::resolve($this->getPath())['publicId'];

        return (string) app(Cloudinary::class)->image($publicId)->toUrl();
    }
}
