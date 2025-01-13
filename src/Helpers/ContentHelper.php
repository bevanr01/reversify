<?php

namespace Reversify\Helpers;

use Illuminate\Support\Str;

class ContentHelper
{
    public function __construct()
    {

    }

    public static function getBaseMigrationContent(string $table): string
    {
        $migrationContent = "<?php\n\n";
        $migrationContent .= "use Illuminate\Database\Migrations\Migration;\n";
        $migrationContent .= "use Illuminate\Database\Schema\Blueprint;\n";
        $migrationContent .= "use Illuminate\Support\Facades\Schema;\n\n";
        $migrationContent .= "return new class extends Migration\n";
        $migrationContent .= "{\n";
        $migrationContent .= "    public function up()\n";
        $migrationContent .= "    {\n";
        $migrationContent .= "        Schema::table('$table', function (Blueprint \$table) {\n\n";

        return $migrationContent;
    }
}