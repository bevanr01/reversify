<?php

namespace Abevanation\Reversify;

class Generator
{
    protected $config;

    public function __construct()
    {
        $this->config = config('reversify');
    }

    public function generate()
    {
        $this->migrations();
        $this->models();
        $this->controllers();

        if ($this->config['reversify']['use_common_fields']) {
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

        if ($this->config['reversify']['use_common_fields']) {
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