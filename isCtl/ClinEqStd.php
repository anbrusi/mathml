<?php

namespace isCtl;

class ClinEqStd extends Ccontrollerbase {

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VlinEqStd':
                $this->VlinEqStdHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VlinEqStdHandler():void {
        if (\isLib\LinstanceStore::available('currentFile')) {  
            $currentFile = \isLib\LinstanceStore::get('currentFile'); 
            $_POST['currentFile'] = $currentFile;
            $_POST['input'] = \isLib\Ltools::getExpression($currentFile);
            try {
                // Original expression
                $LmathExpression = new \isLib\LmathExpression($_POST['input']);
                $originalTree = $LmathExpression->getParseTree();
                $_POST['originalTree'] = \isLib\LmathDebug::drawParseTree($originalTree);
                // Transformed expression
                $LtreeTrf = new \isLib\LtreeTrf(\isLib\Lconfig::CF_TRIG_UNIT);
                $_POST['linEqStd'] = $LtreeTrf->linEqStd($originalTree);
                // Debug function
                $_POST['trfSequence'] = $LtreeTrf->getTrfSequence();
                 
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
        \isLib\LinstanceStore::setView('VlinEqStd');
    }
}