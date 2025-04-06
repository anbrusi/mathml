<?php

namespace isCtl;

class CgaussElim extends CcontrollerBase {
    
    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VgaussElim':
                $this->VgaussElimHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VgaussElimHandler():void {
        if (\isLib\LinstanceStore::available('currentEquations')) {  
            $currentFile = \isLib\LinstanceStore::get('currentEquations'); 
            $_POST['currentFile'] = $currentFile;
            $_POST['input'] = \isLib\Ltools::getExpression(\isLib\Lconfig::CF_EQUATIONS_DIR.$currentFile);
            try {
                // Original expression
                $LmathExpression = new \isLib\LmathExpression($_POST['input']);
                 
            } catch (\isLib\isMathException $ex) {
                $_POST['ex'] = $ex;
                \isLib\LinstanceStore::setView('VmathError');
            }
        } else {
            $_POST['errmess'] = 'No current file set';
            \isLib\LinstanceStore::setView('Verror');
        }
    }

    public static function setInitialView(): void {        
        \isLib\LinstanceStore::setView('VgaussElim');
    }
}