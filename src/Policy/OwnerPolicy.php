<?php


namespace EvansKim\Resourcery\Policy;


use EvansKim\Resourcery\Ownerable;
use EvansKim\Resourcery\ResourceModel;
use Illuminate\Support\Facades\Config;

class OwnerPolicy implements ResourcePolicyContract
{

    public function validate(Ownerable $user = null, ResourceModel $model = null)
    {
        return $user->isOwnerOf( $model->getOwnerId() );
    }

    public function getInvalidMessage()
    {
        return '작성자 본인만 가능합니다.';
    }
}
