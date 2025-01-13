<?php

namespace Reversify\Tests\Commands;

use Reversify\Tests\TestCase;

class ReversifyModelsTest extends TestCase
{
    /** @test */
    public function it_creates_model_files()
    {
        // Run the artisan command
        $this->artisan('reversify:models')
            ->expectsOutput('Models generated successfully.')
            ->assertExitCode(0);

        // Check if model files were created
        $modelPath = app_path('Models');
        $this->assertDirectoryExists($modelPath);
    }
}
