<?php


namespace EvansKim\Resourcery;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Interface Ownerable
 * @property $id
 * @package EvansKim\Resourcery
 */
interface Ownerable
{

    public function refreshToken();

    public function shakeToken();
    /**
     * @param $model_user_id
     * @return bool
     */
    public function isOwnerOf($model_user_id);

    /**
     * @return bool
     */
    public function isAdmin();

    /**
     * @return Role|Role[]|Builder|HasMany
     */
    public function roles();

    /**
     * @param Role $role
     * @return bool
     */
    public function hasRole(Role $role);

    /**
     * @param array $ids
     * @return bool
     */
    public function hasRoles(array $ids);

    /**
     * @return mixed
     */
    public function getPrimaryId();

    /**
     * 이 사용자를 관리자로 지정합니다.
     * @return void
     */
    public function setAsAdmin();

}
