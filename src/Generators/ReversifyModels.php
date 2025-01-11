<?php

namespace Bevanr01\Reversify\Generators;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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
        $this->ignoreTables = $this->config['global']['ignore_tables'];
        $this->useCommonFields = $this->config['global']['use_common_fields'];
        $this->useTimestamps = $this->config['global']['use_timestamps'];
        $this->useSoftDeletes = $this->config['global']['use_soft_deletes'];
    }
    
    public function generate()
    {
        if (!is_dir($this->config['models']['output_directory'])) {
            mkdir($this->config['models']['output_directory'], 0777, true);
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            $tables = collect(DB::select('SELECT name FROM sqlite_master WHERE type = "table"'))
                ->pluck('name')
                ->toArray();
        } else {
            $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();
        }

        if (!$tables) {
            die("No tables exist or could not fetch.");
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
        return ucfirst(Str::singular(str_replace('_', '', $table)));
    }

    protected function getTableColumns(string $table): array
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA table_info('$table');"))
                    ->pluck('name')
                    ->toArray();
        } else {
            return DB::getSchemaBuilder()->getColumnListing($table);
        }
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
        $fillableCode = $fillable ? "protected \$fillable = " . var_export($fillable, true) . ";" : "protected \$fillable = [];";
        $guardedCode = $guarded ? "protected \$guarded = " . var_export($guarded, true) . ";" : "protected \$guarded = [];";
        $castsCode = $casts ? "protected \$casts = " . var_export($casts, true) . ";" : "protected \$casts = [];";
        $withCode = $with ? "protected \$with = " . var_export($with, true) . ";" : "protected \$with = [];";
        $hiddenCode = $hidden ? "protected \$hidden = " . var_export($hidden, true) . ";" : "protected \$hidden = [];";
        $relationshipsCode = implode("\n", $relationships);
        $lifecycleHooksCode = $this->buildLifecycleHooks($lifecycleHooks);

        return <<<PHP
<?php

namespace App\Models;

{$useDeclarationsCode}

class {$className} extends Model
{
    {$traitsUse}

    {$perPageCode}

    /**
     * The table associated with the model.
     *
     * @var string
     */
    {$tableCode}

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    {$fillableCode}

    /**
     * The attributes that are guarded against mass assignment.
     *
     * @var array
     */
    {$guardedCode}

    /**
     * The attributes that should be cast to specific data types.
     *
     * @var array
     */
    {$castsCode}

    /**
     * The relationships that should always be eager loaded.
     *
     * @var array
     */
    {$withCode}

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    {$hiddenCode}

    /**
     * The relationships associated with the model.
     *
     * @var array
     */
    {$relationshipsCode}

    /**
     * The lifecycle hooks that are registered for the model.
     *
     * @var array
     */
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
        $hooksCode = [];

        if (empty($hooks)) {
            $hooksCode[] = <<<PHP
            // Add your lifecycle hooks here
PHP;
        }

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