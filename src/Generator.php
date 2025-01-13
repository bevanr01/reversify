<?php

namespace Reversify;

class Generator
{
    protected $configuration;
    protected $globalConfig;
    protected $globalRun = false;
    protected $database;
    protected $file;
    protected $content;

    public function __construct()
    {
        $this->configuration = new Helpers\ConfigurationHelper();
        $this->globalConfig = $this->configuration->getGlobalConfiguration();
        $this->database = new Helpers\DatabaseHelper();
        $this->file = new Helpers\FileHelper();
        $this->content = new Helpers\ContentHelper();
    }

    public function generate()
    {
        $this->globalRun = true;
        $this->controllers($this->configuration, $this->database, $this->file, $this->content);
        $this->models($this->configuration, $this->database, $this->file, $this->content);
        $this->migrations($this->configuration, $this->database, $this->file, $this->content);
        
        if ($this->globalConfig['use_common_fields']) {
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

        if (!$this->globalRun && $this->globalConfig['use_common_fields']) {
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