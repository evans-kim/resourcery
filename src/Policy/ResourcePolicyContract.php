<?php


namespace EvansKim\Resourcery\Policy;


use EvansKim\Resourcery\Ownerable;
use EvansKim\Resourcery\ResourceModel;

interface ResourcePolicyContract
{
    /**
     * @param Ownerable $user
     * @param ResourceModel|null $model
     * @return bool
     */
    public function validate(Ownerable $user = null, ResourceModel $model = null);

    /**
     * @return string
     */
    public function getInvalidMessage();
}
