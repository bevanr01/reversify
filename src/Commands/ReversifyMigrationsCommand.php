<?php

namespace Reversify\Commands;

use Illuminate\Console\Command;
use Reversify\Generator;
use Illuminate\Support\Facades\Log;

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
            $this->info('Migrations generated successfully.');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
