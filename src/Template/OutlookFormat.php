<?php

namespace EvansKim\Resourcery\Template;

use Illuminate\Support\Str;

trait OutlookFormat
{
    public function toArray()
    {
        return array_merge( parent::toArray(), [
            'title'=>$this->name,
            'label'=>$this->email,
            'icon'=> mb_substr( Str::upper($this->name), 0, 1)
        ]);
    }
}
