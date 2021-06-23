<?php


namespace EvansKim\Resourcery\Policy;


use EvansKim\Resourcery\Ownerable;
use EvansKim\Resourcery\ResourceModel;
use Illuminate\Support\Facades\Config;

class LoggedPolicy implements ResourcePolicyContract
{

    private $guard;

    public function __construct()
    {
        $this->guard = Config::get('resourcery.auth');
    }

    public function validate(Ownerable $user = null, ResourceModel $model = null)
    {
        return auth($this->guard)->check();
    }

    public function getInvalidMessage()
    {
        return '로그인을 하거나 유효한 토큰인지 확인해 주세요.';
    }
}
