<?php

declare(strict_types=1);

namespace LaravelCloudinary\LaravelCloudinary\Tests;

use LaravelCloudinary\LaravelCloudinary\LaravelCloudinaryServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelCloudinaryServiceProvider::class,
        ];
    }
}
