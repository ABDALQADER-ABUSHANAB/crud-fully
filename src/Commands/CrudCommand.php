<?php

namespace abdalqader\crudcommand\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;


use function Laravel\Prompts\text;

class CrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:generate {--modelname= : The name of the model} {--columns= : The columns of the table (comma-separated)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $modelName = text('what is the modelname' ,
        required: true);
        $columns = text('what are the columns the format name:string,age:integer , user_id:forginId:users',
            required: true);
        $apiOrBlade = $this->choice('Api Controller or Not Api Controller?', ['Api Controller', 'Not Api Controller']);

                // Parse the columns option into an array
        $parsedColumns = collect(explode(',', $columns))->map(function ($column) {
            // Split the column definition into name and type
            $parts = explode(':', $column);
            return [
                'name' => $parts[0],
                'type' => $parts[1],
                'foreignKey' => $parts[2] ?? null,
            ];
        })->toArray();

        //dd($parsedColumns);

        // Generate migration file
        $this->call('make:model', ['name' => $modelName,'--seed' => true]);

        $migrationFileName = $this->getMigrationFileName($modelName);

        if(! $migrationFileName){
            $this->call('make:migration', [
                'name' => 'create_' . Str::plural(strtolower($modelName)) . '_table',
                '--create' => strtolower($modelName),
                '--table' => strtolower($modelName),
            ]);
        }
       // $migrationFileName = $this->getMigrationFileName($modelName);


        $this->appendColumnsToMigration($modelName, $parsedColumns);

        $this->call('make:controller', [
            'name' => $modelName . 'Controller',
            '--requests'=> true,
            '--resource'=> true,
            '--model' => $modelName,
        ]);
        foreach (['Create', 'Update', 'Store'] as $action) {
            $requestName = "{$action}{$modelName}Request";
            $this->call('make:request', [
                'name' => $requestName,
                '--force' => true
            ]);
        }
        $controllerContent = $this->generateController($modelName,$apiOrBlade);
        $controllerFile = app_path("Http/Controllers/{$modelName}Controller.php");
        file_put_contents($controllerFile, $controllerContent);
        $rules = $this->getRequestRules($columns, $modelName);
        $this->appendRuleToRequests($modelName, $rules);
        $this->insertFillableIntoModel($modelName, $columns);

        $this->info('CRUD generated successfully!');
    }
    /**
     * Get the migration file name for the given model name.
     */

    private function getMigrationFileName($modelName)
    {
        $migrationFile = glob(database_path("migrations/*_create_" . strtolower($modelName) . "_table.php"));

        if($migrationFile == []){
            $migrationFile = glob(database_path("migrations/*_create_" . strtolower($modelName) . "s_table.php"));
        }
       // $migrationContent = file_get_contents($migrationFile[0]);

        return $migrationFile;
    }

/**
 * Append columns to the migration file.
 */
    private function appendColumnsToMigration($modelName, $columns)
    {
        if(substr($modelName, -1) === 'y'){
            $modelName = substr($modelName, 0, -1) . 'ie';
        }

        $migrationFile = glob(database_path("migrations/*_create_" . strtolower($modelName) . "_table.php"));

        if($migrationFile == []){
            $migrationFile = glob(database_path("migrations/*_create_" . strtolower($modelName) . "s_table.php"));
        }
        $migrationContent = file_get_contents($migrationFile[0]);

        // Add new columns to the migration content
        if ($columns) {
            $columnsDefinition = collect($columns)->map(function ($column)  use ($migrationContent){
            if($column['type'] == 'foreignId' || $column['type'] == 'forignId' ){
                return sprintf("\$table->%s('%s')->constrained('%s');", $column['type'], $column['name'], $column['foreignKey']);
            }
                return sprintf("\$table->%s('%s');", $column['type'], $column['name']);
            })->implode("\n\t\t\t\t");

            // Find the position to insert the new columns
            $insertPosition = strpos($migrationContent, '$table->id();') + strlen('$table->id();');

            // Insert the new columns into the migration content
            foreach($columns as $column){
                if($column['type'] == 'foreignId' || $column['type'] == 'forignId' ){
                    $migrationContent = str_replace('$table->'.$column['type'].'('."'".$column['name']."'".')->constrained('."'".$column['foreignKey']."'".');','',$migrationContent);
                }else{
                    $migrationContent = str_replace('$table->'.$column['type'].'('."'".$column['name']."'".');','',$migrationContent);
                }
            };
            $migrationContent = substr_replace($migrationContent, "\n\t\t\t\t{$columnsDefinition}", $insertPosition, 0);
            file_put_contents($migrationFile[0], $migrationContent);
            //            dd($migrationFile);
        }
    }
