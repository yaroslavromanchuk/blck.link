<?php

use yii\rbac\Rule;

class SubLabelRole extends Rule
{
    public $name = 'isSubLabel';
    public function execute($user, $item, $params)
    {
        // TODO: Implement execute() method.
    }
}