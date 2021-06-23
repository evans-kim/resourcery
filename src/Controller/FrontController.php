<?php


namespace EvansKim\Resourcery\Controller;


use App\Http\Controllers\Controller;
use EvansKim\Resourcery\ResourceAction;
use EvansKim\Resourcery\ResourceManager;
use EvansKim\Resourcery\ResourceRouter;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\Types\Context;

class FrontController extends Controller
{
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
    // 히든 렌더링을 위한 메소드
    public function index(Request $request, $id = null)
    {
        $controller = config('resourcery.controller_namespace') . '\\' . $this->manager->base_class . "Controller";
        if (!class_exists($controller)) {
            return abort(404, 'Not Found Resourcery Controller');
        }
        $data = (new $controller)->{$this->action->function_name}($request, $id);

        return view('welcome',
                    [
                        'data' => $data->toArray(),
                        'manager' => $this->manager->toArray(),
                        'action' => $this->action->toArray()
                    ]
        );
    }
}
