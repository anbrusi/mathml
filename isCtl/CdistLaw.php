<?php

namespace isCtl;

class CdistLaw extends CcontrollerBase {

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VdistLaw':
                $this->VdistLawHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VdistLawHandler():void {
        if (\isLib\LinstanceStore::available('currentFile')) {  
            $currentFile = \isLib\LinstanceStore::get('currentFile'); 
            $_POST['currentFile'] = $currentFile;
            $_POST['input'] = \isLib\Ltools::getExpression($currentFile);
            try {
                $LmathExpression = new \isLib\LmathExpression($_POST['input']);
                $originalTree = $LmathExpression->getParseTree();
                $_POST['originalTree'] = \isLib\LmathDebug::drawParseTree($originalTree);
                $LtreeTrf = new \isLib\LtreeTrf($originalTree);
                $_POST['parseTree'] = \isLib\LmathDebug::drawParseTree($LtreeTrf->applyDistLaw());
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
        \isLib\LinstanceStore::setView('VdistLaw');
    }
}