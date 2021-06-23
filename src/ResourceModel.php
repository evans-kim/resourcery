<?php

namespace EvansKim\Resourcery;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * App\Extensions\Resource\ResourceModel
 *
 * @method static Builder|static findWithRelations($id)
 * @method static Builder|static search(Request $request, $or = false)
 * @method static static|null find($id)
 * @method static static findOrFail($id)
 * @method static static|Builder whereIn($field, $values = [])
 * @method static static|Builder where($column, $valueOrOperator, $value=null)
 * @property-read ResourceManager|Model|null $resource_manager
 * @property-read Collection|Role[] roles
 *
 * @mixin Model
 */
class ResourceModel extends Model
{
    use Searchable;

    private $manager;

    public function __construct(array $attributes = [])
    {
        // 모델의 생성룰이 있다면 fillable 에 추가합니다.
        if (count($this->fillable) === 0) {
            $this->fillable = array_merge($this->fillable, array_keys($this->rules()));
        }
        parent::__construct($attributes);
    }

    /**
     * @return ResourceManager
     */
    public function getResourceManagerAttribute()
    {
        if (!$this->manager) {
            $this->manager = (new ResourceManager())->where('class', self::class)->first();
        }
        if (is_null($this->manager)) {
            abort(400, "리소스 매니저 데이터가 없습니다. 설정된 매니저 아이디를 확인하세요.");
        }
        return $this->manager;
    }

    public function roles()
    {
        return $this->morphToMany(Role::class, 'player');
    }

    public function rules()
    {
        return [];
    }

    public static function getRules()
    {
        return (new static)->rules();
    }

    public function getOwnerId()
    {
        return $this->user_id;
    }

}
