<?php

namespace isCtl;

abstract class CcontrollerBase {

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
     * Reacts to POST set in the current view
     * 
     * @return void 
     */
    abstract public function viewHandler():void;

    /**
     * Renders the current view of a given controller
     * 
     * @return string 
     */
    public function render():string {
        $html = '';
        $view = \isLib\LinstanceStore::getView();
        $className = '\isView\\'.$view;
        $viewObj = new $className($view);
        $html .= $viewObj->render();
        return $html;
    }

}