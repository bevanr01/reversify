<?php

namespace Reversify\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Orchestra\Testbench\Attributes\WithMigration; 
use function Orchestra\Testbench\workbench_path; 

#[WithMigration('laravel', 'cache', 'queue')] 
#[WithMigration('session')]
abstract class TestCase extends BaseTestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        restore_error_handler();
        restore_exception_handler();
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    protected function defineDatabaseMigrations() 
    {
        $this->loadMigrationsFrom(
            workbench_path('database/migrations')
        );
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Reversify\ReversifyServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $app['config']->set('reversify.global.ignore_tables', ['migrations', 'sessions', 'cache', 'password_resets', 'failed_jobs']);
    }

    /**
     * Load package-specific migrations.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getPackageMigrations($app)
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
