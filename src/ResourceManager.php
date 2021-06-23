<?php

namespace EvansKim\Resourcery;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

/**
 * Class ResourceManager
 *
 * @property string $title
 * @property string $label
 * @property string $class
 * @property string $table_name
 * @property bool $uses
 * @property-read string $class_file_path
 * @see ResourceManager::getClassFilePathAttribute()
 * @property-read string $base_class
 * @see ResourceManager::getBaseClassAttribute()
 * @property-read Role[]|Collection roles
 * @property-read ResourceAction[]|Collection actions
 * @property string snake
 * @see ResourceManager::getSnakeAttribute()
 * @package EvansKim\Resourcery
 *
 * @method static Builder|ResourceManager where(...$args)
 * @method static Builder search(Request $request)
 * @method static ResourceManager|null findOrFail($id)
 * @method ResourceManager|null first()
 * @method ResourceManager[]|Collection get()
 * @mixin Builder
 */
class ResourceManager extends ResourceModel
{
    use Searchable;

    public static $routed;

    protected $fields = [];
    protected $fillable = ['title', 'label', 'class', 'uses', 'table_name'];
    protected $casts = ['uses' => 'bool'];

    protected static function boot()
    {
        parent::boot();
        ResourceManager::created(
            function (ResourceManager $manager) {
                // 사전에 명시된 기본 테이블은 생성하지 않습니다.
                if ($manager->isDefaultResource()) {
                    $manager->createDefaultActions();
                    $manager->createVuePage();
                    return false;
                }
                $manager->createModelFile();
                $manager->createController();
                $manager->createDefaultActions();
                $manager->createVuePage();
                $manager->createFactory();

                ResourceManager::createPageRouteJson();
            }
        );

        ResourceManager::updated(
            function (ResourceManager $manager) {
                $manager->flushCache();
                ResourceManager::createPageRouteJson();
            }
        );

        ResourceManager::deleted(
            function (ResourceManager $manager) {
                $manager->flushCache();
                ResourceManager::createPageRouteJson();
            }
        );
    }

    /**
     * @param array $array
     * @return ResourceManager
     */
    public static function create(array $array)
    {
        if (!Arr::get($array, 'table_name')) {
            $plural = Str::plural($array['title']);
            $array['table_name'] = str_replace('-', '_', $plural);
        }
        $studly = Str::studly($array['title']);
        if (!Arr::get($array, 'class')) {
            $array['class'] = config('resourcery.model_namespace') . "\\" . $studly;
        }
        if(!Arr::get($array, 'label')){
            $array['label'] = $studly;
        }

        $model = new ResourceManager($array);
        $model->save();

        return $model;
    }

    public function isDefaultResource()
    {
        return in_array($this->title, ['user', 'role', 'resource-manager', 'resource-action']);
    }

    /**
     * @param $title
     * @return ResourceModel
     */
    public static function getTargetModel($title)
    {
        return self::where('title', $title)->first()->getResourceModel();
    }

    public static function getUpdateRules($id)
    {
        $createRules = self::getCreateRules();
        $createRules['title'][2] = 'unique:resource_managers,title,' . $id;
        return $createRules;
    }

    public static function getCreateRules()
    {
        return [
            'title' => [
                'required',
                ValidationRules::snake(),
                'unique:resource_managers'
            ],
            'label' => 'required|string',
            'table_name' => [
                'nullable',
                'alpha_dash',
                ValidationRules::snakeUnderBar(),
                ValidationRules::hasTable(),
            ],
            'uses' => 'required|bool'
        ];
    }

    public static function getByTitle($title, $action = null)
    {
        return ResourceManager::where('title', $title)->with(
            [
                'actions',
                'roles'
            ]
        )->first();
    }

    public function createController()
    {
        $content = $this->replaceDummy(file_get_contents(__DIR__ . "/../stubs/controller.stub"));

        $this->makeDirByConfig('controller_dir');
        $app_path = $this->getControllerPath();

        if (!file_exists($app_path)) {
            file_put_contents($app_path, $content);
        }
    }

