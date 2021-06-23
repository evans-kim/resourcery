<?php


namespace EvansKim\Resourcery\Middleware;


use Closure;
use EvansKim\Resourcery\Exception\NotAuthorisedResourceException;
use EvansKim\Resourcery\Ownerable;
use EvansKim\Resourcery\ResourceAction;
use EvansKim\Resourcery\ResourceManager;
use EvansKim\Resourcery\ResourceModel;
use EvansKim\Resourcery\ResourceRouter;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AuthorizeResource
{
    /**
     * @var ResourceManager
     */
    protected $manager;
    /**
     * @var ResourceAction
     */
    protected $action;
    // 외부접근시 일부 리소스만 접근 가능하도록 합니다.
    protected $exceptIpCheck = [];
    // 접근 가능한 아이피 주소
    protected $whiteIps = [];
    protected $superAdmin = '';
    private $guard = '';

    public function __construct(  )
    {
        $this->exceptIpCheck = config('resourcery.except_ip_check_resource');
        $this->whiteIps = config('resourcery.white_ip');
        $this->superAdmin = config('resourcery.super_admin_id');
        $this->guard = config('resourcery.auth');
        $this->manager = ResourceRouter::$routed_manager;
        $this->action = ResourceRouter::$routed_action;
    }

    /**
     * @param $request
     * @param Closure $next
     * @param $ability
     * @return mixed
     * @throws NotAuthorisedResourceException
     */
    public function handle($request, Closure $next, $ability = null)
    {
        /**
         * @var $request Request
         */


        if (!$this->action) {
            abort(404, "찾을 수 없는 리소스 요청 입니다.");
        }

        if ($this->before()) {
            return $next($request);
        }
        $call = $this->getGateAbility($ability);
        /**
         * @var Closure $call
         */

        $user = $this->getUser($request);

        $model = $this->getModel();

        if (!$call($user, $model)) {
            abort(403, 'Not Authorized Resource Access');
        }

        return $next($request);
    }

    /**
     * Owner 와 ResourceModel 의 역할 관계 여부를 확인합니다.
     * @return bool
     * @throws NotAuthorisedResourceException
     */
    protected function before()
    {

        if ($this->action->auth_type === 'public') {
            return true;
        }

        if ($this->action->auth_type === 'ban') {
            throw new NotAuthorisedResourceException(403,'사용금지된 기능입니다.');
        }
        // Owner
        $user = null;

        if ($this->getAuth()->check()) {
            $user = $this->getAuth()->user();
        }
        // 승인된 유저인지 확인합니다.
        if (!$user) {
            return false;
        }
        /**
         * @var $user Ownerable
         */
        if (!is_null($user) && $this->isSuperAdmin($user)) {
            return true;
        }
        // 유저가 어떤 역할도 없다면
        if (!$user->roles->count()) {
            return false;
        }
        // 이 리소스 매니저에 소속된 역할이 없다면
        if (!$this->manager->roles->count() && !$this->action->roles->count()) {
            return false;
        }

        $ids = $this->manager->roles->pluck('id')->toArray();
        // 리소스 자체 전체 권한 있는 경우
        if ($user->hasRoles($ids)) {
            return true;
        }

        // 일부 액션에 권한이 있는 경우
        return $user->hasRoles($this->action->roles->pluck('id')->toArray());

    }

    /**
     * @param $ability
     * @return ResourceManager|Builder|Model
     */
    protected function getResourceManager($ability)
    {
        $arr = explode('.', $ability);

        $title = $arr[0];
        $action = $arr[1];
        try {
            $manager = ResourceManager::where('title', '=', $title)->with([
                'actions' => function ($query) use ($action) {
                    $query->where('function_name', '=', lcfirst(Str::studly($action)));
                },
                'roles'
            ])->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            abort(404, "Model Manager Not Found.");
        }

        return $manager;
    }

    /**
     * @return bool
     */
    protected function isExceptIpCheckResource()
    {
        return in_array($this->manager->title, $this->exceptIpCheck);
    }

    /**
     * @param $request
     * @return bool
     */
    protected function isWhiteIpAddress($request)
    {
        return in_array($request->ip(), $this->whiteIps);
    }

    /**
     * @param Authenticatable|null $user
     * @return bool
     */
    protected function isSuperAdmin(Ownerable $user)
    {
        return $user->getPrimaryId() === $this->superAdmin;
    }

    /**
     * @return null|ResourceModel
     */
    private function getModel()
    {
        $model = new $this->manager->class;
        $route = Route::current();
        $paramValue = $route->parameter(config('resourcery.resource_id_parameter_name'));
        if($paramValue){
            $model = $model->find($paramValue);
        }
        return $model;
    }

    /**
     * @param $ability
     * @return mixed
     */
    private function getGateAbility($ability)
    {
        return Gate::abilities()[$ability];
    }

    /**
     * @param $request
     * @return Authenticatable|null
     */
    private function getUser($request)
    {
        $user = $this->getAuth()->user();
        if ($user) {
            if (!$this->isExceptIpCheckResource() && !$this->isWhiteIpAddress($request) && $this->isSuperAdmin($user)) {
                $this->getAuth()->logout();
                abort(402, '외부에서 접근은 제한되어 있습니다.');
            }
        }
        return $user;
    }

    /**
     * @return Factory|Guard|StatefulGuard
     */
    protected function getAuth()
    {
        return auth($this->guard);
    }
}
