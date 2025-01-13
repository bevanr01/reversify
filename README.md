# Reversify

> **Note**  
> Reversify is still in testing. Functionality may be unstable at best. If you would like to assist in getting this project off the ground, feel free to fork and submit pull requests for new features, bug fixes, or improvements.

![Packagist Version](https://img.shields.io/packagist/v/abevanation/reversify)
![Downloads](https://img.shields.io/packagist/dt/abevanation/reversify)


**Reversify** is a Laravel package designed to reverse engineer your existing database schema to create fully functional migrations, models, controllers that include relationships, traits, attributes, and more. It simplifies the process of reverse-engineering your database into Laravel components, making it ideal for legacy systems or rapid development.

---

## Features

- **Migrations**: Generate Laravel migrations from your database schema, including foreign keys and constraints.
- **Models**: Create Eloquent models with support for:
  - Traits
  - Fillable, guarded, casts, with, and hidden attributes
  - Relationships (e.g., `belongsTo`, `hasMany`, etc.)
  - Lifecycle hooks (`creating`, `updating`, etc.)
- **Controllers**: Generate CRUD resource controllers for your database tables.
- **Dynamic Configuration**: Fully configurable to suit your project's needs.

---

## Installation

1. Require the package via Composer:

   ```bash
   composer require abevanation/reversify
   ```

2. Publish the configuration file:

   ```bash
   php artisan vendor:publish --tag=reversify-config
   ```

   This will create a `config/reversify.php` file where you can customize the package behavior.

3. Add the `ReversifyBlueprintMacroServiceProvider` to your `bootstrap/providers.php` (Laravel 11) or `config/app.php` (Laravel 10 or earlier) if it isn't already registered:

   ```php
   App\Providers\ReversifyBlueprintMacroServiceProvider::class,
   ```

---

## Configuration

The configuration file (`config/reversify.php`) allows you to customize how the package generates migrations, models, and controllers.

### Example Configuration

```php
return [
    'ignore_tables' => ['migrations', 'failed_jobs'], // Tables to ignore during generation

    'models' => [
        'traits' => [
            'users' => ['Illuminate\Database\Eloquent\Factories\HasFactory', 'App\Traits\Loggable'],
            'App\Traits\SoftDeletes',
        ],
        'fillable' => [
            'users' => ['name', 'email', 'password'],
        ],
        'guarded' => ['created_by', 'updated_by'],
        'casts' => [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ],
        'with' => [
            'users' => ['profile'],
        ],
        'hidden' => [
            'users' => ['password', '*token'],
        ],
        'relationships' => [
            'users' => [
                'belongsTo' => ['App\Models\Organization', 'organization', 'organization_id', 'id'],
                'hasMany' => ['App\Models\Policy', 'policies', 'id', 'user_id'],
            ],
        ],
        'lifecycle_hooks' => [
            'users' => [
                'creating' => 'function ($model) {
                    $model->created_by = auth()->id();
                    $model->created_at = now();
                }',
                'updating' => 'function ($model) {
                    $model->updated_by = auth()->id();
                }',
            ],
        ],
    ],
];
```

---

## Commands

### Generate Migrations

```bash
php artisan reversify:migrations
```

- Scans the database schema and generates migration files for each table.
- A separate migration file is created to add foreign key constraints.

### Generate Models

```bash
php artisan reversify:models
```

- Creates Eloquent models for all tables (except ignored ones).
- Includes configurable attributes like `fillable`, `guarded`, `casts`, `relationships`, and `lifecycle hooks`.

### Generate Controllers

```bash
php artisan reversify:controllers
```

- Generates basic CRUD resource controllers for each table.
- Controllers are placed in the `app/Http/Controllers` directory.

### Generate All Components

```bash
php artisan reversify:generate
```

- Runs `reversify:migrations`, `reversify:models`, and `reversify:controllers` sequentially.

---

## Output Examples

### Migration File

For a table named `users`:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
```

### Model File

For a table named `users`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Loggable;

class User extends Model
{
    use HasFactory, SoftDeletes, Loggable;

    protected $fillable = ['name', 'email', 'password'];
    protected $guarded = ['created_by', 'updated_by'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];
    protected $with = ['profile'];
    protected $hidden = ['password', '*token'];

    public function organization()
    {
        return $this->belongsTo(App\Models\Organization::class, 'organization_id', 'id');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = auth()->id();
            $model->created_at = now();
        });
        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }
}
```

### Controller File

For a table named `users`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return User::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Define validation rules here
        ]);

        $model = User::create($validated);

        return response()->json($model, 201);
    }

    public function show(User $model)
    {
        return $model;
    }

    public function update(Request $request, User $model)
    {
        $validated = $request->validate([
            // Define validation rules here
        ]);

        $model->update($validated);

        return response()->json($model, 200);
    }

    public function destroy(User $model)
    {
        $model->delete();

        return response()->json(null, 204);
    }
}
```

---

## Contributing

Feel free to fork this repository and submit pull requests for new features or improvements. Please ensure all changes are tested.

---

## License

This package is open-source and licensed under the [MIT License](LICENSE).