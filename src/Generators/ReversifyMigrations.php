<?php

namespace Bevanr01\Reversify\Generators;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ReversifyMigrations
{
    protected $config;
    protected $filePrefix;
    protected $outputPath;
    protected $ignoreTables;
    protected $useCommonFields = false;
    protected $useTimestamps = false;
    protected $useSoftDeletes = false;

    public function __construct()
    {
        $this->config = config('reversify');
        $this->outputPath = $this->config['migrations']['output_directory'];
        $this->filePrefix = $this->config['migrations']['file_prefix'];
        $this->ignoreTables = $this->config['global']['ignore_tables'];
        $this->useCommonFields = $this->config['global']['use_common_fields'];
        $this->useTimestamps = $this->config['global']['use_timestamps'];
        $this->useSoftDeletes = $this->config['global']['use_soft_deletes'];
    }
    
    public function generate()
    {
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0777, true);
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            $tables = collect(DB::select('SELECT name FROM sqlite_master WHERE type = "table"'))
                ->pluck('name')
                ->toArray();
        } else {
            $tablesObject = DB::select('SHOW TABLES');
            $tables = array_map(function ($table) {
                return reset($table); // Get the first value in the object
            }, $tablesObject);
        }

                
        if (!$tables) {
            die("No tables exist or could not fetch.");
        }

        $index = 0;
        $foreignKeys = [];

        foreach ($tables as $table) {

            if (in_array($table, $this->ignoreTables)) {
                echo "Skipping model generation for table: $table\n";
                continue;
            }
            
            // Fetch table columns
            $columns = $this->getTableColumns($table);

            if (!$columns) {
                echo "Error fetching columns for table $table\n";
                continue;
            }

            // Start building the migration file
            $migrationContent = "<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\Schema;\n\nreturn new class extends Migration {\n";
            $migrationContent .= "    public function up()\n    {\n        Schema::create('$table', function (Blueprint \$table) {\n";

            $commonFields = $this->config['global']['common_fields'];
            $commonFieldsAdded = false;

            $timestampFields = ['created_at', 'updated_at'];
            $timestampFieldsAdded = false;

            $softDeleteFields = ['deleted_at'];
            $softDeleteFieldsAdded = false;

            $hasCommonFields = false;
            foreach ($columns as $column) {
                if (in_array($column['name'], $commonFields)) {
                    $hasCommonFields = true;
                    break;
                }
            }

            foreach ($columns as $column) {
                $columnName = $column['name'];
                $columnType = $column['type'];
                $isNullable = array_key_exists('nullable', $column) ? $column['nullable'] : null;
                $defaultValue = array_key_exists('default', $column) ? $column['default'] : null;
                $extra = array_key_exists('extra', $column) ? $column['extra'] : null;

                // Map MySQL types to Laravel migration methods
                $migrationType = match (true) {
                    str_contains($columnType, 'int') => 'integer',
                    str_contains($columnType, 'bigint') => 'bigInteger',
                    str_contains($columnType, 'varchar') => 'string',
                    str_contains($columnType, 'text') => 'text',
                    str_contains($columnType, 'datetime') => 'dateTime',
                    str_contains($columnType, 'timestamp') => 'timestamp',
                    str_contains($columnType, 'date') => 'date',
                    str_contains($columnType, 'float') => 'float',
                    str_contains($columnType, 'decimal') => 'decimal',
                    default => 'string', // Default to string for unmapped types
                };

                if ($this->useCommonFields) {
                    if ($commonFieldsAdded) {
                        if (in_array($columnName, $commonFields)) {
                            continue;
                        }
                    } else {
                        $migrationContent .= "            \$table->commonFields();\n";
                        $commonFieldsAdded = true;
                        if (in_array($columnName, $commonFields)) {
                            continue;
                        }
                    }
                } else if ($this->useTimestamps) {

                    if ($timestampFieldsAdded) {
                        if (in_array($columnName, $timestampFields)) {
                            continue;
                        }
                    } else {
                        $migrationContent .= "            \$table->timestamps();\n";
                        $timestampFieldsAdded = true;
                        if (in_array($columnName, $timestampFields)) {
                            continue;
                        }
                    }
                } else if ($this->useSoftDeletes) {

                    if ($softDeleteFieldsAdded) {
                        if (in_array($columnName, $softDeleteFields)) {
                            continue;
                        }
                    } else {
                        $migrationContent .= "            \$table->softDeletes();\n";
                        $softDeleteFieldsAdded = true;
                        if (in_array($columnName, $softDeleteFields)) {
                            continue;
                        }
                    }
                } else {
                    $migrationContent .= "            \$table->$migrationType('$columnName')";

                    if ($extra === 'auto_increment') {
                        $migrationContent .= "->autoIncrement()";
                    }

                    if ($columnName === 'id' && str_contains($columnType, 'int')) {
                        $migrationContent .= "->primary()";
                    }

                    if ($isNullable) {
                        $migrationContent .= "->nullable()";
                    }

                    if ($defaultValue !== null) {
                        $migrationContent .= "->default('$defaultValue')";
                    }

                    $migrationContent .= ";\n";
                }
            }

            $migrationContent .= "        });\n    }\n\n    public function down()\n    {\n        Schema::dropIfExists('$table');\n    }\n};\n";

            // Write the migration file
            $timestamp = date('Y_m_d_His');
            $migrationFilename = $this->filePrefix === 'timestamp' ? $this->outputPath . '/' . date('Y_m_d_His') . "_create_{$table}_table.php" : $this->outputPath . '/' . $index . "_create_{$table}_table.php";
            File::put($migrationFilename, $migrationContent);

            echo "Migration file created for table $table: $migrationFilename\n";

            $database = DB::connection()->getDatabaseName();

            $foreignKeyResult = [];

            if (DB::connection()->getDriverName() === 'sqlite') {
                $foreignKeyResult = DB::select("PRAGMA foreign_key_list('$table');");
            } else {
                $foreignKeyResult = DB::select("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [$database, $table]);
            }

            foreach ($foreignKeyResult as $fkRow) {
                $foreignKeys[] = [
                    'table' => $table,
                    'column' => $fkRow['COLUMN_NAME'],
                    'referenced_table' => $fkRow['REFERENCED_TABLE_NAME'],
                    'referenced_column' => $fkRow['REFERENCED_COLUMN_NAME'],
                    'constraint_name' => $fkRow['CONSTRAINT_NAME'],
                ];
            }

            $index++;
        }

        if (!empty($foreignKeys)) {
            $migrationContent = "<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\Schema;\n\nreturn new class extends Migration {\n";
            $migrationContent .= "    public function up()\n    {\n";

            foreach ($foreignKeys as $fk) {
                $migrationContent .= "        Schema::table('{$fk['table']}', function (Blueprint \$table) {\n";
                $migrationContent .= "            \$table->foreign('{$fk['column']}')->references('{$fk['referenced_column']}')->on('{$fk['referenced_table']}')->onDelete('cascade');\n";
                $migrationContent .= "        });\n";
            }

            $migrationContent .= "    }\n\n    public function down()\n    {\n";

            foreach ($foreignKeys as $fk) {
                $migrationContent .= "        Schema::table('{$fk['table']}', function (Blueprint \$table) {\n";
                $migrationContent .= "            \$table->dropForeign('{$fk['constraint_name']}');\n";
                $migrationContent .= "        });\n";
            }

            $migrationContent .= "    }\n};\n";

            // Write the foreign key migration file
            $timestamp = date('Y_m_d_His', strtotime('+1 second'));
            $migrationFilename = $this->filePrefix === 'timestamp' ? $this->outputPath . '/' . date('Y_m_d_His') . "_add_foreign_keys.php" : $this->outputPath . '/' . $index . "_add_foreign_keys.php";
            File::put($migrationFilename, $migrationContent);

            echo "Foreign key migration file created: $migrationFilename\n";
        }
    }

    protected function getTableColumns(string $table): array
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA table_info('$table');"))
            ->map(function ($column) {
                return [
                    'name' => $column->name,
                    'type' => $column->type,
                    'nullable' => !$column->notnull,
                    'default' => $column->dflt_value,
                ];
            })
            ->toArray();
        } else {
            return DB::getSchemaBuilder()->getColumnListing($table);
        }
    }
}