    public function createModelFile()
    {
        if (class_exists($this->class)) {
            return false;
        }
        $content = $this->replaceDummy(file_get_contents(__DIR__ . "/../stubs/model.stub"));

        $primaryField = $this->getPrimaryName();

        $content = preg_replace("/DummyPrimary/", $primaryField, $content);
        $content = preg_replace("/DummyTable/", $this->table_name, $content);
        $rules = $this->getCompileValidated($this->table_name);
        $content = preg_replace("/DummyRules/", $rules, $content);

        $this->makeDirByConfig('model_dir');

        if (!file_exists($this->class_file_path)) {
            file_put_contents($this->class_file_path, $content);
        }
    }

    public function deleteModelFile()
    {
        if (!class_exists($this->class)) {
            return false;
        }

        return unlink($this->class_file_path);
    }

    public function flushCache()
    {
        Cache::pull('resource.' . $this->title);
    }

    public function actions()
    {
        return $this->hasMany(ResourceAction::class, 'resource_id', 'id');
    }

    public function roles()
    {
        return $this->morphToMany(Role::class, 'player');
    }

    /**
     * @return Model|mixed
     */
    public function getBaseClassAttribute()
    {
        return class_basename($this->getClass());
    }

    public function getClassFilePathAttribute()
    {
        return Config::get('resourcery.model_dir') . '/' . $this->base_class . '.php';
    }

    /**
     * @return ResourceModel mixed
     */
    public function getResourceModel()
    {
        return new $this->class;
    }

    public function getStudlyAttribute()
    {
        return Str::studly($this->title);
    }

    public function getSnakeAttribute()
    {
        return Str::snake($this->studly, '-');
    }

    public function getDefaultTableAttribute()
    {
        return Str::plural(Str::snake($this->studly, '_'));
    }

    public function createFactory()
    {
        $content = file_get_contents(__DIR__ . "/../stubs/factory.stub");
        $content = str_replace("DummyClass", $this->studly, $content);
        $content = str_replace('DummyResourceModelClass', $this->class, $content);
        $rules = [];
        foreach ($this->getCompiledFields() as $field => $type) {
            switch ($type) {
                case 'primary':
                    $case = '';
                    break;
                case 'string':
                    $case = 'title';
                    break;
                case 'datetime':
                    $case = 'dateTime';
                    break;
                case 'date':
                    $case = 'date';
                    break;
                case 'boolean':
                    $case = 'boolean';
                    break;
                case 'number':
                    $case = 'numberBetween(100, 1000000)';
                    break;
                case is_array($type) && $type[0] === 'select':
                    $case = 'randomElement(' . $this->arrayToString($type[1]) . ')';
                    break;
                default:
                    continue;
                    break;
            }
            if (!$case) {
                continue;
            }
            $rules[$field] = '"$faker->' . $case . "\"";
        }
        $arrayToString = $this->arrayToString($rules, true);
        $arrayToString = str_replace('\'"', '', $arrayToString);
        $arrayToString = str_replace('"\'', '', $arrayToString);
        $content = preg_replace("/DummyRules/", $arrayToString, $content);

        $factory_path = config('resourcery.factory_dir') . '/' . $this->studly . 'Factory.php';
        if (!file_exists($factory_path)) {
            file_put_contents($factory_path, $content);
        }
    }

    public function createVuePage()
    {
        $page = file_get_contents(__DIR__ . "/../stubs/page.stub");
        $page = str_replace('DummyLabel', $this->label, $page);
        $page = str_replace('DummyTitlePage', $this->studly . "Page", $page);
        $page = str_replace('DummyUri', $this->snake, $page);
        $page = str_replace('DummyFullUri', config('resourcery.page_route') . '/' . $this->snake, $page);
        $page = str_replace('DummyPrimaryKey', $this->getPrimaryName(), $page);
        $page = str_replace('DummyForm', json_encode($this->fields), $page);

        $resource_path = resource_path('js/pages/' . $this->studly . "Page.vue");
        if (!file_exists($resource_path)) {
            file_put_contents($resource_path, $page);
        }
    }

