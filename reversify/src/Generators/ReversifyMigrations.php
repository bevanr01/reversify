<?php

namespace Abevanation\Reversify\Generators;

use Illuminate\Support\Facades\DB;

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
        $this->ignoreTables = $this->config['reversify']['ignore_tables'];
        $this->useCommonFields = $this->config['reversify']['use_common_fields'];
        $this->useTimestamps = $this->config['reversify']['use_timestamps'];
        $this->useSoftDeletes = $this->config['reversify']['use_soft_deletes'];
    }
    
    public function generate()
    {
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0777, true);
        }

        $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();
        if (!$tables) {
            die("Error fetching tables: " . DB::getPdo()->errorInfo());
        }

        $index = 0;
        $foreignKeys = [];

        foreach ($tables as $table) {

            if (in_array($table, $this->ignoreTables)) {
                echo "Skipping model generation for table: $table\n";
                continue;
            }
            
            $tableName = $table;

            // Fetch table columns
            $columnsResult = DB::select("SHOW FULL COLUMNS FROM `$tableName`");

            if (!$columnsResult) {
                echo "Error fetching columns for table $tableName: " . DB::getPdo()->errorInfo() . "\n";
                continue;
            }

            // Start building the migration file
            $migrationContent = "<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\Schema;\n\nreturn new class extends Migration {\n";
            $migrationContent .= "    public function up()\n    {\n        Schema::create('$tableName', function (Blueprint \$table) {\n";

            $commonFields = $this->config['reversify']['common_fields'];
            $commonFieldsAdded = false;

            $timestampFields = ['created_at', 'updated_at'];
            $timestampFieldsAdded = false;

            $softDeleteFields = ['deleted_at'];
            $softDeleteFieldsAdded = false;

            $hasCommonFields = false;
            foreach ($columnsResult as $columnRow) {
                if (in_array($columnRow->Field, $commonFields)) {
                    $hasCommonFields = true;
                    break;
                }
            }

            foreach ($columnsResult as $columnRow) {
                $columnName = $columnRow['Field'];
                $columnType = $columnRow['Type'];
                $isNullable = $columnRow['Null'] === 'YES';
                $defaultValue = $columnRow['Default'];
                $extra = $columnRow['Extra'];

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

            $migrationContent .= "        });\n    }\n\n    public function down()\n    {\n        Schema::dropIfExists('$tableName');\n    }\n};\n";

            // Write the migration file
            $timestamp = date('Y_m_d_His');
            $migrationFilename = $this->outputPath . '/' . $this->filePrefix === 'timestamp' ? date('Y_m_d_His') : $index . "_create_{$tableName}_table.php";
            file_put_contents($migrationFilename, $migrationContent);

            echo "Migration file created for table $tableName: $migrationFilename\n";

            $foreignKeyResult = DB::select("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [$database, $tableName]);

            foreach ($foreignKeyResult as $fkRow) {
                $foreignKeys[] = [
                    'table' => $tableName,
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
            $migrationFilename = $this->outputPath . '/' . $this->filePrefix === 'timestamp' ? date('Y_m_d_His') : $index . "_add_foreign_keys.php";
            file_put_contents($migrationFilename, $migrationContent);

            echo "Foreign key migration file created: $migrationFilename\n";
        }
    }
}