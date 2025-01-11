<?php

namespace Abevanation\Reversify\Commands;

use Illuminate\Console\Command;
use Abevanation\Reversify\Generator;

class ReversifyMigrationsCommand extends Command
{
    protected $signature = 'reversify:migrations';
    protected $description = 'Generate Laravel migrations from an existing database';

    public function handle()
    {
        $this->info('Generating migrations...');

        $generator = new Generator();

        try {
            $generator->migrations();
            $generator->createBlueprintMacro();
            $this->info('Migrations generated successfully.');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
