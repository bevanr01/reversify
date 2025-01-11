<?php

namespace Bevanr01\Reversify;

class Generator
{
    protected $config;
    protected $globalRun = false;

    public function __construct()
    {
        $this->config = config('reversify');
    }

    public function generate()
    {
        $this->globalRun = true;
        $this->controllers();
        $this->models();
        $this->migrations();
        
        if ($this->config['global']['use_common_fields']) {
            $this->createBlueprintMacro();
        }
    }

    public function controllers()
    {
        $controllers = new Generators\ReversifyControllers();
        $controllers->generate();
    }

    public function migrations()
    {
        
        $migrations = new Generators\ReversifyMigrations();
        $migrations->generate();

        if (!$this->globalRun && $this->config['global']['use_common_fields']) {
            $this->createBlueprintMacro();
        }
    }

    public function models()
    {
        $models = new Generators\ReversifyModels();
        $models->generate();
    }

    public function createBlueprintMacro()
    {
        $blueprintMacro = new Generators\ReversifyBlueprintMacro();
        $blueprintMacro->generate();
    }
}