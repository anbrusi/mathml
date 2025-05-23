<?php

namespace isCtl;

class CorderProducts extends CcontrollerBase {

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VorderProducts':
                $this->VorderProductsHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VorderProductsHandler():void {
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
                $trfTree = $LtreeTrf->ordProducts($originalTree);
                $_POST['parseTree'] = \isLib\LmathDebug::drawParseTree($trfTree);
                // LateX
                $Llatex = new \isLib\Llatex($trfTree);
                $_POST['latex'] = $Llatex->getLatex();
                // Debug function
                $_POST['trfSequence'] = $LtreeTrf->getTrfSequence();
                // Evaluation. If there are variables, we get their values
                $variableNames = $LmathExpression->getVariableNames(); // Parsed variables
                if (empty($variableNames)) {
                    $vars = [];
                } else {
                    $vars = \isLib\Ltools::getVars($currentFile); // Stored variables name => value
                    if ($vars === false) {
                        // Missing variable values
                        \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 9);
                    }
                    if (empty($vars)) {
                        // We set all variables to 1. The check might succeed even if the formulas are not equivalent,
                        // but it certainly fails if the formulas are not equivalent
                        foreach ($variableNames as $varname) {
                            $vars[$varname] = 1;
                        }
                    }
                }
                $Levaluator = new \isLib\Levaluator($vars, \isLib\Lconfig::CF_TRIG_UNIT);
                $_POST['originalValue'] = $Levaluator->evaluate($originalTree);
                $_POST['trfValue'] = $Levaluator->evaluate($trfTree,);
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
        \isLib\LinstanceStore::setView('VorderProducts');
    }
}