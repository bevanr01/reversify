<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reversify Configuration
    |--------------------------------------------------------------------------
    | Below are all of the reversify configuration options.
    | There are core reversify configuration options as well as specific 
    | configurations for models, controllers, and migrations to help tailor 
    | the generated code to your needs.
    |
    */

    'reversify' => [

        /* 
        -------------------------------------------------------------------------
        | Common Fields
        -------------------------------------------------------------------------
        | If common fields is used, the following fields will be added to each 
        | of your models using a Blueprint Macro regardless of whether they exist
        | in the current database table or not. If using common fields, you can 
        | include the timestamp fields and soft delete field in the common fields
        | or omit them and use the use_timestamps and use_soft_deletes options
        | instead. Do not do both.
        |
        */

        'use_common_fields' => true,

        'common_fields' => [
            [
                'name' => 'created_by', 
                'type' => 'bigInteger',
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
            ],
            [
                'name' => 'updated_by', 
                'type' => 'bigInteger',
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
            ],
            [
                'name' => 'organization_id', 
                'type' => 'bigInteger',
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
            ],
        ],

        'use_soft_deletes' => true,

        'use_timestamps' => true,

        'ignore_tables' => [
            'migrations',
            'failed_jobs',
            'password_resets',
            'cache',
            'sessions',
        ],

        /*
        ---------------------------------------------------------------------------
        | Pluralization
        ---------------------------------------------------------------------------
        |
        | Optionally disable pluralization of model names.
        |
        */
        
        'pluralize' => true,

        'declarations' => false,

        /*
        ---------------------------------------------------------------------------
        | Table Prefix
        ---------------------------------------------------------------------------
        |
        | Remove unwanted table prefixes when generating your files.
        |
        */

        'table_prefix' => '',
    ],

    /*
    ---------------------------------------------------------------------------
    | Models Configuration
    ---------------------------------------------------------------------------
    | Below are all of the configuration options related specifically to models.
    |
    */

    'models' => [

        /*
        ---------------------------------------------------------------------------
        | Traits
        ---------------------------------------------------------------------------
        | 'table' => 'users', 'traits' => ['App\Traits\Loggable'],
        | 
        |  Or without nested array to apply to all models
        | 
        | 'App\Traits\Loggable',
        | 
        */

        'traits' => [
            'App\Traits\Loggable',
            'Illuminate\Database\Eloquent\Factories\HasFactory',
        ],

        /*
        ---------------------------------------------------------------------------
        | Fillable
        ---------------------------------------------------------------------------
        | 'table' => 'users', 'fields' => ['name', 'email', 'password'],
        | 
        | Or without nested array to apply to all models
        | 
        | 'name', 'email', 'password',
        | 
        */

        'fillable' => [],

        /*
        ---------------------------------------------------------------------------
        | Guarded
        ---------------------------------------------------------------------------
        | 'table' => '', 'fields' => [],
        |
        | Or without nested array to apply to all models
        |
        | 'id', 'password', 'remember_token',
        | 
        */

        'guarded' => ['created_by', 'updated_by'],
        
        /*
        ---------------------------------------------------------------------------
        | Casts
        ---------------------------------------------------------------------------
        | 'table' => '', 'fields' => [],
        | 
        | Or without nested array to apply to all models
        | 
        | 'id' => 'int',
        | 
        */

        'casts' => ['created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime'],

        /*
        ---------------------------------------------------------------------------
        | With
        ---------------------------------------------------------------------------
        | 'table' => '', ['users', 'organizations'],
        | 
        | Or without nested array to apply to all models
        | 
        | 'users', 'organizations',
        | 
        */

        'with' => [],

        /*
        ---------------------------------------------------------------------------
        | Hidden
        ---------------------------------------------------------------------------
        |  'table' => '', 'fields' => [],
        | 
        | Or without nested array to apply to all models
        | * is a wildcard to hide all fields with the same suffix
        | 'password', '*token', 'api_token', 'secret',
        | 
        */

        'hidden' => ['*password', '*token', '*secret'],

        /*
        ---------------------------------------------------------------------------
        | Relationships
        ---------------------------------------------------------------------------
        | 'table' => '', 'relationships' => [
        |    'belongsTo' => ['App\Models\Organization', 'organizations', 'organization_id', 'id'],
        |    'hasMany' => ['App\Models\PolicyCoverage', 'policy_coverages', 'id', 'policy_id'],
        | ],
        | 
        | Or without nested array to apply to all models
        | 
        | 'belongsTo' => ['App\Models\Organization', 'organizations', 'organization_id', 'id'],
        | 'hasMany' => ['App\Models\PolicyCoverage', 'policy_coverages', 'id', 'policy_id']
        | 
        */

        'relationships' => [],

        /*
        ---------------------------------------------------------------------------
        | Lifecycle Hooks
        ---------------------------------------------------------------------------
        | 'table' => '', 'hooks' => [
        |   'creating' => 'function ($model) {
        |      $model->created_by = auth()->user()->id;
        |      $model->created_at = now();
        |      $model->updated_at = now();
        |      $model->updated_by = auth()->user()->id;
        |      if (Schema::hasTable('organizations') && !empty(auth()->user()->organization_id) && empty($model->organization_id)) {
        |         $organization = Organization::find(auth()->user()->organization_id);
        |          if ($organization) {
        |             $model->organization_id = $organization->id;
        |          }
        |      }
        |   }',
        |  ],
        |  Or 
        |  'creating' => 'function ($model) {
        |      $model->created_by = auth()->user()->id;
        |      $model->created_at = now();
        |          $model->updated_at = now();
        |          $model->updated_by = auth()->user()->id;
        |          if (Schema::hasTable('organizations') && !empty(auth()->user()->organization_id) && empty($model->organization_id)) {
        |              $organization = Organization::find(auth()->user()->organization_id);
        |              if ($organization) {
        |                  $model->organization_id = $organization->id;
        |              }
        |          }
        |      }',
        */
        'lifecycle_hooks' => [],

        /*
        ---------------------------------------------------------------------------
        | Date Format
        ---------------------------------------------------------------------------
        |
        | The date format that should be used when casting dates in your models.
        |
        */

        'date_format' => 'Y-m-d H:i:s',

        /*
        ---------------------------------------------------------------------------
        | Base Model
        ---------------------------------------------------------------------------
        |
        | The base model that all generated models should extend.
        |
        */

        'base_model' => Illuminate\Database\Eloquent\Model::class,

        /*
        ---------------------------------------------------------------------------
        | Custom Mapping
        ---------------------------------------------------------------------------
        |
        | If you want to map your database columns to different names in your
        | models, you can specify the mapping here.
        |
        | Example
        |
        | 'table' => 'user_has_models', 'model' => 'App\Models\UserModel',
        |
        */

        'custom_mapping' => [],

        /*
        ---------------------------------------------------------------------------
        | Pagination
        ---------------------------------------------------------------------------
        |
        | This setting determines the default value for records returned when 
        | querying a model.
        |
        */

        'per_page' => 25,

        /*
        -------------------------------------------------------------------------
        | Output Directory
        -------------------------------------------------------------------------
        | The directory where the generated models should be created.
        | If you want to create the models in a subdirectory, you can specify
        | the subdirectory here. For example, if you want to create the models
        | in app/Models/Admin, you can set the output_directory to
        | app_path('Models/Admin').
        -------------------------------------------------------------------------
        | Default is app/Models.
        -------------------------------------------------------------------------
        |
        */

        'output_directory' => app_path('Models'),

        /*
        | Namespace
        -------------------------------------------------------------------------
        | The namespace that will be used for the generated models.
        | If you want to create the models in a subnamespace, you can specify
        | the subnamespace here. For example, if you want to create the models
        | in the namespace App\Models\Admin, you can set the namespace to
        | App\Models\Admin.
        | Default is App\Models.
        -------------------------------------------------------------------------
        |
        */

        'namespace' => 'App\Models',

        /*
        ---------------------------------------------------------------------------
        | Columns
        ---------------------------------------------------------------------------
        |
        | Optionally include an array of column names on your model for accessibility.
        |
        */

        'columns' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Controllers Configuration
    |--------------------------------------------------------------------------
    | Below are all of the configuration options related specifically to 
    | controllers.
    |
    */

    'controllers' => [

        /*
        | Output Directory
        -------------------------------------------------------------------------
        | The directory where the generated controllers should be created.
        | If you want to create the controllers in a subdirectory, you can
        | specify the subdirectory here. For example, if you want to create
        | the controllers in app/Http/Controllers/Admin, you can set the
        | output_directory to app_path('Http/Controllers/Admin').        -------------------------------------------------------------------------
        | Default is app/Http/Controllers.
        -------------------------------------------------------------------------
        |
        */

        'output_directory' => app_path('Http/Controllers'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Migrations Configuration
    |--------------------------------------------------------------------------
    | Below are all of the configuration options related specifically to 
    | migrations.
    |
    */

    'migrations' => [

        'use_lowercase' => true,

        /*
        -------------------------------------------------------------------------
        | File Prefix
        -------------------------------------------------------------------------
        | Current supported options are timestamps or index (i.e. 0, 1, 2, 3). 
        | If you prefer to have your migrations like 0_create_users_table.php,
        | you can set the file_prefix to index.
        |
        */

        'file_prefix' => 'timestamps',

        /*
        -------------------------------------------------------------------------
        | Output Directory
        -------------------------------------------------------------------------
        | The directory where the generated migrations should be created.
        | If you want to create the migrations in a subdirectory, you can
        | specify the subdirectory here. For example, if you want to create
        | the migrations in database/migrations/admin, you can set the
        | output_directory to database_path('migrations/admin').        -------------------------------------------------------------------------
        | Default is database/migrations.
        -------------------------------------------------------------------------
        |
        */

        'output_directory' => database_path('migrations'),
    ],
];
