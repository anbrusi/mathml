<?php

namespace isCtl;

class CpartEval extends CcontrollerBase {

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VpartEval':
                $this->VpartEvalHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VpartEvalHandler():void {
        if (\isLib\LinstanceStore::available('currentFile')) {  
            $currentFile = \isLib\LinstanceStore::get('currentFile'); 
            $_POST['currentFile'] = $currentFile;
            $_POST['input'] = \isLib\Ltools::getExpression(\isLib\Lconfig::CF_FILES_DIR.$currentFile);
            try {
                // Original expression
                $LmathExpression = new \isLib\LmathExpression($_POST['input']);
                $originalTree = $LmathExpression->getParseTree();
                $_POST['originalTree'] = \isLib\LmathDebug::drawParseTree($originalTree);
                // Transformed expression
                $LtreeTrf = new \isLib\LtreeTrf(\isLib\Lconfig::CF_TRIG_UNIT);
                $trfTree = $LtreeTrf->partEvaluate($originalTree);
                $_POST['parseTree'] = \isLib\LmathDebug::drawParseTree($trfTree);
                // LateX
                $Llatex = new \isLib\Llatex($trfTree);
                $_POST['latex'] = $Llatex->getLatex();
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
        \isLib\LinstanceStore::setView('VpartEval');
    }
}