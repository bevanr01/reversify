<?php

namespace Reversify\Helpers;

use Illuminate\Support\Facades\File;

class FileHelper
{

    protected $config;
    protected $migrationsOutputPath;
    protected $modelsOutputPath;
    protected $controllersOutputPath;
    protected $filePrefix;
    protected $ignoreTables;

    public function __construct()
    {
        $this->config = config('reversify');
        $this->migrationsOutputPath = $this->config['migrations']['output_directory'];
        $this->modelsOutputPath = $this->config['models']['output_directory'];
        $this->controllersOutputPath = $this->config['controllers']['output_directory'];
        $this->filePrefix = $this->config['migrations']['file_prefix'];
        $this->ignoreTables = $this->config['global']['ignore_tables'];
    }

    public static function checkDirectory(): bool
    {
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0777, true);
        }

        return true;
    }

    public static function shouldIgnore(string $table): bool
    {
        return in_array($table, $this->ignoreTables);
    }

    public static function createMigrationFile(string $content, string $type, int $index): void
    {
        $timestamp = date('Y_m_d_His', strtotime('+1 second'));
        $filename = '';

        if ($type === 'table') {
            $filename = $this->filePrefix === 'timestamp' ? $this->outputPath . '/' . date('Y_m_d_His') . "_create_{$table}_table.php" : $this->outputPath . '/' . $index . "_create_{$table}_table.php";
        } elseif ($type === 'foreign_keys') {
            $filename = $this->filePrefix === 'timestamp' ? $this->outputPath . '/' . date('Y_m_d_His') . "_add_foreign_keys.php" : $this->outputPath . '/' . $index . "_add_foreign_keys.php";
        }
            
        File::put($filename, $migrationContent);
    }

    public static function createModelFile(string $content, string $table): void
    {
        $modelFilename = $this->outputPath . '/' . $table . '.php';
        File::put($modelFilename, $content);
    }

    public static function getFiles(string $path, string $extension): array
    {
        $files = scandir($path);
        $files = array_filter($files, function ($file) use ($extension) {
            return pathinfo($file, PATHINFO_EXTENSION) === $extension;
        });

        return $files;
    }

    public static function getDirectories(string $path): array
    {
        $directories = scandir($path);
        $directories = array_filter($directories, function ($directory) use ($path) {
            return is_dir($path . '/' . $directory) && $directory !== '.' && $directory !== '..';
        });

        return $directories;
    }

    public static function getFilesAndDirectories(string $path): array
    {
        $filesAndDirectories = scandir($path);
        $filesAndDirectories = array_filter($filesAndDirectories, function ($fileOrDirectory) use ($path) {
            return $fileOrDirectory !== '.' && $fileOrDirectory !== '..';
        });

        return $filesAndDirectories;
    }
}