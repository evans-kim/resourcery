<?php


namespace EvansKim\Resourcery\Controller;


use App\Http\Controllers\Controller;
use EvansKim\Resourcery\ResourceAction;
use EvansKim\Resourcery\ResourceManager;
use EvansKim\Resourcery\ValidationRules;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResourceActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @param Request $request
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        $this->validate(
            $request,
            [
                'function_name' => 'nullable|string',
                'auth_type' => 'nullable|string',
                'resource_id' => 'nullable|numeric',
                'per_page' => 'nullable|numeric|max:100|min:10'
            ]
        );

        $perPage = $request->perPage ?? 15;

        return ResourceAction::search($request)->with(['roles'])->paginate($perPage)->toArray();
    }

    /**
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function show(Request $request, $id)
    {
        return ResourceAction::findOrFail($id)->toArray();
    }

    /**
     * @param Request $request
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $array = $request->all();
        $this->createValidate($request);

        return response(['message' => '생성되었습니다.', 'model' => ResourceAction::create($array)->toArray()], 201);
    }

    /**
     * @param Request $request
     * @throws ValidationException
     */
    public function update(Request $request, $id)
    {
        $this->updateValidate($request, $id);

        $manager = ResourceAction::findOrFail($id);

        $manager->fill($request->all());
        $manager->roles()->sync( collect($request->roles)->map(function($role){
            return $role['id'];
        }));

        if ($manager->save()) {
            return response(['message' => '수정되었습니다.', 'model' => $manager->toArray()], 200);
        }

        return response(['message' => '수정하지 못했습니다.'], 406);
    }

    /**
     * @param Request $request
     * @param ResourceManager $manager
     * @throws Exception
     */
    public function destroy(Request $request, $id)
    {
        $manager = ResourceManager::findOrFail($id);

        $manager->delete();

        return response(['message' => '삭제되었습니다.'], 202);
    }

    /**
     * @param Request $request
     * @param $id
     * @throws ValidationException
     */
    protected function updateValidate(Request $request, $id): void
    {
        $updateRoles = $this->getUpdateRule($id);

        $this->validate($request, $updateRoles);
    }

    /**
     * @param Request $request
     * @throws ValidationException
     */
    protected function createValidate(Request $request)
    {
        $this->validate($request, $this->getRules());
    }

    /**
     * @return array
     */
    protected function getRules()
    {
        return [
            'resource_id' => [
                'required',
                'numeric'
            ],
            'method' => [
                'required','string','max:64'
            ],
            'function_name' => [
                'nullable',
                ValidationRules::studly()
            ],
            'auth_type' =>[
                'required',
                'string'
            ],
            'roles' => [
                'nullable',
                'array'
            ]
        ];
    }

    /**
     * @param $id
     * @return array
     */
    protected function getUpdateRule()
    {
        return $this->getRules();
    }
}