/**
 * Get the validation rules for the given columns.
 */
    private function getRequestRules($columns, $modelName)
    {

        //return ('dskdskdnknd');
        // Ensure $columns is an array
        $columnsArray = collect(explode(',', $columns))->mapWithKeys(function ($column) {
            [$name, $type] = explode(':', $column);
            return [$name => $type];
        })->toArray();

        $rules = collect($columnsArray)->map(function ($type, $name) {
            if ($type === 'string') {
                return 'required|string';
            } elseif ($type === 'integer' || $type === 'int') {
                return 'required|numeric';
            } elseif ($name === 'email') {
                return 'required|email|unique:users,email';
            } elseif ($name === 'password') {
                return 'required|string|min:8|confirmed';
            } elseif ($type === 'date') {
                return 'required|date';
            } else {
                return 'required';
            }
        })->toJson();


        return $rules;
    }
    /**
     * Append validation rules to the requests.
     */
    private function appendRuleToRequests($modelName, $rules)
    {
        foreach (['Create', 'Update'] as $action) {
            $requestName = "{$action}{$modelName}Request";
            $requestFile = app_path("Http/Requests/{$requestName}.php");
            $requestContent = file_get_contents($requestFile);

            // Replace the authorize method with the desired implementation
            $requestContent = str_replace(
                ['false'],
                    ['true'],
                        $requestContent
                    );
                   // dd($newRequestContent);
            $insertPosition = strpos($requestContent, 'return [') + strlen('return [');

            $transformedRules = str_replace(['{', '}', '[', ']', ':'], ['', '', '', '', ' => '], $rules);
            // dd($transformedRules);
            $rulesDef = collect($transformedRules)->map(function ($rule) {
                return "\n\t\t\t $rule,";
            })->implode("");
            //dd($rulesDef);

            $newRequestContent = substr_replace($requestContent, $rulesDef, $insertPosition, 0);
            file_put_contents($requestFile, $newRequestContent);
        }
    }

