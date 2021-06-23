<?php

namespace EvansKim\Resourcery;

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * App\Role
 *
 * @property int $id
 * @property string $title
 * @property int $level
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @mixin Model
 * @method static Role findOrFail($id)
 * @method static Role create(array $array)
 */
class Role extends Model
{
    use Searchable;

    protected $fillable = ['title', 'level'];

    public function owners()
    {
        return $this->morphedByMany(Owner::class, 'player');
    }

    public function resource_actions()
    {
        return $this->morphedByMany(ResourceAction::class, 'player');
    }

    public function resource_managers()
    {
        return $this->morphedByMany(ResourceManager::class, 'player');
    }

    public static function rules()
    {
        return [
            'title' =>
                [
                    'required',
                    'string',
                    'max:255',
                ],
            'level' =>
                [
                    'nullable',
                    'numeric'
                ]
        ];
    }
}
