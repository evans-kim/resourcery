<?php


namespace EvansKim\Resourcery;


use EvansKim\Resourcery\Controller\FrontController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ResourceRouter
{
    /**
     * @var ResourceManager|null
     */
    public static $routed_manager;
    /**
     * @var ResourceAction|null
     */
    public static $routed_action;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var string
     */
    private $requestUri;
    /**
     * @var string
     */
    private $title;
    /**
     * @var ResourceManager
     */
    private $manager;
    private $match;
    private $prefix;
    private $guard;
    /**
     * @var array
     */
    private $prefixes;
    /**
     * @var bool
     */
    private $hasResourceIdUri;

    public function __construct($request = null)
    {
        $this->request = ($request) ? $request : request();
        $this->requestUri = $this->request->getRequestUri();
        $this->guard = Config::get('resourcery.auth');
    }
    public static function createRoutes()
    {
        $resourceRouter = new static();
        if( $resourceRouter->request->method() === 'GET' ){
            // 히든 랜더링을 위한 라우트 생성
            $prefix = config('resourcery.page_route');
            $result = $resourceRouter->createRoutesByRequest($prefix);
            if($result){
                return;
            }
        }
        $prefix = config('resourcery.base_uri');
        $resourceRouter->createRoutesByRequest($prefix);

    }

    protected function createRoutesByRequest($prefix)
    {
        $this->setPrefix($prefix);

        if (!$this->parseUri()) {
            return false;
        }
        $this->setCurrentResourceManager();

        if (!$this->manager || $this->manager->isDefaultResource()) {
            return false;
        }

        $act = $this->getActionByRequestMethod();
        if (!$act) {
            return false;
        }

        $this->createRouteByAction($act);

        static::$routed_action = $act;


        return true;
    }

    /**
     * @return ResourceAction|null
     */
    private function getActionByRequestMethod()
    {
        return $this->manager->actions->filter(
            function (ResourceAction $act) {
                $method_name = strtolower($this->request->getMethod());
                if ($this->hasResourceIdUri) {
                    // 아이디 값이 있는 uri 라면 show, update, delete 함수 중에 하나 입니다.
                    return $act->getMethod() === $method_name && in_array(
                            $act->function_name,
                            ['show', 'update', 'destroy']
                        );
                }
                return $act->getMethod() === $method_name;
            }
        )->first();
    }

    private function setCurrentResourceManager()
    {
        $this->manager = ResourceManager::getByTitle($this->title);
        static::$routed_manager = $this->manager;
        return $this->manager;
    }

    /**
     * @param string $controller
     * @param ResourceAction $act
     * @return string
     */
    private function getControllerToString(ResourceAction $act)
    {
        if( $this->isFrontRoute() ){
            return FrontController::class ."@index";
        }
        $studly = Str::studly($this->manager->title);
        $controller = config('resourcery.controller_namespace');
        if ($this->manager->isDefaultResource()) {
            $controller = "EvansKim\\Resourcery\\Controller";
        }
        $controller .= "\\{$studly}Controller";
        return $controller . "@" . $act->function_name;
    }

    private function createRouteByAction(ResourceAction $act)
    {
        $actionName = Str::snake($act->function_name, '-');
        $ability = $this->manager->title . '.' . $actionName;

        $this->defineGate($act, $ability);

        $method = $act->getMethod();
        $uri = $act->getRouteUriRule($this->manager->title, $this->prefix);
        $strController = $this->getControllerToString($act);
        if($this->isFrontRoute()){
            return Route::get($uri, $strController);
        }
        Route::{$method}($uri, $strController)->middleware('able:' . $ability)->name($ability);
    }

    /**
     * @return false|int
     */
    private function isResourceApiUri()
    {
        $this->prefixes = explode('/', $this->prefix);

        foreach ($this->prefixes as $i => $prefix) {
            if ($this->match[$i] !== $prefix) {
                return false;
            }
        }
        return true;
    }

    private function parseUri()
    {
        preg_match_all("/\/([A-Za-z0-9@_\-]+)+/i", $this->requestUri, $this->match);
        $uris = array_map(
            function ($match) {
                return str_replace('/', '', $match);
            },
            $this->match[0]
        );

        if (count($uris) === 0) {
            return false;
        }
        $this->prefixes = array_values(
            array_filter(
                explode('/', $this->prefix),
                function ($item) {
                    return $item;
                }
            )
        );
        foreach ($this->prefixes as $i => $prefix) {
            if ($uris[$i] !== $prefix) {
                return false;
            }
        }

        $countPrefix = count($this->prefixes);
        $countUris = count($uris);
        $i1 = ($countUris - $countPrefix) % 2;
        if ($i1) {
            $index = $countUris - 1;
            $this->hasResourceIdUri = false;
        } else {
            $index = $countUris - 2;
            $this->hasResourceIdUri = true;
        }
        if($index < 0){
            return false;
        }
        $this->title = $uris[$index];
        return true;
    }

    /**
     * @param $prefix
     */
    private function setPrefix($prefix)
    {
        $this->prefix = $this->trimPrefixUri($prefix);
    }

    /**
     * AuthorizeResource 미들웨어에서 호출됩니다.
     *
     * @param ResourceAction $act
     * @param string $ability
     */
    private function defineGate(ResourceAction $act, string $ability)
    {
        Gate::define(
            $ability,
            $act->getAuthClass().'@validate'
        );
    }

    protected function isFrontRoute()
    {
        return $this->prefix == $this->trimPrefixUri(config('resourcery.page_route'));
    }
    protected function isAPIRoute()
    {
        return $this->prefix == $this->trimPrefixUri(config('resourcery.base_uri'));
    }

    /**
     * @param $prefix
     * @return false|string
     */
    private function trimPrefixUri($prefix)
    {
        $prefix = trim($prefix);
        if (substr($prefix, -1) === '/') {
            $prefix = substr($prefix, 0, -1);
        }
        return $prefix;
    }

}
