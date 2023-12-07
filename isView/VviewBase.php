<?php

namespace isView;

abstract class VviewBase {

    protected string $name;

    function __construct(string $name) {
        $this->name = $name;
    }

    abstract public function render():string;
}