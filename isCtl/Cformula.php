<?php

namespace isCtl;

class Cformula extends CcontrollerBase {
    
    private string $view = '';

    /**
     * 
     * @param string $name The name of the controller
     * @return void 
     */
    function __construct(string $name) {
        parent::__construct($name);
        
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VeditFormula');
    }

    public function render():string {
        $html = '';
        $html .= '<p>'.$this->name.'</p>';
        return $html;
    }
}