private function generateController($modelName ,$apiOrBlade)
{
if( $apiOrBlade  == 'Not Api Controller'){


    $controllerTemplate = "<?php

namespace App\Http\Controllers;

use App\Models\\$modelName;
use App\Http\Requests\Store{$modelName}Request;
use App\Http\Requests\Update{$modelName}Request;

class {$modelName}Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('{$modelName}.index', [
            '{$modelName}s' => {$modelName}::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('{$modelName}.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Store{$modelName}Request \$request)
    {
        {$modelName}::create(\$request->validated());
        return redirect()->route('{$modelName}.index')->with('success', '{$modelName} created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show({$modelName} \${$modelName})
    {
        return view('{$modelName}.show', compact('{$modelName}'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit({$modelName} \${$modelName})
    {
        return view('{$modelName}.edit', compact('{$modelName}'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Update{$modelName}Request \$request, {$modelName} \${$modelName})
    {
        \${$modelName}->update(\$request->validated());
        return redirect()->route('{$modelName}.index')->with('success', '{$modelName} updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy({$modelName} \${$modelName})
    {
        \${$modelName}->delete();
        return redirect()->route('{$modelName}.index')->with('success', '{$modelName} deleted successfully.');
    }
}
";
}else{
    $controllerTemplate = "<?php

    namespace App\Http\Controllers;

    use App\Models\\$modelName;
    use App\Http\Requests\Store{$modelName}Request;
    use App\Http\Requests\Update{$modelName}Request;
    use Illuminate\Http\Request;


class {$modelName}Controller extends Controller
{
        /**
         * Display a listing of the resource.
         */
        public function index()
        {
            return $modelName::all();
        }

        /**
         * Store a newly created resource in storage.
         */
        public function store(Store{$modelName}Request \$request)
        {
           \${$modelName} = $modelName::create(\$request->validated());
             return response()->json(['message' => '$modelName created successfully', 'data' => \${$modelName}], 201);

        }

        /**
         * Display the specified resource.
         */
        public function show($modelName \$$modelName)
        {
            return \${$modelName};
        }

        /**
         * Update the specified resource in storage.
         */
        public function update(Update{$modelName}Request \$request, $modelName \$$modelName)
        {
            \${$modelName}->update(\$request->validated());
            return response()->json(['message' => '$modelName updated successfully', 'data' => \${$modelName}]);

        }

        /**
         * Remove the specified resource from storage.
         */
        public function destroy($modelName \${$modelName})
        {
            \${$modelName}->delete();
            return response()->json(['message' => '$modelName deleted successfully']);
        }
    }
    ";
}

    return $controllerTemplate;
}

// private function insertFillableIntoModel($modelName, $columnsString)
// {
//             // Define the model file path
//             $modelFilePath = app_path('Models/' . $modelName . '.php');

//             // Read the current content of the model file
//             $modelContent = file_get_contents($modelFilePath);

//             // Extract column names from the columns string
//             $columns = collect(explode(',', $columnsString))->map(function ($column) {
//                 return explode(':', $column)[0];
//             })->toArray();

//             // Find the position to insert the fillable array
//             $fillablePosition = strpos($modelContent, 'use HasFactory;');

//             // Calculate the indentation
//             $indentation = substr($modelContent, $fillablePosition, strpos($modelContent, ']', $fillablePosition) - $fillablePosition);

//             // Define the fillable array as a string
//             $fillable = $indentation . " use HasFactory;\n   protected \$fillable = [\n" . $indentation . "        '" . implode("',\n" . $indentation . "        '", $columns) . "',\n" . $indentation . "    ];\n";

//             // Insert the fillable array into the model content
//            // $modelContent = str_replace('use HasFactory;'. $fillable , $fillable, $modelContent);
//             $modelContent = str_replace('use HasFactory;', $fillable, $modelContent);

//             // Save the modified content back to the model file
//             file_put_contents($modelFilePath, $modelContent);
//           //  dd($modelContent,$fillable,$fillablePosition,$indentation);
//         }

// }

private function insertFillableIntoModel($modelName, $columns)
{
    // Define the model file path
    $modelFilePath = app_path('Models/' . $modelName . '.php');

    // Read the current content of the model file
    $modelContent = file_get_contents($modelFilePath);

    // Extract column names from the columns string
    $columns = collect(explode(',', $columns))->map(function ($column) {
        return explode(':', $column)[0];
    })->toArray();

    // Define the fillable array as a string
    $fillableArray = "'" . implode("',\n        '", $columns) . "',";

    // Check if the fillable variable already exists in the model content
    if (strpos($modelContent, 'protected $fillable') !== false) {
        // Use a regular expression to find and update the fillable array
        $modelContent = preg_replace(
            '/(protected \$fillable\s*=\s*\[\s*)([^;]*?)(\s*\];)/s',
            '$1' . $fillableArray . '$3',
            $modelContent
        );
    } else {
        // Add the fillable variable to the model content
        $modelContent = preg_replace(
            '/(class\s+' . $modelName . '\s+extends\s+Model\s*\{)/',
            "$1\n\n    protected \$fillable = [\n        " . $fillableArray . "\n    ];",
            $modelContent
        );
    }

    // Save the modified content back to the model file
    file_put_contents($modelFilePath, $modelContent);
}
}
