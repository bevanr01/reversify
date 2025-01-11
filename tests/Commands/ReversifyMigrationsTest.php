<?php

namespace Bevanr01\Reversify\Tests\Commands;

use Bevanr01\Reversify\Tests\TestCase;

class ReversifyMigrationsTest extends TestCase
{
    /** @test */
    public function it_creates_migration_files()
    {
        // Run the artisan command
        $this->artisan('reversify:migrations')
            ->expectsOutput('Migrations generated successfully.')
            ->assertExitCode(0);

        // Check if migration files were created
        $migrationPath = base_path('database/migrations');
        $this->assertDirectoryExists($migrationPath);
    }
}
