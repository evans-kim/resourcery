<?php


namespace EvansKim\Resourcery\Policy;


use EvansKim\Resourcery\Ownerable;
use EvansKim\Resourcery\ResourceModel;

class AdminPolicy implements ResourcePolicyContract
{
    public function validate(Ownerable $user = null , ResourceModel $model = null )
    {
        if (!$user)
            return false;

        return $user->isAdmin();
    }

    public function getInvalidMessage()
    {
        return '관리자만 접근할 수 있습니다.';
    }
}
