<?php

namespace Reversify\Tests\Commands;

use Reversify\Tests\TestCase;

class ReversifyGenerateTest extends TestCase
{
    /** @test */
    public function it_runs_all_generators()
    {
        // Run the full generate command
        $this->artisan('reversify:generate')
            ->expectsOutput('Migrations, Models, and Controllers generated successfully.')
            ->assertExitCode(0);
    }
}
