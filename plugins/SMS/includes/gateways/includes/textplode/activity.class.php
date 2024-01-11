<?php

namespace plugins\SMS\includes\gateways\includes\textplode;
class TP_Activity
{

    private $parent;

    public function __construct($parent)
    {
        $this->parent = $parent;
    }

    public function get_all()
    {
        $result = $this->parent->request('activity/get/all');
        return $result ? $result : null;
    }

}