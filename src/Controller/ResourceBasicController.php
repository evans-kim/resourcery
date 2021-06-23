<?php

namespace EvansKim\Resourcery\Controller;


use EvansKim\Resourcery\ResourceAction;
use EvansKim\Resourcery\ResourceManager;
use EvansKim\Resourcery\ResourceModel;
use EvansKim\Resourcery\ResourceRouter;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class ResourceBasicController extends Controller
{
    /**
     * @var ResourceModel
     */
    protected $model;
    /**
     * @var ResourceManager
     */
    private $manager;
    /**
     * @var ResourceAction
     */
    private $action;


    public function __construct()
    {
        $this->manager = ResourceRouter::$routed_manager;
        $this->action = ResourceRouter::$routed_action;
    }

    /**
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function index(Request $request)
    {
        $perPage = 15;
        if ($request->has('perPage')) {
            $perPage = $request->perPage;
        }
        return $this->getModel()->search($request)->paginate($perPage);
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response
     * @throws ValidationException
     */
    public function store(Request $request)
    {

        $model = $this->getModel()->create($this->validated($request));

        return response(['message'=>'Success', 'model'=>$model->toArray()], 201);
    }

    /**
     * @param Request $request
     * @param $id
     * @return ResourceModel|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|BelongsTo|BelongsTo[]|HasOneOrMany|HasOneOrMany[]
     */
    public function show(Request $request)
    {
        $id = $this->getIdParameter();

        $resourceModel = $this->getModel()->findOrFail($id);

        return $resourceModel;
    }

    /**
     * @param Request $request
     * @param $id
     * @return ResponseFactory|Response
     * @throws ValidationException
     */
    public function update(Request $request)
    {
        $id = $this->getIdParameter();

        $resourceModel = $this->getModel()->findOrFail($id);
        $resourceModel->fill(
            $this->validated($request)
        )->save();

        return response(['message'=>'Success', 'model'=>$resourceModel->toArray()], 202);
    }

    /**
     * @param Request $request
     * @param $id
     * @return ResponseFactory|Response
     * @throws Exception
     */
    public function destroy(Request $request)
    {
        $id = $this->getIdParameter();
        $model = $this->getModel()->findOrFail($id);
        $model->delete();

        $toArray = $model->toArray();
        return response(['message'=>'Success', 'model'=> $toArray], 204);
    }

    /**
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    protected function validated(Request $request)
    {
        $instance = $this->manager->getResourceModel();
        if (!method_exists($instance, 'rules')) {
            return $request->all();
        }
        $rules = $instance->rules();
        if (!count($rules)) {
            return $request->all();
        }

        if (method_exists($instance, 'getMergedRequest')) {
            $request = $instance->getMergedRequest();
        }

        $this->validate($request, $rules);

        return $request->only(array_keys($rules));
    }

    /**
     * @return ResourceModel|HasOneOrMany|BelongsTo
     */
    protected function getModel()
    {
        if($this->model){
            return $this->model;
        }
        // /resource or /resource/{id}
        if( !$this->action->model_id ){
            $this->model = $this->manager->getResourceModel();
            return $this->model;
        }
        // /{parent}/{parent_id}/resource or /{parent}/{parent_id}/resource/{id}
        $relation_name = Arr::get( Route::current()->parameters, $this->action->model_id );
        $parent = ResourceManager::getTargetModel($relation_name);
        $parent_id = Arr::get(Route::current()->parameters, $this->action->model_id . '_id');

        if($parent_id){
            $parent = $parent->findOrFail( $parent_id );
        }

        $this->model = $parent->{$this->action->model_id}();

        return $this->model;


    }

    /**
     * @return object|string
     */
    protected function getIdParameter()
    {
        return Route::current()->parameter(config('resourcery.resource_id_parameter_name'));
    }
}