    public function createDefaultActions()
    {
        $actions = config("resourcery.default_actions");
        // 리소스 생성시 기본 액션을 추가합니다.
        foreach ($actions as $action => $type) {
            $act = new ResourceAction(
                [
                    'function_name' => $action,
                    'auth_type' => $type,
                    'resource_id' => $this->id
                ]
            );
            $act->method = $act->getMethod();
            $act->save();
        }
    }

    public function replaceDummy($content)
    {
        $content = str_replace("DummyControllerNameSpace", config('resourcery.controller_namespace'), $content);
        $content = str_replace("DummyModelNameSpace", config('resourcery.model_namespace'), $content);
        $content = str_replace("DummyClass", $this->studly, $content);
        $content = str_replace('DummyResourceModelClass', $this->class, $content);
        $content = str_replace("DummyName", $this->snake, $content);
        $content = str_replace('ResourceId', $this->id, $content);

        return $content;
    }

    protected function getCompileValidated($table = null)
    {
        $columns = DB::select('show columns from ' . $table);

        foreach ($columns as $column) {
            if ($column->Key === 'PRI') {
                continue;
            }
            if ($validate = $this->compileValidation($column)) {
                $fields[$column->Field] = $validate;
            }
        }
        return $this->arrayToString($fields, true);
    }

    /**
     * @param $table
     * @return mixed
     */
    public function getCompiledFields()
    {
        if (count($this->fields)) {
            return $this->fields;
        }

        $columns = DB::select('show columns from ' . $this->table_name);

        foreach ($columns as $column) {
            list($type, $length) = $this->compileColumn($column);
            if ($column->Key === 'PRI') {
                continue;
            }
            if (in_array($type, ['varchar', 'char'])) {
                $this->fields[$column->Field] = "string";
                if ($length == 1) {
                    $this->fields[$column->Field] = "boolean";
                }
                continue;
            }
            if (in_array($type, ['text', 'longtext', 'tinytext'])) {
                $this->fields[$column->Field] = "text";
                continue;
            }
            if (in_array($type, ['datetime', 'date', 'timestamp'])) {
                $this->fields[$column->Field] = "datetime";
                if ($type === "date") {
                    $this->fields[$column->Field] = "date";
                }
                continue;
            }
            if (in_array($type, ['enum', 'set'])) {
                $options = explode(",", str_replace("'", "", $length));
                $this->fields[$column->Field] = ["select", $options];
                continue;
            }
            if (in_array($type, ['tinyint']) && $length == 1) {
                $this->fields[$column->Field] = "boolean";
                continue;
            }
            if (in_array($type, ['int', 'double', 'float'])) {
                $this->fields[$column->Field] = "number";
                continue;
            }
        }
        return $this->fields;
    }

    protected function compileValidation($column)
    {
        list($type, $length) = $this->compileColumn($column);

        $validates = [];
        if ($column->Null === 'NO' && is_null($column->Default)) {
            $validates[] = 'required';
        } else {
            $validates[] = 'nullable';
        }

        $validates = $this->getStringField($type, $validates, $length);
        $validates = $this->getSelectField($type, $validates, $length);
        $validates = $this->getNumberField($type, $validates);
        $validates = $this->getBooleanField($type, $length, $validates);
        $validates = $this->getDateField($type, $validates);
        return array_map(
            function ($item) {
                if (!is_null($item)) {
                    return $item;
                }
            },
            $validates
        );
    }

    /**
     * @param $column
     * @return array
     */
    protected function compileColumn($column)
    {
        $pattern = "/(\w*)\((.*)\)/";
        $matches = [];
        preg_match($pattern, $column->Type, $matches);
        if (!isset($matches[1])) {
            $type = $column->Type;
            $length = null;
        } else {
            $type = $matches[1] ?: null;
            $length = $matches[2] ?: null;
        }
        return array($type, $length);
    }

    /**
     * @param $type
     * @param $validates
     * @param $length
     * @return array
     */
    protected function getStringField($type, $validates, $length)
    {
        if (in_array($type, ['varchar', 'char'])) {
            $validates[] = 'string';
            if ((int)$length > 0) {
                $validates[] = 'max:' . $length;
            }
        }
        return $validates;
    }

