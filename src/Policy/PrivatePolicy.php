<?php


namespace EvansKim\Resourcery\Policy;


use EvansKim\Resourcery\Ownerable;
use EvansKim\Resourcery\ResourceModel;

class PrivatePolicy implements ResourcePolicyContract
{
    public function validate(Ownerable $user = null, ResourceModel $model = null)
    {
        if (!$user) {
            return false;
        }
        if (!$model) {
            return false;
        }

        if ($user->isOwnerOf($model->getOwnerId())) {
            return true;
        }

        return $user->isAdmin();
    }

    public function getInvalidMessage()
    {
        return '작성자 또는 관리자만 접근할 수 있습니다.';
    }
}
