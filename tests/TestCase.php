<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Tests\Concerns\BuildsApiData;

abstract class TestCase extends BaseTestCase
{
    use BuildsApiData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
