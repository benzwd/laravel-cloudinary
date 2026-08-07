<?php

declare(strict_types=1);

namespace LaravelCloudinary\LaravelCloudinary;

use Cloudinary\Cloudinary;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

class LaravelCloudinaryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Storage::extend('cloudinary', function (Application $app, array $config): LaravelFilesystemAdapter {
            $cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => $config['cloud_name'],
                    'api_key' => $config['api_key'],
                    'api_secret' => $config['api_secret'],
                ],
                'url' => [
                    'secure' => $config['secure'] ?? true,
                ],
            ]);

            $adapter = new CloudinaryAdapter($cloudinary);
            $filesystem = new Filesystem($adapter);

            return new LaravelFilesystemAdapter(
                $filesystem,
                $adapter,
                $config,
            );
        });
    }
}
