<?php

namespace EvansKim\Resourcery;

use Carbon\Carbon;

use EvansKim\Resourcery\Exception\NotAuthorisedResourceException;
use EvansKim\Resourcery\Exception\NotAvailableResourcePolicyException;
use EvansKim\Resourcery\Policy\ResourcePolicyContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * App\ResourceAction
 *
 * @property int $id
 * @property int $resource_id
 * @property string|null $method
 * @property string $function_name
 * @property string|null $model_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $auth_type
 * @property-read ResourceManager $manager
 * @property-read Collection|Role[] $roles
 * @mixin Model
 * @method static ResourceAction create(array $array)
 * @method static Builder search(Request $request)
 * @method static ResourceAction|null findOrFail($id)
 */
class ResourceAction extends Model
{
    use Searchable;
    public function getSearchable()
    {
        return [
            'resource_id'
        ];
    }

    protected $fillable = ['function_name', 'auth_type', 'resource_id', 'method', 'model_id'];

    public function manager()
    {
        return $this->belongsTo(ResourceManager::class, 'resource_id', 'id');
    }
    public function roles()
    {
        return $this->morphToMany(Role::class, 'player');
    }
    public function getAuthClass()
    {
        return 'EvansKim\\Resourcery\\Policy\\'.Str::studly( Str::ucfirst($this->auth_type) )."Policy";
    }
    /**
     * @return ResourcePolicyContract
     * @throws NotAvailableResourcePolicyException
     */
    public function getPolicy()
    {
        $authType = $this->getAuthClass();
        if(!class_exists($authType)){
            throw new NotAvailableResourcePolicyException();
        }
        return new $authType;
    }
    public function getIdParamName()
    {
        if ($this->model_id){
            return $this->model_id;
        }

        if(in_array($this->function_name, ['show','update','destroy'])){
            return 'id';
        }

        return null;
    }
    public function getRouteUriRule($resource_title, $prefix=null)
    {
        if(!$prefix){
            $prefix = config('resourcery.base_uri');
        }
        $uri = $prefix .'/'. $resource_title;

        if($this->model_id){
            $model = strtolower($this->model_id);
            $uri = sprintf("%s/{%s}/{%s}/%s", $prefix, $model, $model.'_id', $resource_title);
        }
        if(in_array($this->function_name, ['show','update','destroy'])){
            return $uri.'/{'.config('resourcery.resource_id_parameter_name').'}';
        }else if(in_array($this->function_name, ['index','store'])){
            return $uri;
        }

        return $uri . '/@' . Str::snake($this->function_name, '-');
    }
    public function getMethod()
    {
        if($this->method){
            return strtolower($this->method);
        }
        switch ($this->function_name) {
            case 'index':
                return 'get';
                break;
            case 'show':
                return 'get';
                break;
            case 'store':
                return 'post';
                break;
            case 'update':
                return 'put';
                break;
            case 'destroy':
                return 'delete';
                break;
            default :
                return 'get';
                break;
        }
    }
}
