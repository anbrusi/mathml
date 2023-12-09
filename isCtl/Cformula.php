<?php

namespace isCtl;

class Cformula extends CcontrollerBase {

    /**
     * 
     * @param string $name The name of the controller
     * @return void 
     */
    function __construct(string $name) {
        parent::__construct($name);        
    }

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VadminFormulas':
                $this->VadminFormulasHandler();
                break;
            case 'VeditFile':
                $this->VnewFileHandler();
                break;
            default:
                throw new \Exception('Unimplemented hadler for: '.$currentView);
        }
    }

    public function VadminFormulasHandler():void {
        if (isset($_POST['set'])) {
            $file = $_POST['available_files'];
            \isLib\LinstanceStore::set('currentFile', $file);
        } elseif (isset($_POST['edit'])) {
            // change the view
            if (\isLib\LinstanceStore::available('currentFile')) {
                $_POST['oldFile'] = \isLib\LinstanceStore::get('currentFile');
            } else {
                throw new \Exception('no old file');
            }
            \isLib\LinstanceStore::setView('VeditFile');
        } elseif (isset($_POST['new'])) {
            // change the view
            $_POST['oldFile'] = '';
            \isLib\LinstanceStore::setView('VeditFile');
        }
    }

    public function VnewFileHandler():void {

    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VadminFormulas');
    }

}