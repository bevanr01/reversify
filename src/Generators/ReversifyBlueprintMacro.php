<?php

namespace Bevanr01\Reversify\Generators;

use Illuminate\Support\Facades\File;

class ReversifyBlueprintMacro
{
    protected $config;

    public function __construct()
    {
        $this->config = config('reversify');
    }

    public function generate()
    {
        $filePath = app_path('Providers/ReversifyBlueprintMacroServiceProvider.php');

        $fields = $this->config['global']['common_fields'];
        $macroContent = '';

        foreach ($fields as $field) {
            if ($field['type'] === 'timestamps' || $field['type'] === 'softDeletes') {

                $macroContent .= "            \$this->{$field['type']}();\n";

            } elseif ($field['type'] === 'enum') {
                
                if (!isset($field['enum_values']) || !is_array($field['enum_values'])) {
                    $this->error("Invalid or missing 'enum_values' for field '{$field['name']}'.");
                    continue;
                }

                $enumValues = "'" . implode("', '", $field['enum_values']) . "'";
                $macroContent .= "            \$this->enum('{$field['name']}', [$enumValues])";

                if (!empty($field['nullable'])) {
                    $macroContent .= "->nullable()";
                }

                $macroContent .= ";\n";
            } else {
                $macroContent .= "            \$this->{$field['type']}('{$field['name']}')";
                if (!empty($field['unsigned'])) {
                    $macroContent .= "->unsigned()";
                }

                if (!empty($field['nullable'])) {
                    $macroContent .= "->nullable()";
                }

                $macroContent .= ";\n";
            }
        }

        $content = <<<PHP
                <?php

                namespace App\Providers;

                use Illuminate\Support\ServiceProvider;
                use Illuminate\Database\Schema\Blueprint;

                class ReversifyBlueprintMacroServiceProvider extends ServiceProvider
                {
                    /**
                     * Register any application services.
                     */
                    public function register(): void
                    {
                        // Register any services if needed
                    }

                    /**
                     * Bootstrap any application services.
                     */
                    public function boot(): void
                    {
                        Blueprint::macro('commonFields', function () {
                            $macroContent
                        });
                    }
                }
                PHP;

        File::ensureDirectoryExists(app_path('Providers'));
        File::put($filePath, $content);

        echo "Blueprint macro service provider created successfully.";

        $this->registerServiceProvider();
    }

    protected function registerServiceProvider()
    {
        $serviceProviderEntry = "App\\Providers\\ReversifyBlueprintMacroServiceProvider::class,";

        // Check if Laravel 11's bootstrap/providers.php exists
        $bootstrapProvidersPath = base_path('bootstrap/providers.php');
        if (File::exists($bootstrapProvidersPath)) {
            $providersContent = File::get($bootstrapProvidersPath);

            if (str_contains($providersContent, $serviceProviderEntry)) {
                echo "Service provider already registered in bootstrap/providers.php.";
                return;
            }

            $updatedContent = preg_replace(
                '/return\s*\[/',
                "return [\n    $serviceProviderEntry",
                $providersContent
            );

            File::put($bootstrapProvidersPath, $updatedContent);
            echo "Service provider registered successfully in bootstrap/providers.php.";
            return;
        }

        // Fallback to config/app.php for older Laravel versions
        $appConfigPath = config_path('app.php');
        if (File::exists($appConfigPath)) {
            $configContent = File::get($appConfigPath);

            if (str_contains($configContent, $serviceProviderEntry)) {
                echo "Service provider already registered in config/app.php.";
                return;
            }

            $updatedContent = preg_replace(
                '/(\'providers\'\s*=>\s*\[)/',
                "$1\n        $serviceProviderEntry",
                $configContent
            );

            File::put($appConfigPath, $updatedContent);
            echo "Service provider registered successfully in config/app.php.";
        } else {
            echo "Could not find a suitable file to register the service provider.";
        }
    }
}