<?php

namespace isCtl;

abstract class Ccontrollerbase {

    /**
     * Name Cxx of the controller
     * 
     * @var string
     */
    protected string $name;

    function __construct(string $name) {
        $this->name = $name;
    }

    /**
     * Sets the initial view of a given controller
     * 
     * @return void 
     */
    abstract public static function setInitialView():void;

    /**
     * Renders the current view of a given controller
     * 
     * @return string 
     */
    abstract public function render():string;

}