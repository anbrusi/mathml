<?php

namespace isCtl;

class Cparse extends CcontrollerBase {

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'Vparse':
                $this->VparseHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VparseHandler():void {
        if (\isLib\LinstanceStore::available('currentFile')) {  
            $currentFile = \isLib\LinstanceStore::get('currentFile'); 
            $_POST['currentFile'] = $currentFile;
            $_POST['input']= \isLib\Ltools::getExpression($currentFile);
            try {
                $LmathExpression = new \isLib\LmathExpression($_POST['input']);
                $_POST['parseTree'] = \isLib\LmathDebug::drawParseTree($LmathExpression->getParseTree());
                $_POST['variableNames'] = $LmathExpression->getVariableNames();
            } catch (\isLib\isMathException $ex) {
                $_POST['ex'] = $ex;
                \isLib\LinstanceStore::setView('VmathError');
            }
        } else {
            $_POST['errmess'] = 'No current file set';
            \isLib\LinstanceStore::setView('Verror');
        }
    }
    
    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('Vparse');
    }
}