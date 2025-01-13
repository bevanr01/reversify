<?php

namespace Reversify\Generators;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ReversifyMigrations
{
    protected $config;
    protected $database;
    protected $file;
    protected $content;
    protected $outputPath;
    protected $useCommonFields = false;
    protected $useTimestamps = false;
    protected $useSoftDeletes = false;

    public function __construct($configuration, $database, $file, $content)
    {
        $this->config = $configuration->getConfiguration();
        $this->database = $database;
        $this->file = $file;
        $this->content = $content;
        
        $this->useCommonFields = $this->config['global']['use_common_fields'];
        $this->useTimestamps = $this->config['global']['use_timestamps'];
        $this->useSoftDeletes = $this->config['global']['use_soft_deletes'];
    }
    
    public function generate()
    {
        $directoryExists = $this->file->checkDirectory();
        $tables = $this->database->getTables();

        if (!$tables) {
            die("No tables exist or could not fetch.");
        }

        $index = 0;
        $foreignKeys = [];

        foreach ($tables as $table) {

            if ($this->file->shouldIgnore($table)) {
                echo "Skipping model generation for table: $table\n";
                continue;
            }
            
            $columns = $this->getTableColumns($table);

            if (!$columns) {
                echo "Error fetching columns for table $table\n";
                continue;
            }

            // Start building the migration file
            $migrationContent = $this->content->getBaseMigrationContent($table);

            $commonFields = $this->config['global']['common_fields'];
            $commonFieldsAdded = false;

            $timestampFields = ['created_at', 'updated_at'];
            $timestampFieldsAdded = false;

            $softDeleteFields = ['deleted_at'];
            $softDeleteFieldsAdded = false;

            $hasCommonFields = false;
            $hasTimestampFields = false;
            $hasSoftDeleteFields = false;

            $includeCommonFields = false;
            $includeTimestampFields = false;
            $includeSoftDeleteFields = false;

            foreach ($columns as $column) {
                if (in_array($column['name'], $commonFields)) {
                    $hasCommonFields = true;
                    break;
                } 
                
                if (in_array($column['name'], $timestampFields)) {
                    $hasTimestampFields = true;
                    break;
                } 
                
                if (in_array($column['name'], $softDeleteFields)) {
                    $hasSoftDeleteFields = true;
                    break;
                }
            }

            foreach ($columns as $column) {
                $columnName = $column['name'];
                $columnType = $column['type'];
                $primaryKey = array_key_exists('primary_key', $column) ? $column['primary_key'] : false;
                $isNullable = array_key_exists('nullable', $column) ? $column['nullable'] : false;
                $defaultValue = array_key_exists('default', $column) ? $column['default'] : null;
                $extra = array_key_exists('extra', $column) ? $column['extra'] : null;

                $migrationType = match (true) {
                    str_contains($columnType, 'int') && str_contains($extra, 'auto_increment') && $primaryKey => 'id',
                    str_contains($columnType, 'bigint') => 'bigInteger',
                    str_contains($columnType, 'int') => 'integer',
                    str_contains($columnType, 'varchar') => 'string',
                    str_contains($columnType, 'text') => 'text',
                    str_contains($columnType, 'datetime') => 'dateTime',
                    str_contains($columnType, 'timestamp') => 'timestamp',
                    str_contains($columnType, 'date') => 'date',
                    str_contains($columnType, 'float') => 'float',
                    str_contains($columnType, 'decimal') => 'decimal',
                    default => 'string', // Default to string for unmapped types
                };

                if ($hasCommonFields) {
                    $includeCommonFields = true;
                    $commonFieldsAdded = true;
                    if (in_array($columnName, $commonFields)) {
                        continue;
                    }
                }
                
                if ($hasTimestampFields) {
                    $includeTimestampFields = true;
                    $timestampFieldsAdded = true;
                    if (in_array($columnName, $timestampFields)) {
                        continue;
                    }
                }
                
                if ($hasSoftDeleteFields) {
                    $includeSoftDeleteFields = true;
                    $softDeleteFieldsAdded = true;
                    if (in_array($columnName, $softDeleteFields)) {
                        continue;
                    }
                }

                $migrationContent .= "            \$table->$migrationType('$columnName')";

                if (!$primaryKey && $extra === 'auto_increment') {
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

            if ($this->useCommonFields || $includeCommonFields) {
                $migrationContent .= "            \$table->commonFields();\n";
                $commonFieldsAdded = true;
            } 
            
            if ($this->useTimestamps || $includeTimestampFields) {
                $migrationContent .= "            \$table->timestamps();\n";
                $timestampFieldsAdded = true;
            } 
            
            if ($this->useSoftDeletes || $includeSoftDeleteFields) {
                $migrationContent .= "            \$table->softDeletes();\n";
                $softDeleteFieldsAdded = true;
            }

            $migrationContent .= "        });\n    }\n\n    public function down()\n    {\n        Schema::dropIfExists('$table');\n    }\n};\n";

            // Write the migration file
            $this->file->createMigrationFile($migrationContent, 'table', $index);

            echo "Migration file created for table $table: $migrationFilename\n";

            $foreignKeys = $this->database->getForeignKeys($table);

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
            
            $this->file->createMigrationFile($migrationContent, 'foreign_keys', $index);

            echo "Foreign key migration file created: $migrationFilename\n";
        }
    }
}