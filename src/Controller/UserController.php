<?php


namespace EvansKim\Resourcery\Controller;

use App\Http\Controllers\Controller;
use EvansKim\Resourcery\Owner;
use EvansKim\Resourcery\ResourceManager;
use EvansKim\Resourcery\ValidationRules;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
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
                'name' => 'nullable|string',
                'email' => 'nullable|string',
                'per_page' => 'nullable|numeric|max:100|min:10'
            ]
        );

        $perPage = $request->perPage ?? 10;

        return Owner::search($request)->with(['roles'])->paginate($perPage)->toArray();
    }

    /**
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function show(Request $request, $id)
    {
        return Owner::findOrFail($id)->toArray();
    }

    /**
     * @param Request $request
     * @throws ValidationException
     */
    public function store(Request $request)
    {

        $array = $request->all();

        return response(['message' => '생성되었습니다.', 'model' => Owner::create($array)->toArray()], 201);
    }

    /**
     * @param Request $request
     * @throws ValidationException
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, $this->getRules());
        $manager = Owner::findOrFail($id);

        $manager->fill($request->all());

        $manager->roles()->sync( collect($request->roles)->pluck('id') );

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
     * @return array
     */
    protected function getRules()
    {
        $rules = Owner::getRules();
        $rules['name'][0] = 'sometimes';
        $rules['email'][0] = 'sometimes';
        $rules['password'][0] = 'sometimes';
        $rules['roles']= ['nullable','array'];

        return $rules;
    }
}
