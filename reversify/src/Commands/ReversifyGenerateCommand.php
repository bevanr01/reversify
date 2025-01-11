<?php

namespace Abevanation\Reversify\Commands;

use Illuminate\Console\Command;
use Abevanation\Reversify\Generator;

class ReversifyGenerateCommand extends Command
{
    protected $signature = 'reversify:generate';
    protected $description = 'Generate Laravel migrations, models, and controllers from an existing database';

    public function handle()
    {
        $this->info('Generating models...');

        $config = config('reversify');
        $generator = new Generator($config);

        try {
            $generator->generate();
            $this->info('Migrations, Models, and Controllers generated successfully.');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