    /**
     * @param $type
     * @param $validates
     * @param $length
     * @return array
     */
    protected function getSelectField($type, $validates, $length)
    {
        if (in_array($type, ['enum', 'set'])) {
            $validates[] = 'string';
            $options = str_replace("'", "", $length);

            $validates[] = 'in:' . $options;
        }
        return $validates;
    }

    /**
     * @param $type
     * @param $validates
     * @return array
     */
    protected function getNumberField($type, $validates)
    {
        if (in_array($type, ['int', 'float', 'double'])) {
            $validates[] = 'numeric';
        }
        return $validates;
    }

    /**
     * @param $type
     * @param $length
     * @param $validates
     * @return array
     */
    protected function getBooleanField($type, $length, $validates)
    {
        if (in_array($type, ['tinyint'])) {
            if ((int)$length === 1) {
                $validates[] = 'boolean';
            } else {
                $validates[] = 'numeric';
            }
        }
        return $validates;
    }

    /**
     * @param $type
     * @param $validates
     * @return array
     */
    protected function getDateField($type, $validates)
    {
        if (in_array($type, ['datetime', 'date'])) {
            $validates[] = 'date';
        }
        return $validates;
    }

    private function getControllerPath()
    {
        return $this->getConfig()['controller_dir'] . '/' . $this->base_class . "Controller.php";
    }


    /**
     * @return array|string
     */
    private function getConfig($var = null)
    {
        if (is_null($var)) {
            return Config::get('resourcery');
        }
        return Config::get('resourcery.' . $var);
    }

    private function makeDirByConfig($var)
    {
        $path = $this->getConfig($var);
        if (!is_dir($path)) {
            File::makeDirectory($path);
        }
    }

    /**
     * @return string
     */
    public function getClass()
    {
        if ($this->class) {
            return $this->class;
        }
        return Config::get('resourcery.model_namespace') . "\\" . Str::studly($this->title);
    }

    /**
     * @return int|string
     */
    protected function getPrimaryName()
    {
        $result = DB::select("SHOW KEYS FROM {$this->table_name} WHERE Key_name = 'PRIMARY'");
        return $result[0]->Column_name;
    }

    /**
     * @param $fields
     * @return string|string[]|null
     */
    protected function arrayToString($fields, $white_space = false)
    {
        $fields = var_export($fields, true);
        $fields = preg_replace("/\d.=>./", "", $fields);
        if ($white_space) {
            $fields = str_replace("  ", "    ", $fields);
        }
        return $fields;
    }

    public static function createPageRouteJson()
    {
        $json = self::all()->map(
            function (ResourceManager $manager) {
                return [
                    $manager->studly . 'Page' => config('resourcery.page_route') . '/' . $manager->snake,
                    'api' => config('resourcery.base_uri') . '/' . $manager->snake,
                    'manager' => $manager->toArray()
                ];
            }
        )->toJson(JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);

        file_put_contents(resource_path('/views/page_route.json'), $json);
    }
    public function routesToString()
    {
        $gates = [];
        $routes = [];
        $this->actions->map( function(ResourceAction $act)use(&$gates, &$routes){
            $actionName = Str::snake($act->function_name, '-');
            $ability = $this->title . '.' . $actionName;
            $gates[] = "Gate::define('{$ability}', '{$act->getAuthClass()}@validate' );";

            $method = $act->getMethod();
            $uri = $act->getRouteUriRule($this->title);
            $studly = Str::studly($this->title);
            $namespace = config('resourcery.controller_namespace');
            $controller = "{$studly}Controller";
            $strController = $namespace .'\\'. $controller . "@" . $act->function_name;
            $routes[] = "Route::{$method}('{$uri}', '{$strController}')->middleware('able:{$ability}')->name('{$this->title}');";
        });
        $content = "\n";

        $content .= implode("\n", $gates) . "\n";
        $content .= implode("\n", $routes);

        return $content;
    }
}
