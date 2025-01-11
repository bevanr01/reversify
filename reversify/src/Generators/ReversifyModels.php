<?php

namespace Abevanation\Reversify\Generators;

use Illuminate\Support\Facades\DB;

class ReversifyModels
{
    protected $config;
    protected $outputPath;
    protected $ignoreTables;
    protected $traits;
    protected $useCommonFields = false;
    protected $useTimestamps = false;
    protected $useSoftDeletes = false;

    public function __construct()
    {
        $this->config = config('reversify');
        $this->outputPath = $this->config['models']['output_directory'];
        $this->traits = $this->config['models']['traits'];
        $this->ignoreTables = $this->config['reversify']['ignore_tables'];
        $this->useCommonFields = $this->config['reversify']['use_common_fields'];
        $this->useTimestamps = $this->config['reversify']['use_timestamps'];
        $this->useSoftDeletes = $this->config['reversify']['use_soft_deletes'];
    }
    
    public function generate()
    {
        if (!is_dir($this->config['models']['output_directory'])) {
            mkdir($this->config['models']['output_directory'], 0777, true);
        }

        $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();
        if (!$tables) {
            die("Error fetching tables: " . DB::getPdo()->errorInfo());
        }

        foreach ($tables as $table) {
            // Skip ignored tables
            if (in_array($table, $this->ignoreTables)) {
                echo "Skipping model generation for table: $table\n";
                continue;
            }

            // Generate the model
            $this->generateModel($table);
        }
    }

    protected function generateModel(string $table)
    {
        // Determine model class name
        $className = $this->getModelClassName($table);

        // Define the model file path
        $filePath = "{$this->outputPath}/{$className}.php";

        if (File::exists($filePath)) {
            echo "Model already exists for table: $table ($className)\n";
            return;
        }

        // Fetch the columns of the table
        $columns = $this->getTableColumns($table);

        // Generate the model content
        $content = $this->getModelContent($className, $table, $columns);

        // Write the model to a file
        File::put($filePath, $content);

        echo "Model created: $className (table: $table)\n";
    }

    protected function getModelClassName(string $table): string
    {
        return ucfirst(str_singular(str_replace('_', '', $table)));
    }

    protected function getTableColumns(string $table): array
    {
        return DB::getSchemaBuilder()->getColumnListing($table);
    }

    protected function getModelContent(string $className, string $table, array $columns): string
    {

        $traits = $this->getTraits($table);
        $fillable = $this->filterColumns($this->getFillable($table), $columns);
        $guarded = $this->filterColumns($this->getGuarded($table), $columns);
        $casts = $this->filterColumns($this->getCasts($table), $columns, true);
        $with = $this->filterColumns($this->getWith($table), $columns);
        $hidden = $this->filterColumns($this->getHidden($table), $columns);
        $relationships = $this->getRelationships($table);
        $lifecycleHooks = $this->getLifecycleHooks($table);

        $useDeclarationsCode = $this->buildUseDeclarations($traits, $relationships);
        $traitsUse = !empty($traits) ? "use " . implode(", ", $traits) . ";" : "";
        $tableCode = "protected \$table = '{$table}';";
        $perPageCode = $this->config['models']['per_page'] ? "protected \$perPage = " . $this->config['models']['per_page'] . ";" : "";
        $fillableCode = $fillable ? "protected \$fillable = " . var_export($fillable, true) . ";" : "";
        $guardedCode = $guarded ? "protected \$guarded = " . var_export($guarded, true) . ";" : "";
        $castsCode = $casts ? "protected \$casts = " . var_export($casts, true) . ";" : "";
        $withCode = $with ? "protected \$with = " . var_export($with, true) . ";" : "";
        $hiddenCode = $hidden ? "protected \$hidden = " . var_export($hidden, true) . ";" : "";
        $relationshipsCode = implode("\n", $relationships);
        $lifecycleHooksCode = $this->buildLifecycleHooks($lifecycleHooks);

        return <<<PHP
<?php

namespace App\Models;

{$useDeclarationsCode}

class {$className} extends Model
{
    {$traitsUse}

    {$tableCode}

    {$perPageCode}

    {$fillableCode}

    {$guardedCode}

    {$castsCode}

    {$withCode}

    {$hiddenCode}

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected \$table = '{$table}';

    {$relationshipsCode}

    {$lifecycleHooksCode}
}
PHP;
    }

    protected function filterColumns(array $configValues, array $columns, bool $isCasts = false): array
    {
        if (!$configValues) {
            return [];
        }

        if ($isCasts) {
            // For casts, ensure both the key (column) exists in the table
            return array_filter($configValues, fn($type, $column) => in_array($column, $columns), ARRAY_FILTER_USE_BOTH);
        }

        // Filter config values by ensuring the column exists in the table
        return array_filter($configValues, fn($field) => in_array($field, $columns));
    }

    protected function buildUseDeclarations(array $traits, array $relationships): string
    {
        $declarations = ['Illuminate\Database\Eloquent\Model'];

        if ($this->useSoftDeletes) {
            $declarations[] = 'Illuminate\Database\Eloquent\SoftDeletes';
        }

        foreach ($traits as $trait) {
            $declarations[] = $trait;
        }

        foreach ($relationships as $relation) {
            [$relatedModel] = $relation;
            $declarations[] = $relatedModel;
        }

        return 'use ' . implode(";\nuse ", array_unique($declarations)) . ';';
    }

    protected function getTraits(string $table): array
    {
        $traits = $this->config['traits'] ?? [];
        if (isset($this->config['traits'][$table])) {
            $traits = $this->config['traits'][$table];
        }
        return is_array($traits) ? $traits : [$traits];
    }

    protected function getFillable(string $table): ?array
    {
        return $this->config['fillable'][$table] ?? $this->config['fillable'] ?? [];
    }

    protected function getGuarded(string $table): ?array
    {
        return $this->config['guarded'][$table] ?? $this->config['guarded'] ?? [];
    }

    protected function getCasts(string $table): ?array
    {
        return $this->config['casts'][$table] ?? $this->config['casts'] ?? [];
    }

    protected function getWith(string $table): ?array
    {
        return $this->config['with'][$table] ?? $this->config['with'] ?? [];
    }

    protected function getHidden(string $table): ?array
    {
        return $this->config['hidden'][$table] ?? $this->config['hidden'] ?? [];
    }

    protected function getRelationships(string $table): array
    {
        $relationships = $this->config['relationships'][$table] ?? $this->config['relationships'] ?? [];
        $methods = [];

        foreach ($relationships as $type => $relation) {
            [$relatedModel, $relatedTable, $foreignKey, $ownerKey] = $relation;

            $methods[] = <<<PHP
    public function {$relatedTable}()
    {
        return \$this->{$type}({$relatedModel}::class, '{$foreignKey}', '{$ownerKey}');
    }
PHP;
        }

        return $methods;
    }

    protected function getLifecycleHooks(string $table): array
    {
        return $this->config['lifecycle_hooks'][$table] ?? $this->config['lifecycle_hooks'] ?? [];
    }

    protected function buildLifecycleHooks(array $hooks): string
    {
        if (empty($hooks)) {
            return '';
        }

        $hooksCode = [];
        foreach ($hooks as $hook => $callback) {
            $hooksCode[] = <<<PHP
        static::{$hook}({$callback});
PHP;
        }

        $hooksCodeString = implode("\n", $hooksCode);

        return <<<PHP
    protected static function booted()
    {
{$hooksCodeString}
    }
PHP;
    }
}