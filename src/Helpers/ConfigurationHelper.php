<?php

namespace Reversify\Helpers;

class ConfigurationHelper
{
    protected $config;
    protected $globalConfig;
    protected $migrationsConfig;
    protected $modelsConfig;
    protected $controllersConfig;

    public function __construct()
    {
        $this->config = config('reversify');
        $this->globalConfig = $this->config['global'];
        $this->migrationsConfig = $this->config['migrations'];
        $this->modelsConfig = $this->config['models'];
        $this->controllersConfig = $this->config['controllers'];
    }

    public static function getConfiguration(): array
    {
        return $config;
    }

    public static function getGlobalConfiguration(): array
    {
        return $globalConfig;
    }

    public static function getMigrationsConfiguration(): array
    {
        return $migrationsConfig;
    }

    public static function getModelsConfiguration(): array
    {
        return $modelsConfig;
    }

    public static function getControllersConfiguration(): array
    {
        return $controllersConfig;
    }
}