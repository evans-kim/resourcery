<?php


namespace EvansKim\Resourcery\Controller;

use App\Http\Controllers\Controller;
use EvansKim\Resourcery\Role;
use EvansKim\Resourcery\ResourceManager;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        $this->validate(
            $request,
            [
                'title' => 'nullable|string',
                'level' => 'nullable|numeric',
            ]
        );

        $perPage = $request->perPage ?? 5;

        return Role::search($request)->paginate($perPage)->toArray();
    }

    /**
     * @param Request $request
     * @param $id
     * @return array
     */
    public function show(Request $request, $id)
    {
        return Role::findOrFail($id)->toArray();
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $this->validate($request, $this->getRules());

        $array = $request->all();

        return response(['message' => '생성되었습니다.', 'model' => Role::create($array)->toArray()], 201);
    }

    /**
     * @param Request $request
     * @param $id
     * @return ResponseFactory|Response
     */
    public function update(Request $request, $id)
    {

        $manager = Role::findOrFail($id);

        $manager->fill($request->only(['title', 'level']));

        if ($manager->save()) {
            return response(['message' => '수정되었습니다.', 'model' => $manager->toArray()], 200);
        }

        return response(['message' => '수정하지 못했습니다.'], 406);
    }

    /**
     * @param Request $request
     * @param $id
     * @return ResponseFactory|Response
     * @throws Exception
     */
    public function destroy(Request $request, $id)
    {
        $manager = ResourceManager::findOrFail($id);

        $manager->delete();

        return response(['message' => '삭제되었습니다.'], 202);
    }

    /**
     * @return array
     */
    protected function getRules()
    {
        return Role::rules();
    }
}
