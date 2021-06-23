<?php

namespace EvansKim\Resourcery;

use EvansKim\Resourcery\Template\OutlookFormat;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * @method static static create(array $array)
 * @method static static find(int $int)
 * @method static static findOrFail($id)
 * @property string email
 * @property string api_token
 */
class Owner extends ResourceModel implements
    Ownerable,
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use \Illuminate\Auth\Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail, Notifiable, Searchable;

    protected $table = 'users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'api_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function owner_token()
    {
        return $this->morphOne(OwnerToken::class, 'owner');
    }

    public function refreshToken()
    {
        $token = $this->shakeToken();

        $this->owner_token()->create(['token'=> $token]);

        cookie('owner_token', $token, 60 * 24);
    }

    public function shakeToken()
    {
        return Str::random(80);
    }

    public function resource_test()
    {
        return $this->hasMany(ResourceTest::class, 'user_id', 'id');
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

    public function getGroupLevel()
    {
        return 2;
    }

    public function rules()
    {
        return [
            'name' =>
                [
                    'required',
                    'string',
                    'max:255',
                ],
            'email' =>
                [
                    'required',
                    'string',
                    'max:255',
                ],
            'password' =>
                [
                    'required',
                    'string',
                    'max:255',
                ],
        ];
    }

}
