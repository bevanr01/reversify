<?php

namespace Bevanr01\Reversify\Tests\Commands;

use Bevanr01\Reversify\Tests\TestCase;

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
