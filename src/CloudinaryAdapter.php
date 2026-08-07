<?php

declare(strict_types=1);

namespace LaravelCloudinary\LaravelCloudinary;

use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Cloudinary;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;

class CloudinaryAdapter implements FilesystemAdapter
{
    public function __construct(private readonly Cloudinary $cloudinary) {}

    public function fileExists(string $path): bool
    {
        try {
            $this->cloudinary->adminApi()->asset($this->toPublicId($path));

            return true;
        } catch (NotFound) {
            return false;
        } catch (ApiError $e) {
            throw UnableToCheckFileExistence::forLocation($path, $e);
        }
    }

    public function directoryExists(string $path): bool
    {
        $prefix = trim($path, '/').'/';

        try {
            $result = $this->cloudinary->adminApi()->assets([
                'prefix' => $prefix,
                'max_results' => 1,
                'type' => 'upload',
            ]);

            return count($result['resources'] ?? []) > 0;
        } catch (ApiError) {
            return false;
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    /**
     * @param  resource  $contents
     */
    // @phpstan-ignore typeCoverage.paramTypeCoverage
    public function writeStream(string $path, $contents, Config $config): void // @pest-ignore-type
    {
        $tmpFile = tmpfile();

        if ($tmpFile === false) {
            throw UnableToWriteFile::atLocation($path, 'Could not create a temporary file.');
        }

        stream_copy_to_stream($contents, $tmpFile);
        $meta = stream_get_meta_data($tmpFile);
        $uri = $meta['uri'] ?? null;

        if ($uri === null) {
            fclose($tmpFile);

            throw UnableToWriteFile::atLocation($path, 'Could not resolve the temporary file path.');
        }

        $this->upload($path, $uri, $config, isFilePath: true);

        fclose($tmpFile);
    }

    private function upload(string $path, string $contentsOrFilePath, Config $config, bool $isFilePath = false): void
    {
        $resolved = CloudinaryPathResolver::resolve($path);

        try {
            $this->cloudinary->uploadApi()->upload($contentsOrFilePath, [
                'public_id' => $resolved['publicId'],
                'asset_folder' => $resolved['assetFolder'],
                'display_name' => basename($resolved['publicId']),
                'overwrite' => $config->get('overwrite', true),
                'resource_type' => $config->get('resource_type', 'auto'),
                'unique_filename' => false,
                'use_filename' => false,
            ]);
        } catch (ApiError $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function read(string $path): string
    {
        $contents = @file_get_contents((string) $this->cloudinary->image($this->toPublicId($path))->toUrl());

        if ($contents === false) {
            throw UnableToReadFile::fromLocation($path);
        }

        return $contents;
    }

    /**
     * @return resource
     */
    // @phpstan-ignore typeCoverage.returnTypeCoverage
    public function readStream(string $path) // @pest-ignore-type
    {
        $stream = @fopen((string) $this->cloudinary->image($this->toPublicId($path))->toUrl(), 'rb');

        if ($stream === false) {
            throw UnableToReadFile::fromLocation($path);
        }

        return $stream;
    }

    public function delete(string $path): void
    {
        try {
            $this->cloudinary->uploadApi()->destroy($this->toPublicId($path), [
                'resource_type' => 'image',
                'invalidate' => true,
            ]);
        } catch (ApiError $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $prefix = trim($path, '/').'/';

        try {
            $this->cloudinary->adminApi()->deleteAssetsByPrefix($prefix);
        } catch (ApiError $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        // Cloudinary creates folders implicitly on first upload.
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'Cloudinary assets are always public.');
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, visibility: Visibility::PUBLIC);
    }

    public function mimeType(string $path): FileAttributes
    {
        $asset = $this->fetchAsset($path);

        return new FileAttributes($path, mimeType: $asset['resource_type'].'/'.$asset['format']);
    }

    public function lastModified(string $path): FileAttributes
    {
        $asset = $this->fetchAsset($path);
        $timestamp = strtotime((string) $asset['created_at']);

        return new FileAttributes($path, lastModified: $timestamp !== false ? $timestamp : null);
    }

    public function fileSize(string $path): FileAttributes
    {
        $asset = $this->fetchAsset($path);

        return new FileAttributes($path, fileSize: $asset['bytes']);
    }

    /**
     * @throws UnableToRetrieveMetadata
     */
    private function fetchAsset(string $path): ApiResponse
    {
        try {
            return $this->cloudinary->adminApi()->asset($this->toPublicId($path));
        } catch (ApiError $e) {
            throw UnableToRetrieveMetadata::create($path, 'metadata', $e->getMessage(), $e);
        }
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $prefix = trim($path, '/').'/';

        try {
            $result = $this->cloudinary->adminApi()->assets([
                'prefix' => $prefix,
                'type' => 'upload',
                'max_results' => 500,
            ]);
        } catch (ApiError) {
            return;
        }

        foreach ($result['resources'] ?? [] as $resource) {
            $timestamp = isset($resource['created_at']) ? strtotime((string) $resource['created_at']) : null;

            yield new FileAttributes(
                path: $resource['public_id'],
                fileSize: $resource['bytes'] ?? null,
                lastModified: $timestamp !== false ? $timestamp : null,
            );
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $resolved = CloudinaryPathResolver::resolve($destination);

        try {
            $this->cloudinary->uploadApi()->rename($this->toPublicId($source), $resolved['publicId'], [
                'asset_folder' => $resolved['assetFolder'],
                'overwrite' => true,
            ]);
        } catch (ApiError $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $contents = $this->read($source);
            $this->write($destination, $contents, $config);
        } catch (UnableToReadFile|UnableToWriteFile $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    private function toPublicId(string $path): string
    {
        return CloudinaryPathResolver::resolve($path)['publicId'];
    }

    public function getUrl(string $path): string
    {
        return (string) $this->cloudinary->image($this->toPublicId($path))->toUrl();
    }
}
