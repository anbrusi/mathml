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

    public function ViewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VadminFormulas':
                $this->VadminFormulasHandler();
                break;
            default:
                throw new \Exception('Unimplemented hadler for: '.$currentView);
        }
    }

    public function VadminFormulasHandler():void {
        if (isset($_POST['set'])) {
            $file = $_POST['available_files'];
            \isLib\LinstanceStore::set('currentFile', $file);
        }
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VeditFormula');
    }

}