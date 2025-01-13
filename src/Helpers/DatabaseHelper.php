<?php

namespace Reversify\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DatabaseHelper
{
    public static function getTables(): array
    {
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

        return $tables;
    }

    public static function getModelClassName(string $table): string
    {
        return ucfirst(Str::singular(str_replace('_', '', $table)));
    }

    public static function getTableColumns(string $table): array
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
            $columns = DB::select("SHOW FULL COLUMNS FROM `$table`");

            return array_map(function ($column) {
                return [
                    'name' => $column->Field,
                    'type' => $column->Type,
                    'primary_key' => $column->Key === 'PRI',
                    'nullable' => $column->Null === 'YES',
                    'default' => $column->Default,
                    'extra' => $column->Extra,
                ];
            }, $columns);
        }
    }

    public static function getPrimaryKey(string $table): array
    {
        $primaryKey = DB::select("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'");

        return $primaryKey;
    }

    public static function getForeignKeys(string $table): array
    {
        $foreignKeyResult = [];

        $database = DB::connection()->getDatabaseName();

        if (DB::connection()->getDriverName() === 'sqlite') {
            $foreignKeyResult = DB::select("PRAGMA foreign_key_list('$table');");
        } 
        else {
            $foreignKeyResult = DB::select("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [$database, $table]);

            // Transform the result if necessary
            $foreignKeys = array_map(function ($foreignKey) {
                return [
                    'table' => $table,
                    'constraint_name' => $foreignKey->CONSTRAINT_NAME,
                    'column_name' => $foreignKey->COLUMN_NAME,
                    'referenced_table_name' => $foreignKey->REFERENCED_TABLE_NAME,
                    'referenced_column_name' => $foreignKey->REFERENCED_COLUMN_NAME,
                ];
            }, $foreignKeyResult);
        }

        return $foreignKeys;
    }

    public static function getUniqueKeys(string $table): array
    {
        $uniqueKeys = DB::select("SHOW INDEXES FROM $table WHERE Non_unique = 0");

        return $uniqueKeys;
    }

    public static function getIndexes(string $table): array
    {
        $indexes = DB::select("SHOW INDEXES FROM $table");

        return $indexes;
    }

    public static function getTableComment(string $table): array
    {
        $tableComment = DB::select("SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_NAME = '$table'");

        return $tableComment;
    }

    public static function getColumnComment(string $table, string $column): array
    {
        $columnComment = DB::select("SELECT COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_NAME = '$table' AND COLUMN_NAME = '$column'");

        return $columnComment;
    }

    public static function getEnumValues(string $table, string $column): array
    {
        $enumValues = DB::select("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_NAME = '$table' AND COLUMN_NAME = '$column'");

        return $enum;
    }
}