<?php

namespace Reversify\Commands;

use Illuminate\Console\Command;
use Reversify\Generator;
use Illuminate\Support\Facades\Log;

class ReversifyControllersCommand extends Command
{
    protected $signature = 'reversify:controllers';
    protected $description = 'Generate Laravel controllers from an existing database';

    public function handle()
    {
        $config = config('reversify.controllers');
        $generator = new Generator($config);

        try {
            $generator->controllers();
            $this->info('Controllers generated successfully.');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
