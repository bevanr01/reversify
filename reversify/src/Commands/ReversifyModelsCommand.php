<?php

namespace Abevanation\Reversify\Commands;

use Illuminate\Console\Command;
use Abevanation\Reversify\Generator;

class ReversifyModelsCommand extends Command
{
    protected $signature = 'reversify:models';
    protected $description = 'Generate Laravel models from an existing database';

    public function handle()
    {
        $this->info('Generating models...');

        $config = config('reversify.models');
        $generator = new Generator($config);

        try {
            $generator->models();
            $this->info('Models generated successfully.');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
