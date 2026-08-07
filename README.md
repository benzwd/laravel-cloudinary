# Laravel Cloudinary

A Flysystem v3 adapter for Cloudinary with native support for **dynamic folder mode** (June 2024+ accounts), designed to work seamlessly with Spatie Laravel Media Library.

## Why this package

Most existing Cloudinary Flysystem adapters predate Cloudinary's dynamic folder mode, and treat a slash in the path as automatically creating a browsable folder — which stopped being true for accounts created after June 2024. This adapter derives the `asset_folder` parameter automatically from the path on every upload, so folders always show up correctly in the Cloudinary dashboard without any extra configuration.

It also correctly implements `deleteDirectory()` via Cloudinary's Admin API (list-by-prefix + bulk delete), since Cloudinary has no real directories and most generic adapters silently fail on this operation.

Supports both `cloudinary/cloudinary_php` v2 and v3.

## Installation

```bash
composer require zwdev/laravel-cloudinary
```

Add the disk to `config/filesystems.php`:

```php
'disks' => [
    'cloudinary' => [
        'driver' => 'cloudinary',
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        'secure' => true,
    ],
],
```

## Usage with Spatie Media Library

In `config/media-library.php`:

```php
'disk_name' => 'cloudinary',
'url_generator' => LaravelCloudinary\LaravelCloudinary\MediaLibrary\CloudinaryUrlGenerator::class,
```

## Known limitations

- `setVisibility()` is not supported — Cloudinary assets are always public.
- `copy()` is implemented via read+write (downloads then re-uploads) since Cloudinary's native duplication requires the Admin API in a way not yet wrapped here.
- Extensions are always stripped from `public_id` — Cloudinary infers the format automatically. Do not pass filenames with extensions if you want predictable public IDs.

## Credits

- [zwdev](https://github.com/benzwd)

## License

Laravel Cloudinary is open-sourced software licensed under the [MIT license](LICENSE.md).
