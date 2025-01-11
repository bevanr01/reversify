<?php

namespace Bevanr01\Reversify\Tests\Commands;

use Bevanr01\Reversify\Tests\TestCase;

class ReversifyControllersTest extends TestCase
{
    /** @test */
    public function it_creates_controller_files()
    {
        // Run the artisan command
        $this->artisan('reversify:controllers')
            ->expectsOutput('Controllers generated successfully.')
            ->assertExitCode(0);

        // Check if controller files were created
        $controllerPath = app_path('Http/Controllers');
        $this->assertDirectoryExists($controllerPath);
    }
}
