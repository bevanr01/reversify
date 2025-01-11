<?php

namespace Bevanr01\Reversify\Generators;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ReversifyControllers
{
    protected $config;
    protected $outputPath;
    protected $ignoreTables;
    protected $useCommonFields = false;
    protected $useTimestamps = false;
    protected $useSoftDeletes = false;

    public function __construct()
    {
        $this->config = config('reversify');
        $this->outputPath = $this->config['controllers']['output_directory'];
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
            $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();
        }

        if (!$tables) {
            die("No tables exist or could not fetch.");
        }

        foreach ($tables as $table) {

            $className = $this->getControllerClassName($table);
            $modelName = $this->getModelClassName($table);

            if (in_array($table, $this->ignoreTables)) {
                echo "Skipping model generation for table: $table\n";
                continue;
            }

            $filePath = "{$this->outputPath}/{$className}.php";

            $content = $this->getControllerContent($className, $modelName);
            File::put($filePath, $content);

            echo "Controller created: $className (table: $table) at (location: $filePath)\n";
        }
    }

    protected function getControllerClassName(string $table): string
    {
        return ucfirst(Str::singular(str_replace('_', '', $table))) . 'Controller';
    }

    protected function getModelClassName(string $table): string
    {
        return ucfirst(Str::singular(str_replace('_', '', $table)));
    }

    protected function getControllerContent(string $className, string $modelName): string
    {
        return <<<PHP
<?php

namespace App\Http\Controllers;

use App\Models\\{$modelName};
use Illuminate\Http\Request;

class {$className} extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return {$modelName}::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request \$request)
    {
        \$validated = \$request->validate([
            // Define validation rules here
        ]);

        \$model = {$modelName}::create(\$validated);

        return response()->json(\$model, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show({$modelName} \$model)
    {
        return \$model;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request \$request, {$modelName} \$model)
    {
        \$validated = \$request->validate([
            // Define validation rules here
        ]);

        \$model->update(\$validated);

        return response()->json(\$model, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy({$modelName} \$model)
    {
        \$model->delete();

        return response()->json(null, 204);
    }
}
PHP;
    }
}