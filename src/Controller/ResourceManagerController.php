<?php


namespace EvansKim\Resourcery\Controller;


use App\Http\Controllers\Controller;
use EvansKim\Resourcery\ResourceManager;
use EvansKim\Resourcery\ValidationRules;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResourceManagerController extends Controller
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
                'title' => 'nullable|string',
                'label' => 'nullable|string',
                'uses' => 'nullable|bool',
                'per_page' => 'nullable|numeric|max:100|min:10'
            ]
        );

        $perPage = $request->perPage ?? 15;

        return ResourceManager::search($request)->with(['actions'=>function($query){
            $query->with('roles');
        }])->paginate($perPage)->toArray();
    }

    /**
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function show(Request $request, $id)
    {
        return ResourceManager::findOrFail($id)->toArray();
    }

    /**
     * @param Request $request
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $this->createValidate($request);

        $array = $request->all();

        return response(['message' => '생성되었습니다.', 'model' => ResourceManager::create($array)->toArray()], 201);
    }

    /**
     * @param Request $request
     * @throws ValidationException
     */
    public function update(Request $request, $id)
    {
        $this->updateValidate($request, $id);

        $manager = ResourceManager::findOrFail($id);

        $manager->fill($request->only(['label','uses']));

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
    protected function updateValidate(Request $request, $id)
    {
        $this->validate($request, ResourceManager::getUpdateRules($id));
    }

    /**
     * @param Request $request
     * @throws ValidationException
     */
    protected function createValidate(Request $request)
    {
        $this->validate($request, ResourceManager::getCreateRules());
    }
}
