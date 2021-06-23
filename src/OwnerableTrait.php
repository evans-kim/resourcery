<?php


namespace EvansKim\Resourcery;


use Illuminate\Support\Str;

trait OwnerableTrait
{

    public function refreshToken()
    {
        $this->api_token = $this->shakeToken();
        $this->save();
        cookie('api_token', $this->api_token, 60 * 24);
    }

    public function shakeToken()
    {
        return Str::random(80);
    }

    public function roles()
    {
        return $this->morphToMany(Role::class, 'player');
    }

    /**
     * @inheritDoc
     */
    public function isOwnerOf($model_user_id)
    {
        return $model_user_id === $this->id;
    }

    /**
     * 이 유저가 관리자인 조건을 명시하세요.
     * @inheritDoc
     */
    public function isAdmin()
    {
        return $this->roles->filter(function (Role $role) {
                return $role->id === 1;
            })->count() > 0;
    }

    public function setAsAdmin()
    {
        $this->roles()->attach(1);
    }

    public function unsetAsAdmin()
    {
        $this->roles()->detach(1);
    }

    public function hasRole(Role $role)
    {
        return $this->roles()->where('id', $role->id)->count() > 0;
    }

    public function hasRoles(array $ids)
    {
        return $this->roles->filter(function (Role $role) use ($ids) {
                return in_array($role->id, $ids);
            })->count() > 0;
    }

    /**
     * @return mixed
     */
    public function getPrimaryId()
    {
        return $this->id;
    }
}
